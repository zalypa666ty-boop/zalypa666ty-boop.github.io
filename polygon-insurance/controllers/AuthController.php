<?php
/**
 * Контроллер аутентификации
 * Регистрация, вход, выход, управление сессиями
 */

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Log.php';

class AuthController {
    private $db;
    private $user;
    private $log;
    
    public function __construct($db) {
        $this->db = $db;
        $this->user = new User($db);
        $this->log = new Log($db);
    }
    
    /**
     * CSRF-токен
     */
    private function generateCsrfToken() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    private function verifyCsrfToken($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Страница входа
     */
    public function login() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Проверка CSRF
            if (!$this->verifyCsrfToken($_POST['csrf_token'] ?? '')) {
                $error = 'Ошибка безопасности. Попробуйте снова.';
                include __DIR__ . '/../views/login.php';
                return;
            }
            
            $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
            $password = $_POST['password'] ?? '';
            
            $result = $this->user->login($email, $password);
            
            if ($result['success']) {
                $_SESSION['user_id'] = $result['user']['id'];
                $_SESSION['email'] = $result['user']['email'];
                $_SESSION['full_name'] = $result['user']['full_name'];
                $_SESSION['role'] = $result['user']['role'];
                
                // Логирование входа
                $this->log->add($result['user']['id'], 'Вход в систему', "Успешный вход пользователя $email");
                
                header('Location: /polygon-insurance/dashboard');
                exit();
            } else {
                $error = $result['error'];
                // Логирование неудачной попытки
                $this->log->add(null, 'Неудачная попытка входа', "Попытка входа с email: $email");
            }
        }
        
        $csrf_token = $this->generateCsrfToken();
        include __DIR__ . '/../views/login.php';
    }
    
    /**
     * Страница регистрации
     */
    public function register() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Проверка CSRF
            if (!$this->verifyCsrfToken($_POST['csrf_token'] ?? '')) {
                $error = 'Ошибка безопасности. Попробуйте снова.';
                include __DIR__ . '/../views/register.php';
                return;
            }
            
            $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
            $password = $_POST['password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';
            $fullName = htmlspecialchars($_POST['full_name'] ?? '');
            $phone = htmlspecialchars($_POST['phone'] ?? '');
            $role = $_POST['role'] ?? 'client';
            
            // Валидация
            $errors = [];
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Неверный формат email';
            }
            if (strlen($password) < 6) {
                $errors[] = 'Пароль должен быть не менее 6 символов';
            }
            if ($password !== $confirmPassword) {
                $errors[] = 'Пароли не совпадают';
            }
            if (empty($fullName)) {
                $errors[] = 'Введите ФИО';
            }
            
            if (empty($errors)) {
                $result = $this->user->register($email, $password, $fullName, $phone, $role);
                
                if ($result['success']) {
                    $this->log->add($result['user_id'], 'Регистрация', "Зарегистрирован новый пользователь $email");
                    header('Location: /polygon-insurance/login?registered=1');
                    exit();
                } else {
                    $error = $result['error'];
                }
            } else {
                $error = implode('<br>', $errors);
            }
        }
        
        $csrf_token = $this->generateCsrfToken();
        include __DIR__ . '/../views/register.php';
    }
    
    /**
     * Выход из системы
     */
    public function logout() {
        if (isset($_SESSION['user_id'])) {
            $this->log->add($_SESSION['user_id'], 'Выход из системы', 'Пользователь вышел');
        }
        
        session_destroy();
        header('Location: /polygon-insurance/login');
        exit();
    }
}
?>