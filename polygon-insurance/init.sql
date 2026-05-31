-- Создание базы данных
CREATE DATABASE IF NOT EXISTS `polygon_insurance`
CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE `polygon_insurance`;

-- Таблица пользователей
CREATE TABLE `users` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `email` VARCHAR(100) UNIQUE NOT NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `full_name` VARCHAR(150) NOT NULL,
    `phone` VARCHAR(20),
    `role` ENUM('client', 'agent', 'admin') DEFAULT 'client',
    `status` ENUM('active', 'blocked') DEFAULT 'active',
    `agent_id` INT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`agent_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    INDEX idx_email (`email`),
    INDEX idx_role (`role`)
) ENGINE=InnoDB;

-- Таблица страховых полисов
CREATE TABLE `policies` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `user_id` INT NOT NULL,
    `agent_id` INT NULL,
    `policy_number` VARCHAR(50) UNIQUE NOT NULL,
    `type` ENUM('osago', 'casco', 'health') NOT NULL,
    `data_json` JSON NOT NULL,
    `premium` DECIMAL(10,2) NOT NULL,
    `status` ENUM('pending', 'active', 'expired', 'cancelled') DEFAULT 'pending',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `valid_from` DATE,
    `valid_to` DATE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`agent_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    INDEX idx_policy_number (`policy_number`),
    INDEX idx_status (`status`)
) ENGINE=InnoDB;

-- Таблица тарифов
CREATE TABLE `tariffs` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `type` ENUM('osago', 'casco', 'health') NOT NULL,
    `param_name` VARCHAR(100) NOT NULL,
    `coefficient` DECIMAL(5,2) NOT NULL,
    `base_price` DECIMAL(10,2) NOT NULL,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_type (`type`)
) ENGINE=InnoDB;

-- Таблица платежей
CREATE TABLE `payments` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `policy_id` INT NOT NULL,
    `amount` DECIMAL(10,2) NOT NULL,
    `status` ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
    `transaction_id` VARCHAR(100),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`policy_id`) REFERENCES `policies`(`id`) ON DELETE CASCADE,
    INDEX idx_status (`status`)
) ENGINE=InnoDB;

-- Таблица заявок
CREATE TABLE `requests` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `user_id` INT NOT NULL,
    `policy_id` INT NULL,
    `status` ENUM('new', 'processing', 'completed', 'rejected') DEFAULT 'new',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`policy_id`) REFERENCES `policies`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Таблица логов действий
CREATE TABLE `logs` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `user_id` INT NULL,
    `action` VARCHAR(255) NOT NULL,
    `details` TEXT,
    `ip` VARCHAR(45),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    INDEX idx_user_id (`user_id`),
    INDEX idx_action (`action`)
) ENGINE=InnoDB;

-- Вставка тестовых данных
-- Пароли: password123 (хэш для всех тестовых пользователей)
INSERT INTO `users` (`email`, `password_hash`, `full_name`, `phone`, `role`, `status`) VALUES
('admin@polygon.ru', '$2y$10$YourHashHere', 'Администратор Системы', '+7(999)111-2233', 'admin', 'active'),
('agent@polygon.ru', '$2y$10$YourHashHere', 'Иванов Иван Агент', '+7(999)222-3344', 'agent', 'active'),
('client@polygon.ru', '$2y$10$YourHashHere', 'Петров Петр Клиент', '+7(999)333-4455', 'client', 'active');

-- Добавление тестовых тарифов
INSERT INTO `tariffs` (`type`, `param_name`, `coefficient`, `base_price`) VALUES
('osago', 'Базовая ставка', 1.00, 2500.00),
('osago', 'Мощность до 50 л.с.', 0.80, 0.00),
('osago', 'Мощность 51-70 л.с.', 1.00, 0.00),
('osago', 'Мощность 71-100 л.с.', 1.10, 0.00),
('osago', 'Мощность 101-120 л.с.', 1.20, 0.00),
('osago', 'Мощность более 120 л.с.', 1.50, 0.00),
('osago', 'Возраст/стаж молодые', 0.90, 0.00),
('osago', 'Возраст/стаж опытные', 0.60, 0.00),
('casco', 'Базовая ставка (1% от стоимости авто)', 0.01, 0.00),
('health', 'Базовая ставка (взрослый)', 0.00, 5000.00),
('health', 'Базовая ставка (ребенок)', 0.00, 3000.00);

-- Тестовые полисы
INSERT INTO `policies` (`user_id`, `policy_number`, `type`, `data_json`, `premium`, `status`, `valid_from`, `valid_to`) VALUES
(3, 'POL-2024-0001', 'osago', '{"vehicle":"Lada Granta","power":90,"driver_age":30,"experience":10,"region":"Москва"}', 4125.00, 'active', '2024-01-01', '2024-12-31'),
(3, 'POL-2024-0002', 'health', '{"person_name":"Петров Петр","age":35,"type":"adult"}', 5000.00, 'active', '2024-01-01', '2024-12-31');