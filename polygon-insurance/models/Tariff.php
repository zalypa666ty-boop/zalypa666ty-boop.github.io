<?php
/**
 * Модель тарифов
 * Управление страховыми тарифами и коэффициентами
 */

class Tariff {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    /**
     * Получение всех тарифов
     */
    public function getAllTariffs($type = null) {
        $sql = "SELECT * FROM tariffs";
        $params = [];
        
        if ($type) {
            $sql .= " WHERE type = ?";
            $params[] = $type;
        }
        
        $sql .= " ORDER BY type, id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    /**
     * Получение тарифа по ID
     */
    public function getTariffById($id) {
        $stmt = $this->db->prepare("SELECT * FROM tariffs WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    /**
     * Создание нового тарифа
     */
    public function createTariff($type, $paramName, $coefficient, $basePrice) {
        $stmt = $this->db->prepare("
            INSERT INTO tariffs (type, param_name, coefficient, base_price)
            VALUES (?, ?, ?, ?)
        ");
        return $stmt->execute([$type, $paramName, $coefficient, $basePrice]);
    }
    
    /**
     * Обновление тарифа
     */
    public function updateTariff($id, $coefficient, $basePrice) {
        $stmt = $this->db->prepare("
            UPDATE tariffs 
            SET coefficient = ?, base_price = ?, updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        return $stmt->execute([$coefficient, $basePrice, $id]);
    }
    
    /**
     * Удаление тарифа
     */
    public function deleteTariff($id) {
        $stmt = $this->db->prepare("DELETE FROM tariffs WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
?>