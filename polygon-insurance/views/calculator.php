<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../models/Tariff.php';

$tariffModel = new Tariff($conn);
$tariffs = $tariffModel->getAllTariffs();

// Генерация CSRF-токена
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Калькулятор страховки - Полигон-страхование</title>
    <link rel="stylesheet" href="/polygon-insurance/assets/css/style.css">
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="container">
        <div class="card">
            <h2 class="card-title">Калькулятор страховых продуктов</h2>
            <p>Рассчитайте стоимость страхового полиса онлайн за несколько секунд</p>
        </div>
        
        <div class="card">
            <form id="calculator-form" data-validate="true">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                
                <div class="form-group">
                    <label for="insurance-type">Вид страхования</label>
                    <select id="insurance-type" name="type" class="form-control" required>
                        <option value="osago">ОСАГО</option>
                        <option value="casco">КАСКО</option>
                        <option value="health">Страхование здоровья</option>
                    </select>
                </div>
                
                <!-- Поля для ОСАГО -->
                <div id="osago-fields">
                    <div class="form-group">
                        <label for="vehicle">Марка и модель автомобиля</label>
                        <input type="text" id="vehicle" name="vehicle" class="form-control" placeholder="Например: Lada Granta">
                    </div>
                    <div class="form-group">
                        <label for="power">Мощность двигателя (л.с.)</label>
                        <input type="number" id="power" name="power" class="form-control" placeholder="Введите мощность" required>
                    </div>
                    <div class="form-group">
                        <label for="driver_age">Возраст водителя</label>
                        <input type="number" id="driver_age" name="driver_age" class="form-control" placeholder="Полных лет" required>
                    </div>
                    <div class="form-group">
                        <label for="experience">Стаж вождения (лет)</label>
                        <input type="number" id="experience" name="experience" class="form-control" placeholder="Стаж" required>
                    </div>
                    <div class="form-group">
                        <label for="region">Регион регистрации</label>
                        <select id="region" name="region" class="form-control">
                            <option value="Москва">Москва</option>
                            <option value="Регион">Другой регион</option>
                        </select>
                    </div>
                </div>
                
                <!-- Поля для КАСКО -->
                <div id="casco-fields" style="display: none;">
                    <div class="form-group">
                        <label for="car_value">Стоимость автомобиля (₽)</label>
                        <input type="number" id="car_value" name="car_value" class="form-control" placeholder="Рыночная стоимость" required>
                    </div>
                </div>
                
                <!-- Поля для здоровья -->
                <div id="health-fields" style="display: none;">
                    <div class="form-group">
                        <label for="person_name">ФИО застрахованного</label>
                        <input type="text" id="person_name" name="person_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="person_age">Возраст</label>
                        <input type="number" id="person_age" name="person_age" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="person_type">Категория</label>
                        <select id="person_type" name="type" class="form-control">
                            <option value="adult">Взрослый (18+)</option>
                            <option value="child">Ребенок (до 18)</option>
                        </select>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary">Рассчитать стоимость</button>
            </form>
            
            <div id="result-block" style="display: none; margin-top: 2rem; padding: 1rem; background: #e8f4f8; border-radius: 10px;">
                <h3>Результат расчета:</h3>
                <p style="font-size: 24px; font-weight: bold; color: #0056b3;" id="result-price"></p>
                <button id="apply-policy" class="btn btn-success">Оформить полис</button>
            </div>
        </div>
    </div>
    
    <?php include 'footer.php'; ?>
    
    <script src="/polygon-insurance/assets/js/main.js"></script>
</body>
</html>