<?php
/**
 * Модель пользователя
 * Управление данными пользователей, аутентификация, роли
 */

class User {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    /**
     * Регистрация нового пользователя
     */
    public function register($email, $password, $fullName, $phone, $role = 'client', $agentId = null) {
        // Проверка существования email
        $stmt = $this->db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            return ['success' => false, 'error' => 'Email уже зарегистрирован'];
        }
        
        // Хэширование пароля
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Вставка пользователя
        $stmt = $this->db->prepare("
            INSERT INTO users (email, password_hash, full_name, phone, role, agent_id) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        if ($stmt->execute([$email, $hashedPassword, $fullName, $phone, $role, $agentId])) {
            return ['success' => true, 'user_id' => $this->db->lastInsertId()];
        }
        
        return ['success' => false, 'error' => 'Ошибка регистрации'];
    }
    
    /**
     * Аутентификация пользователя
     */
    public function login($email, $password) {
        $stmt = $this->db->prepare("
            SELECT id, email, password_hash, full_name, role, status 
            FROM users 
            WHERE email = ? AND status = 'active'
        ");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password_hash'])) {
            return ['success' => true, 'user' => $user];
        }
        
        return ['success' => false, 'error' => 'Неверный email или пароль'];
    }
    
    /**
     * Получение информации о пользователе
     */
    public function getUserById($id) {
        $stmt = $this->db->prepare("
            SELECT id, email, full_name, phone, role, status, agent_id, created_at 
            FROM users 
            WHERE id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    /**
     * Обновление профиля пользователя
     */
    public function updateProfile($id, $fullName, $phone) {
        $stmt = $this->db->prepare("
            UPDATE users 
            SET full_name = ?, phone = ? 
            WHERE id = ?
        ");
        return $stmt->execute([$fullName, $phone, $id]);
    }
    
    /**
     * Смена пароля
     */
    public function changePassword($id, $oldPassword, $newPassword) {
        // Получаем текущий хэш
        $stmt = $this->db->prepare("SELECT password_hash FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch();
        
        if (!password_verify($oldPassword, $user['password_hash'])) {
            return ['success' => false, 'error' => 'Неверный текущий пароль'];
        }
        
        $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $this->db->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
        
        if ($stmt->execute([$newHash, $id])) {
            return ['success' => true];
        }
        
        return ['success' => false, 'error' => 'Ошибка смены пароля'];
    }
    
    /**
     * Получение списка клиентов для агента
     */
    public function getClientsByAgent($agentId) {
        $stmt = $this->db->prepare("
            SELECT id, email, full_name, phone, created_at 
            FROM users 
            WHERE agent_id = ? AND role = 'client'
            ORDER BY created_at DESC
        ");
        $stmt->execute([$agentId]);
        return $stmt->fetchAll();
    }
    
    /**
     * Получение всех пользователей (для админа)
     */
    public function getAllUsers($filters = []) {
        $sql = "SELECT id, email, full_name, phone, role, status, created_at FROM users WHERE 1=1";
        $params = [];
        
        if (isset($filters['role']) && $filters['role']) {
            $sql .= " AND role = ?";
            $params[] = $filters['role'];
        }
        
        if (isset($filters['status']) && $filters['status']) {
            $sql .= " AND status = ?";
            $params[] = $filters['status'];
        }
        
        $sql .= " ORDER BY created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    /**
     * Блокировка/разблокировка пользователя
     */
    public function toggleUserStatus($userId, $status) {
        $stmt = $this->db->prepare("UPDATE users SET status = ? WHERE id = ?");
        return $stmt->execute([$status, $userId]);
    }
    
    /**
     * Смена роли пользователя
     */
    public function changeUserRole($userId, $role) {
        $stmt = $this->db->prepare("UPDATE users SET role = ? WHERE id = ?");
        return $stmt->execute([$role, $userId]);
    }
}
?>