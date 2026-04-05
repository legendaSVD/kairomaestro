-- База данных kairomaestro
-- MySQL 8

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- --------------------------------------------------------
-- Таблица пользователей
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `users` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `phone` VARCHAR(20) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `first_name` VARCHAR(100) NOT NULL,
  `last_name` VARCHAR(100) NOT NULL,
  `middle_name` VARCHAR(100),
  `role` ENUM('worker', 'organizer') NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `last_login` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_phone` (`phone`),
  INDEX `idx_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Профили работников
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `worker_profiles` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `specialty` VARCHAR(100) NOT NULL,
  `has_car` TINYINT(1) DEFAULT 0,
  `car_brand` VARCHAR(100),
  `car_number` VARCHAR(20),
  `additional_info` TEXT,
  `hourly_rate` DECIMAL(10,2) DEFAULT 0.00,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_specialty` (`specialty`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Мероприятия
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `events` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `organizer_id` INT(11) NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `event_date` DATE NOT NULL,
  `start_time` TIME NOT NULL,
  `end_time` TIME,
  `location` VARCHAR(255) NOT NULL,
  `latitude` DECIMAL(10,8),
  `longitude` DECIMAL(11,8),
  `technical_task` TEXT,
  `status` ENUM('draft', 'active', 'completed', 'cancelled') DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`organizer_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX `idx_organizer` (`organizer_id`),
  INDEX `idx_date` (`event_date`),
  INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Связь сотрудников и мероприятий
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `event_staff` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `event_id` INT(11) NOT NULL,
  `worker_id` INT(11) NOT NULL,
  `status` ENUM('pending', 'confirmed', 'on_location', 'completed', 'declined') DEFAULT 'pending',
  `assigned_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `confirmed_at` TIMESTAMP NULL,
  `arrived_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`event_id`) REFERENCES `events`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`worker_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `unique_event_worker` (`event_id`, `worker_id`),
  INDEX `idx_event` (`event_id`),
  INDEX `idx_worker` (`worker_id`),
  INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Календарь доступности работников
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `worker_availability` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `worker_id` INT(11) NOT NULL,
  `unavailable_date` DATE NOT NULL,
  `reason` VARCHAR(255),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`worker_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `unique_worker_date` (`worker_id`, `unavailable_date`),
  INDEX `idx_worker` (`worker_id`),
  INDEX `idx_date` (`unavailable_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Чаты
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `chats` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `event_id` INT(11),
  `type` ENUM('event_group', 'personal') NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`event_id`) REFERENCES `events`(`id`) ON DELETE CASCADE,
  INDEX `idx_event` (`event_id`),
  INDEX `idx_type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Участники чатов
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `chat_participants` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `chat_id` INT(11) NOT NULL,
  `user_id` INT(11) NOT NULL,
  `joined_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`chat_id`) REFERENCES `chats`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `unique_chat_user` (`chat_id`, `user_id`),
  INDEX `idx_chat` (`chat_id`),
  INDEX `idx_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Сообщения
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `messages` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `chat_id` INT(11) NOT NULL,
  `user_id` INT(11) NOT NULL,
  `message` TEXT NOT NULL,
  `file_path` VARCHAR(255),
  `file_type` VARCHAR(50),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `is_read` TINYINT(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`chat_id`) REFERENCES `chats`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX `idx_chat` (`chat_id`),
  INDEX `idx_user` (`user_id`),
  INDEX `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Платежи и расходы
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `payments` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `event_id` INT(11) NOT NULL,
  `worker_id` INT(11) NOT NULL,
  `hours_worked` DECIMAL(5,2) DEFAULT 0,
  `hourly_rate` DECIMAL(10,2) DEFAULT 0,
  `work_payment` DECIMAL(10,2) DEFAULT 0,
  `travel_type` ENUM('own_car', 'taxi', 'none') DEFAULT 'none',
  `travel_distance` DECIMAL(10,2),
  `travel_cost` DECIMAL(10,2) DEFAULT 0,
  `travel_receipt` VARCHAR(255),
  `total_amount` DECIMAL(10,2) DEFAULT 0,
  `status` ENUM('pending', 'approved', 'paid') DEFAULT 'pending',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `paid_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`event_id`) REFERENCES `events`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`worker_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX `idx_event` (`event_id`),
  INDEX `idx_worker` (`worker_id`),
  INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Уведомления
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `notifications` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `type` VARCHAR(50) NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `message` TEXT NOT NULL,
  `link` VARCHAR(255),
  `is_read` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX `idx_user` (`user_id`),
  INDEX `idx_read` (`is_read`),
  INDEX `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Журнал действий
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `action_logs` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `action_type` VARCHAR(100) NOT NULL,
  `description` TEXT NOT NULL,
  `entity_type` VARCHAR(50),
  `entity_id` INT(11),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX `idx_user` (`user_id`),
  INDEX `idx_created` (`created_at`),
  INDEX `idx_entity` (`entity_type`, `entity_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Шаблоны мероприятий
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `event_templates` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `organizer_id` INT(11) NOT NULL,
  `template_name` VARCHAR(255) NOT NULL,
  `title` VARCHAR(255),
  `duration_hours` INT(11),
  `location` VARCHAR(255),
  `technical_task` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`organizer_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX `idx_organizer` (`organizer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Медиафайлы мероприятий
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `event_media` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `event_id` INT(11) NOT NULL,
  `file_path` VARCHAR(255) NOT NULL,
  `file_type` VARCHAR(50) NOT NULL,
  `uploaded_by` INT(11) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`event_id`) REFERENCES `events`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`uploaded_by`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX `idx_event` (`event_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Статистика работников
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `worker_statistics` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `worker_id` INT(11) NOT NULL,
  `total_events` INT(11) DEFAULT 0,
  `total_earnings` DECIMAL(10,2) DEFAULT 0,
  `total_hours` DECIMAL(10,2) DEFAULT 0,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`worker_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `unique_worker_stats` (`worker_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Добавление поля для аватара в таблицу users
ALTER TABLE `users` ADD COLUMN `avatar` VARCHAR(255) NULL AFTER `middle_name`;
