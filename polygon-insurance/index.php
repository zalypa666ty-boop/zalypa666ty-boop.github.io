<?php
/**
 * Front controller - единая точка входа
 * Маршрутизация запросов и обработка сессий
 */

session_start();
require_once __DIR__ . '/config/db.php';

// Простая маршрутизация
$request = $_SERVER['REQUEST_URI'];
$method = $_SERVER['REQUEST_METHOD'];

// Извлекаем путь без GET параметров
$path = parse_url($request, PHP_URL_PATH);
$path = str_replace('/polygon-insurance', '', $path); // Базовая папка

// Проверка авторизации для защищенных страниц
$publicRoutes = ['/', '/login', '/register', '/calculator', '/api'];
$isPublic = false;
foreach ($publicRoutes as $route) {
    if (strpos($path, $route) === 0) {
        $isPublic = true;
        break;
    }
}

if (!$isPublic && !isset($_SESSION['user_id'])) {
    header('Location: /polygon-insurance/login');
    exit();
}

// Маршрутизация
switch($path) {
    case '/':
    case '/index':
        require_once 'views/calculator.php';
        break;
        
    case '/login':
        require_once 'controllers/AuthController.php';
        $auth = new AuthController($conn);
        $auth->login();
        break;
        
    case '/register':
        require_once 'controllers/AuthController.php';
        $auth = new AuthController($conn);
        $auth->register();
        break;
        
    case '/logout':
        require_once 'controllers/AuthController.php';
        $auth = new AuthController($conn);
        $auth->logout();
        break;
        
    case '/dashboard':
        // Перенаправление в зависимости от роли
        if ($_SESSION['role'] == 'admin') {
            header('Location: /polygon-insurance/admin');
        } elseif ($_SESSION['role'] == 'agent') {
            header('Location: /polygon-insurance/agent');
        } else {
            header('Location: /polygon-insurance/client');
        }
        break;
        
    case '/client':
        require_once 'controllers/PolicyController.php';
        $policyCtrl = new PolicyController($conn);
        $policyCtrl->clientDashboard();
        break;
        
    case '/agent':
        require_once 'controllers/AgentController.php';
        $agentCtrl = new AgentController($conn);
        $agentCtrl->dashboard();
        break;
        
    case '/admin':
        require_once 'controllers/AdminController.php';
        $adminCtrl = new AdminController($conn);
        $adminCtrl->dashboard();
        break;
        
    case '/calculator':
        require_once 'views/calculator.php';
        break;
        
    case '/policy-form':
        require_once 'controllers/PolicyController.php';
        $policyCtrl = new PolicyController($conn);
        $policyCtrl->showForm();
        break;
        
    case '/profile':
        require_once 'views/profile.php';
        break;
        
    case '/api':
        require_once 'controllers/ApiController.php';
        $api = new ApiController($conn);
        $action = $_GET['action'] ?? '';
        $api->handleRequest($action);
        break;
        
    default:
        http_response_code(404);
        echo "404 - Страница не найдена";
        break;
}
?>