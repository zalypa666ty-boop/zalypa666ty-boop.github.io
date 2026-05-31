<div class="header">
    <div class="header-container">
        <div class="logo">
            <h1>Полигон-страхование</h1>
            <p>Надёжная защита ваших интересов</p>
        </div>
        
        <div class="burger">
            <span></span>
            <span></span>
            <span></span>
        </div>
        
        <div class="nav">
            <a href="/polygon-insurance/">Главная</a>
            <a href="/polygon-insurance/calculator">Калькулятор</a>
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="/polygon-insurance/dashboard">Личный кабинет</a>
                <a href="/polygon-insurance/profile">Профиль</a>
                <a href="/polygon-insurance/logout">Выход (<?php echo htmlspecialchars($_SESSION['full_name']); ?>)</a>
            <?php else: ?>
                <a href="/polygon-insurance/login">Вход</a>
                <a href="/polygon-insurance/register">Регистрация</a>
            <?php endif; ?>
        </div>
    </div>
</div>