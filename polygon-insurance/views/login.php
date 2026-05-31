<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход - Полигон-страхование</title>
    <link rel="stylesheet" href="/polygon-insurance/assets/css/style.css">
</head>
<body>
    <div class="container" style="max-width: 500px;">
        <div class="card">
            <h2 class="card-title">Вход в систему</h2>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if (isset($_GET['registered'])): ?>
                <div class="alert alert-success">Регистрация успешна! Теперь вы можете войти.</div>
            <?php endif; ?>
            
            <form method="POST" action="" data-validate="true">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Пароль</label>
                    <input type="password" id="password" name="password" class="form-control" required>
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%;">Войти</button>
            </form>
            
            <div style="text-align: center; margin-top: 1rem;">
                <a href="/polygon-insurance/register">Нет аккаунта? Зарегистрируйтесь</a>
            </div>
        </div>
        
        <div class="card" style="text-align: center;">
            <h3>Тестовые данные для входа</h3>
            <p>Админ: admin@polygon.ru / password123</p>
            <p>Агент: agent@polygon.ru / password123</p>
            <p>Клиент: client@polygon.ru / password123</p>
        </div>
    </div>
</body>
</html>