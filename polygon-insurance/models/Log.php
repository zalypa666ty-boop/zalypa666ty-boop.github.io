<?php
/**
 * Модель логирования
 * Запись действий пользователей для аудита
 */

class Log {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    /**
     * Запись действия в лог
     */
    public function add($userId, $action, $details = null) {
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        
        $stmt = $this->db->prepare("
            INSERT INTO logs (user_id, action, details, ip)
            VALUES (?, ?, ?, ?)
        ");
        return $stmt->execute([$userId, $action, $details, $ip]);
    }
    
    /**
     * Получение логов пользователя
     */
    public function getUserLogs($userId, $limit = 50) {
        $stmt = $this->db->prepare("
            SELECT * FROM logs 
            WHERE user_id = ? 
            ORDER BY created_at DESC 
            LIMIT ?
        ");
        $stmt->execute([$userId, $limit]);
        return $stmt->fetchAll();
    }
    
    /**
     * Получение всех логов (для админа)
     */
    public function getAllLogs($limit = 100) {
        $stmt = $this->db->prepare("
            SELECT l.*, u.full_name, u.email
            FROM logs l
            LEFT JOIN users u ON l.user_id = u.id
            ORDER BY l.created_at DESC
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }
    
    /**
     * Очистка старых логов (старше 90 дней)
     */
    public function cleanOldLogs() {
        $stmt = $this->db->prepare("DELETE FROM logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY)");
        return $stmt->execute();
    }
}
?>