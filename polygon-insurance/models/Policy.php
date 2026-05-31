<?php
/**
 * Модель страховых полисов
 * Управление полисами, расчёт стоимости, генерация PDF
 */

class Policy {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    /**
     * Генерация уникального номера полиса
     */
    private function generatePolicyNumber() {
        $prefix = date('Y');
        $random = str_pad(random_int(1, 9999), 4, '0', STR_PAD_LEFT);
        return "POL-{$prefix}-{$random}";
    }
    
    /**
     * Расчёт стоимости ОСАГО
     */
    public function calculateOsago($data) {
        // Получаем тарифы из БД
        $stmt = $this->db->prepare("SELECT param_name, coefficient, base_price FROM tariffs WHERE type = 'osago'");
        $stmt->execute();
        $tariffs = $stmt->fetchAll();
        
        $tariffsMap = [];
        foreach ($tariffs as $tariff) {
            $tariffsMap[$tariff['param_name']] = $tariff;
        }
        
        // Базовый тариф
        $baseRate = $tariffsMap['Базовая ставка']['base_price'];
        
        // Коэффициент мощности
        $power = $data['power'];
        $powerFactor = 1.0;
        if ($power <= 50) $powerFactor = 0.8;
        elseif ($power <= 70) $powerFactor = 1.0;
        elseif ($power <= 100) $powerFactor = 1.1;
        elseif ($power <= 120) $powerFactor = 1.2;
        else $powerFactor = 1.5;
        
        // Коэффициент возраста/стажа
        $age = $data['driver_age'];
        $experience = $data['experience'];
        $driverFactor = 0.6;
        if ($age < 22 && $experience < 3) $driverFactor = 0.9;
        elseif ($age < 22 && $experience >= 3) $driverFactor = 0.8;
        elseif ($age >= 22 && $experience < 3) $driverFactor = 0.7;
        else $driverFactor = 0.6;
        
        // Коэффициент региона (упрощённо)
        $regionFactor = ($data['region'] == 'Москва') ? 1.0 : 0.8;
        
        // Итоговая стоимость
        $premium = $baseRate * $powerFactor * $driverFactor * $regionFactor;
        
        return round($premium, 2);
    }
    
    /**
     * Расчёт стоимости КАСКО (1% от стоимости авто)
     */
    public function calculateCasco($data) {
        $carValue = $data['car_value'];
        $premium = $carValue * 0.01; // 1% от стоимости авто
        return round($premium, 2);
    }
    
    /**
     * Расчёт стоимости страхования здоровья
     */
    public function calculateHealth($data) {
        $stmt = $this->db->prepare("SELECT base_price FROM tariffs WHERE type = 'health' AND param_name = ?");
        $stmt->execute([$data['type'] == 'adult' ? 'Базовая ставка (взрослый)' : 'Базовая ставка (ребенок)']);
        $tariff = $stmt->fetch();
        
        return $tariff ? $tariff['base_price'] : 5000;
    }
    
    /**
     * Создание нового полиса
     */
    public function createPolicy($userId, $agentId, $type, $data, $premium) {
        $policyNumber = $this->generatePolicyNumber();
        $validFrom = date('Y-m-d');
        $validTo = date('Y-m-d', strtotime('+1 year'));
        
        $stmt = $this->db->prepare("
            INSERT INTO policies (user_id, agent_id, policy_number, type, data_json, premium, status, valid_from, valid_to)
            VALUES (?, ?, ?, ?, ?, ?, 'active', ?, ?)
        ");
        
        $dataJson = json_encode($data, JSON_UNESCAPED_UNICODE);
        
        if ($stmt->execute([$userId, $agentId, $policyNumber, $type, $dataJson, $premium, $validFrom, $validTo])) {
            $policyId = $this->db->lastInsertId();
            
            // Создаём запись о платеже
            $stmt = $this->db->prepare("
                INSERT INTO payments (policy_id, amount, status, transaction_id)
                VALUES (?, ?, 'completed', ?)
            ");
            $transactionId = 'TXN_' . time() . '_' . $policyId;
            $stmt->execute([$policyId, $premium, $transactionId]);
            
            return ['success' => true, 'policy_id' => $policyId, 'policy_number' => $policyNumber];
        }
        
        return ['success' => false, 'error' => 'Ошибка создания полиса'];
    }
    
    /**
     * Получение полисов пользователя
     */
    public function getUserPolicies($userId) {
        $stmt = $this->db->prepare("
            SELECT p.*, 
                   (SELECT amount FROM payments WHERE policy_id = p.id AND status = 'completed') as paid_amount
            FROM policies p
            WHERE p.user_id = ?
            ORDER BY p.created_at DESC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }
    
    /**
     * Получение полисов для агента
     */
    public function getAgentPolicies($agentId) {
        $stmt = $this->db->prepare("
            SELECT p.*, u.full_name as client_name, u.email as client_email
            FROM policies p
            JOIN users u ON p.user_id = u.id
            WHERE p.agent_id = ?
            ORDER BY p.created_at DESC
        ");
        $stmt->execute([$agentId]);
        return $stmt->fetchAll();
    }
    
    /**
     * Получение всех полисов (для админа)
     */
    public function getAllPolicies($filters = []) {
        $sql = "
            SELECT p.*, u.full_name as client_name, u.email as client_email,
                   ag.full_name as agent_name
            FROM policies p
            JOIN users u ON p.user_id = u.id
            LEFT JOIN users ag ON p.agent_id = ag.id
            WHERE 1=1
        ";
        $params = [];
        
        if (isset($filters['type']) && $filters['type']) {
            $sql .= " AND p.type = ?";
            $params[] = $filters['type'];
        }
        
        if (isset($filters['status']) && $filters['status']) {
            $sql .= " AND p.status = ?";
            $params[] = $filters['status'];
        }
        
        if (isset($filters['search']) && $filters['search']) {
            $sql .= " AND (p.policy_number LIKE ? OR u.full_name LIKE ? OR u.email LIKE ?)";
            $search = "%{$filters['search']}%";
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
        }
        
        $sql .= " ORDER BY p.created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    /**
     * Генерация PDF полиса
     */
    public function generatePDF($policyId) {
        // Получаем данные полиса
        $stmt = $this->db->prepare("
            SELECT p.*, u.full_name as client_name, u.email as client_email, u.phone
            FROM policies p
            JOIN users u ON p.user_id = u.id
            WHERE p.id = ?
        ");
        $stmt->execute([$policyId]);
        $policy = $stmt->fetch();
        
        if (!$policy) {
            return false;
        }
        
        // Создаём простой HTML для PDF
        $html = "
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>Страховой полис {$policy['policy_number']}</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 40px; }
                .header { text-align: center; border-bottom: 2px solid #0056b3; padding-bottom: 10px; margin-bottom: 20px; }
                .company { color: #0056b3; font-size: 24px; font-weight: bold; }
                .title { font-size: 18px; font-weight: bold; margin: 20px 0; text-align: center; }
                .info { margin: 20px 0; }
                .info table { width: 100%; border-collapse: collapse; }
                .info td { padding: 8px; border-bottom: 1px solid #ddd; }
                .label { font-weight: bold; width: 40%; }
                .footer { margin-top: 40px; text-align: center; font-size: 12px; color: #666; border-top: 1px solid #ddd; padding-top: 10px; }
                .qr { text-align: center; margin-top: 20px; }
            </style>
        </head>
        <body>
            <div class='header'>
                <div class='company'>ООО «Полигон-страхование»</div>
                <div>Лицензия СИ № 1234 от 01.01.2024</div>
            </div>
            <div class='title'>
                СТРАХОВОЙ ПОЛИС № {$policy['policy_number']}
            </div>
            <div class='info'>
                <table>
                    <tr><td class='label'>Страхователь:</td><td>{$policy['client_name']}</td></tr>
                    <tr><td class='label'>Контактный телефон:</td><td>{$policy['phone']}</td></tr>
                    <tr><td class='label'>Email:</td><td>{$policy['client_email']}</td></tr>
                    <tr><td class='label'>Вид страхования:</td><td>" . $this->getPolicyTypeName($policy['type']) . "</td></tr>
                    <tr><td class='label'>Страховая премия:</td><td>" . number_format($policy['premium'], 2) . " ₽</td></tr>
                    <tr><td class='label'>Дата начала действия:</td><td>{$policy['valid_from']}</td></tr>
                    <tr><td class='label'>Дата окончания действия:</td><td>{$policy['valid_to']}</td></tr>
                    <tr><td class='label'>Статус:</td><td>" . $this->getStatusName($policy['status']) . "</td></tr>
                </table>
            </div>
            <div class='qr'>
                <img src='https://chart.googleapis.com/chart?chs=100x100&cht=qr&chl={$policy['policy_number']}' alt='QR-код'>
            </div>
            <div class='footer'>
                ООО «Полигон-страхование»<br>
                Адрес: г. Москва, ул. Страховая, д. 1<br>
                Телефон: +7 (999) 123-45-67 | Email: info@polygon.ru
            </div>
        </html>
        ";
        
        // Сохраняем HTML для генерации PDF
        // В реальном проекте используйте библиотеку типа TCPDF или Dompdf
        file_put_contents(__DIR__ . '/../assets/pdf/policy_' . $policyId . '.html', $html);
        
        return '/assets/pdf/policy_' . $policyId . '.html';
    }
    
    private function getPolicyTypeName($type) {
        $types = [
            'osago' => 'ОСАГО',
            'casco' => 'КАСКО',
            'health' => 'Страхование здоровья'
        ];
        return $types[$type] ?? $type;
    }
    
    private function getStatusName($status) {
        $statuses = [
            'pending' => 'Ожидание',
            'active' => 'Активен',
            'expired' => 'Истек',
            'cancelled' => 'Отменен'
        ];
        return $statuses[$status] ?? $status;
    }
}
?>