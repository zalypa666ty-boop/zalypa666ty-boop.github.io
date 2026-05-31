<?php
/**
 * Database configuration and connection
 * Использует PDO с подготовленными запросами для безопасности
 */

class Database {
    private static $instance = null;
    private $connection;
    
    private function __construct() {
        // Загрузка конфигурации из .env
        $env = parse_ini_file(__DIR__ . '/../.env');
        
        $host = $env['DB_HOST'];
        $dbname = $env['DB_NAME'];
        $user = $env['DB_USER'];
        $pass = $env['DB_PASS'];
        
        try {
            $this->connection = new PDO(
                "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
                $user,
                $pass,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false // Защита от SQL-инъекций
                ]
            );
        } catch(PDOException $e) {
            die("Ошибка подключения к базе данных: " . $e->getMessage());
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->connection;
    }
}

// Возвращаем соединение для простого использования
return Database::getInstance()->getConnection();
?>