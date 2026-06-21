-- ════════════════════════════════════════════════════════════════════════
--  WiseWallet 2.0 — Complete MySQL schema, indexes and seed data
--  Import:  mysql -u root < database/wisewallet.sql      (or via phpMyAdmin)
--
--  Seeded logins (change in production!):
--    • admin@wisewallet.local  /  Admin@WiseWallet2026   (role: admin)
--    • demo@wisewallet.local   /  Demo@WiseWallet2026    (role: user, populated)
--
--  Passwords are Argon2id hashes. Charset utf8mb4, engine InnoDB.
-- ════════════════════════════════════════════════════════════════════════

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
SET time_zone = '+00:00';

CREATE DATABASE IF NOT EXISTS `wisewallet`
  DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `wisewallet`;

-- Drop in dependency order (idempotent re-import).
DROP TABLE IF EXISTS `notifications`;
DROP TABLE IF EXISTS `net_worth_snapshots`;
DROP TABLE IF EXISTS `recurring_rules`;
DROP TABLE IF EXISTS `audit_log`;
DROP TABLE IF EXISTS `login_attempts`;
DROP TABLE IF EXISTS `news_cache`;
DROP TABLE IF EXISTS `articles`;
DROP TABLE IF EXISTS `article_categories`;
DROP TABLE IF EXISTS `user_achievements`;
DROP TABLE IF EXISTS `achievements`;
DROP TABLE IF EXISTS `investments`;
DROP TABLE IF EXISTS `subscriptions`;
DROP TABLE IF EXISTS `bills`;
DROP TABLE IF EXISTS `goal_contributions`;
DROP TABLE IF EXISTS `goals`;
DROP TABLE IF EXISTS `budgets`;
DROP TABLE IF EXISTS `transaction_tags`;
DROP TABLE IF EXISTS `tags`;
DROP TABLE IF EXISTS `transactions`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `accounts`;
DROP TABLE IF EXISTS `settings`;
DROP TABLE IF EXISTS `sessions`;
DROP TABLE IF EXISTS `users`;

-- ───────────────────────────── users ──────────────────────────────────
CREATE TABLE `users` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`          VARCHAR(120) NOT NULL,
  `email`         VARCHAR(190) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `role`          ENUM('user','admin') NOT NULL DEFAULT 'user',
  `currency`      CHAR(3) NOT NULL DEFAULT 'EUR',
  `theme`         ENUM('dark','light') NOT NULL DEFAULT 'dark',
  `privacy_mode`  TINYINT(1) NOT NULL DEFAULT 0,
  `points`        INT NOT NULL DEFAULT 0,
  `is_active`     TINYINT(1) NOT NULL DEFAULT 1,
  `last_login_at` DATETIME NULL,
  `totp_secret`   VARCHAR(64) NULL,
  `totp_enabled`  TINYINT(1) NOT NULL DEFAULT 0,
  `locked_until`  DATETIME NULL,
  `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ───────────────────────────── sessions (optional DB store) ────────────
CREATE TABLE `sessions` (
  `id`            VARCHAR(128) NOT NULL,
  `user_id`       INT UNSIGNED NULL,
  `ip`            VARCHAR(45) NULL,
  `user_agent`    VARCHAR(255) NULL,
  `payload`       MEDIUMTEXT NULL,
  `last_activity` INT NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_sessions_user` (`user_id`),
  KEY `idx_sessions_activity` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ───────────────────────────── settings (global, admin-editable) ───────
CREATE TABLE `settings` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `key`        VARCHAR(100) NOT NULL,
  `value`      TEXT NULL,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_settings_key` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ───────────────────────────── accounts ───────────────────────────────
CREATE TABLE `accounts` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`     INT UNSIGNED NOT NULL,
  `name`        VARCHAR(120) NOT NULL,
  `type`        ENUM('checking','savings','credit','cash','crypto','investment') NOT NULL DEFAULT 'checking',
  `balance`     DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `currency`    CHAR(3) NOT NULL DEFAULT 'EUR',
  `color`       VARCHAR(20) NULL,
  `is_archived` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_accounts_user` (`user_id`),
  CONSTRAINT `fk_accounts_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ───────────────────────────── categories ─────────────────────────────
CREATE TABLE `categories` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`    INT UNSIGNED NULL,            -- NULL = system category (admin-managed)
  `name`       VARCHAR(80) NOT NULL,
  `type`       ENUM('income','expense','transfer') NOT NULL,
  `icon`       VARCHAR(40) NOT NULL DEFAULT 'tag',
  `color`      VARCHAR(20) NOT NULL DEFAULT '#64748b',
  `is_system`  TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_categories_user_type` (`user_id`,`type`),
  CONSTRAINT `fk_categories_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ───────────────────────────── transactions ───────────────────────────
CREATE TABLE `transactions` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`       INT UNSIGNED NOT NULL,
  `account_id`    INT UNSIGNED NOT NULL,
  `to_account_id` INT UNSIGNED NULL,         -- destination for transfers
  `category_id`   INT UNSIGNED NULL,
  `type`          ENUM('income','expense','transfer') NOT NULL,
  `amount`        DECIMAL(15,2) NOT NULL,
  `description`   VARCHAR(255) NOT NULL DEFAULT '',
  `notes`         TEXT NULL,
  `occurred_on`   DATE NOT NULL,
  `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tx_user` (`user_id`),
  KEY `idx_tx_account` (`account_id`),
  KEY `idx_tx_category` (`category_id`),
  KEY `idx_tx_date` (`user_id`,`occurred_on`),
  CONSTRAINT `fk_tx_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_tx_account` FOREIGN KEY (`account_id`) REFERENCES `accounts`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_tx_to_account` FOREIGN KEY (`to_account_id`) REFERENCES `accounts`(`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_tx_category` FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ───────────────────────────── tags ───────────────────────────────────
CREATE TABLE `tags` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`    INT UNSIGNED NOT NULL,
  `name`       VARCHAR(50) NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_tags_user_name` (`user_id`,`name`),
  CONSTRAINT `fk_tags_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `transaction_tags` (
  `transaction_id` INT UNSIGNED NOT NULL,
  `tag_id`         INT UNSIGNED NOT NULL,
  PRIMARY KEY (`transaction_id`,`tag_id`),
  CONSTRAINT `fk_tt_tx` FOREIGN KEY (`transaction_id`) REFERENCES `transactions`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_tt_tag` FOREIGN KEY (`tag_id`) REFERENCES `tags`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ───────────────────────────── budgets ────────────────────────────────
CREATE TABLE `budgets` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`     INT UNSIGNED NOT NULL,
  `category_id` INT UNSIGNED NOT NULL,
  `amount`      DECIMAL(15,2) NOT NULL,
  `period`      ENUM('weekly','monthly','yearly') NOT NULL DEFAULT 'monthly',
  `start_date`  DATE NOT NULL,
  `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_budgets_user` (`user_id`),
  CONSTRAINT `fk_budgets_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_budgets_category` FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ───────────────────────────── goals ──────────────────────────────────
CREATE TABLE `goals` (
  `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`        INT UNSIGNED NOT NULL,
  `name`           VARCHAR(150) NOT NULL,
  `target_amount`  DECIMAL(15,2) NOT NULL,
  `current_amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `deadline`       DATE NULL,
  `color`          VARCHAR(20) NOT NULL DEFAULT '#6366f1',
  `icon`           VARCHAR(40) NOT NULL DEFAULT 'target',
  `notes`          TEXT NULL,
  `status`         ENUM('active','completed','archived') NOT NULL DEFAULT 'active',
  `created_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_goals_user` (`user_id`),
  CONSTRAINT `fk_goals_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `goal_contributions` (
  `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `goal_id`        INT UNSIGNED NOT NULL,
  `user_id`        INT UNSIGNED NOT NULL,
  `amount`         DECIMAL(15,2) NOT NULL,
  `note`           VARCHAR(255) NULL,
  `contributed_on` DATE NOT NULL,
  `created_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_gc_goal` (`goal_id`),
  CONSTRAINT `fk_gc_goal` FOREIGN KEY (`goal_id`) REFERENCES `goals`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_gc_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ───────────────────────────── bills ──────────────────────────────────
CREATE TABLE `bills` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`     INT UNSIGNED NOT NULL,
  `name`        VARCHAR(150) NOT NULL,
  `amount`      DECIMAL(15,2) NOT NULL,
  `due_date`    DATE NOT NULL,
  `recurrence`  ENUM('once','weekly','monthly','quarterly','yearly') NOT NULL DEFAULT 'monthly',
  `category_id` INT UNSIGNED NULL,
  `account_id`  INT UNSIGNED NULL,
  `status`      ENUM('pending','paid','overdue') NOT NULL DEFAULT 'pending',
  `paid_at`     DATETIME NULL,
  `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_bills_user` (`user_id`),
  CONSTRAINT `fk_bills_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ───────────────────────────── subscriptions ──────────────────────────
CREATE TABLE `subscriptions` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`       INT UNSIGNED NOT NULL,
  `name`          VARCHAR(150) NOT NULL,
  `amount`        DECIMAL(15,2) NOT NULL,
  `billing_cycle` ENUM('monthly','yearly') NOT NULL DEFAULT 'monthly',
  `next_renewal`  DATE NULL,
  `category_id`   INT UNSIGNED NULL,
  `is_active`     TINYINT(1) NOT NULL DEFAULT 1,
  `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_subs_user` (`user_id`),
  CONSTRAINT `fk_subs_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ───────────────────────────── investments ────────────────────────────
CREATE TABLE `investments` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`       INT UNSIGNED NOT NULL,
  `name`          VARCHAR(150) NOT NULL,
  `symbol`        VARCHAR(30) NULL,
  `type`          ENUM('stock','etf','crypto','bond','real_estate','retirement') NOT NULL DEFAULT 'stock',
  `quantity`      DECIMAL(18,8) NOT NULL DEFAULT 0,
  `buy_price`     DECIMAL(18,8) NOT NULL DEFAULT 0,
  `current_price` DECIMAL(18,8) NULL,
  `currency`      CHAR(3) NOT NULL DEFAULT 'EUR',
  `purchased_on`  DATE NULL,
  `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_inv_user` (`user_id`),
  CONSTRAINT `fk_inv_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ───────────────────────────── achievements ───────────────────────────
CREATE TABLE `achievements` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `code`        VARCHAR(50) NOT NULL,
  `name`        VARCHAR(120) NOT NULL,
  `description` VARCHAR(255) NOT NULL,
  `icon`        VARCHAR(40) NOT NULL DEFAULT 'award',
  `rarity`      ENUM('common','rare','epic','legendary') NOT NULL DEFAULT 'common',
  `points`      INT NOT NULL DEFAULT 10,
  `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_ach_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `user_achievements` (
  `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`        INT UNSIGNED NOT NULL,
  `achievement_id` INT UNSIGNED NOT NULL,
  `unlocked_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_ua` (`user_id`,`achievement_id`),
  CONSTRAINT `fk_ua_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ua_ach` FOREIGN KEY (`achievement_id`) REFERENCES `achievements`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ───────────────────────────── blog ───────────────────────────────────
CREATE TABLE `article_categories` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`       VARCHAR(80) NOT NULL,
  `slug`       VARCHAR(100) NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_artcat_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `articles` (
  `id`               INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `author_id`        INT UNSIGNED NULL,
  `category_id`      INT UNSIGNED NULL,
  `slug`             VARCHAR(190) NOT NULL,
  `title`            VARCHAR(200) NOT NULL,
  `excerpt`          VARCHAR(300) NOT NULL DEFAULT '',
  `body`             MEDIUMTEXT NOT NULL,
  `cover_image`      VARCHAR(255) NULL,
  `status`           ENUM('draft','published') NOT NULL DEFAULT 'published',
  `reading_minutes`  INT NOT NULL DEFAULT 5,
  `views`            INT NOT NULL DEFAULT 0,
  `published_at`     DATETIME NULL,
  `created_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_articles_slug` (`slug`),
  KEY `idx_articles_status` (`status`,`published_at`),
  CONSTRAINT `fk_articles_author` FOREIGN KEY (`author_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_articles_cat` FOREIGN KEY (`category_id`) REFERENCES `article_categories`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ───────────────────────────── news cache ─────────────────────────────
CREATE TABLE `news_cache` (
  `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `source`       VARCHAR(100) NOT NULL,
  `title`        VARCHAR(300) NOT NULL,
  `url`          VARCHAR(500) NOT NULL,
  `summary`      TEXT NULL,
  `published_at` DATETIME NULL,
  `fetched_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_news_url` (`url`(255)),
  KEY `idx_news_pub` (`published_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ───────────────────────────── security: login attempts & audit ───────
CREATE TABLE `login_attempts` (
  `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `identifier`   VARCHAR(190) NOT NULL,
  `ip`           VARCHAR(45) NOT NULL,
  `success`      TINYINT(1) NOT NULL DEFAULT 0,
  `attempted_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_la_identifier` (`identifier`,`attempted_at`),
  KEY `idx_la_ip` (`ip`,`attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `audit_log` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`    INT UNSIGNED NULL,
  `action`     VARCHAR(80) NOT NULL,
  `ip`         VARCHAR(45) NULL,
  `user_agent` VARCHAR(255) NULL,
  `meta`       TEXT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_audit_user` (`user_id`),
  KEY `idx_audit_action` (`action`),
  KEY `idx_audit_time` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ───────────────────────────── recurring rules ────────────────────────
CREATE TABLE `recurring_rules` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`     INT UNSIGNED NOT NULL,
  `type`        ENUM('income','expense','transfer') NOT NULL,
  `account_id`  INT UNSIGNED NOT NULL,
  `category_id` INT UNSIGNED NULL,
  `amount`      DECIMAL(15,2) NOT NULL,
  `description` VARCHAR(255) NOT NULL DEFAULT '',
  `frequency`   ENUM('daily','weekly','monthly','yearly') NOT NULL DEFAULT 'monthly',
  `next_run`    DATE NOT NULL,
  `is_active`   TINYINT(1) NOT NULL DEFAULT 1,
  `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_rr_user` (`user_id`),
  CONSTRAINT `fk_rr_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ───────────────────────────── net worth snapshots ────────────────────
CREATE TABLE `net_worth_snapshots` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`     INT UNSIGNED NOT NULL,
  `net_worth`   DECIMAL(15,2) NOT NULL,
  `assets`      DECIMAL(15,2) NOT NULL DEFAULT 0,
  `liabilities` DECIMAL(15,2) NOT NULL DEFAULT 0,
  `captured_on` DATE NOT NULL,
  `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_nws` (`user_id`,`captured_on`),
  CONSTRAINT `fk_nws_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ───────────────────────────── notifications ──────────────────────────
CREATE TABLE `notifications` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`    INT UNSIGNED NOT NULL,
  `type`       VARCHAR(40) NOT NULL DEFAULT 'info',
  `title`      VARCHAR(150) NOT NULL,
  `body`       VARCHAR(300) NULL,
  `icon`       VARCHAR(40) NULL,
  `is_read`    TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_notif_user` (`user_id`,`is_read`),
  CONSTRAINT `fk_notif_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ───────────────────────────── security: rate limiting & resets ───────
CREATE TABLE `rate_limits` (
  `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `rl_key`       VARCHAR(190) NOT NULL,
  `hits`         INT NOT NULL DEFAULT 0,
  `window_start` INT NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_rl_key` (`rl_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `password_resets` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`    INT UNSIGNED NOT NULL,
  `token_hash` CHAR(64) NOT NULL,
  `expires_at` DATETIME NOT NULL,
  `used`       TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_pr_token` (`token_hash`),
  KEY `idx_pr_user` (`user_id`),
  CONSTRAINT `fk_pr_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `api_keys` (
  `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`      INT UNSIGNED NOT NULL,
  `name`         VARCHAR(80) NOT NULL,
  `key_hash`     CHAR(64) NOT NULL,
  `last_used_at` DATETIME NULL,
  `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_apikey` (`key_hash`),
  CONSTRAINT `fk_apikey_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `security_events` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `kind`       VARCHAR(40) NOT NULL,
  `ip`         VARCHAR(45) NULL,
  `uri`        VARCHAR(255) NULL,
  `detail`     VARCHAR(255) NULL,
  `user_id`    INT UNSIGNED NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_se_kind` (`kind`),
  KEY `idx_se_time` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ════════════════════════════════════════════════════════════════════════
--  SEED DATA
-- ════════════════════════════════════════════════════════════════════════

-- Users (Argon2id hashes; passwords documented in the header).
INSERT INTO `users` (`id`,`name`,`email`,`password_hash`,`role`,`currency`,`theme`,`points`,`is_active`,`created_at`) VALUES
(1,'Administrador','admin@wisewallet.local','$argon2id$v=19$m=65536,t=4,p=2$bzAyL2ZBZUo4dC5ERUlBcA$bSyqYugiPL8K8xMHXANlI+r1/wZ7lRgOq3FjL2lgfIs','admin','EUR','dark',0,1, NOW() - INTERVAL 120 DAY),
(2,'Eduardo Demo','demo@wisewallet.local','$argon2id$v=19$m=65536,t=4,p=2$ZUxnV2xuaDluUm1sT0xwMg$TPK3+Xv83GDwldp/wiEXto0mjOHaBK+9GuLTk6MiKbE','user','EUR','dark',85,1, NOW() - INTERVAL 90 DAY);

-- Global settings (admin-editable).
INSERT INTO `settings` (`key`,`value`) VALUES
('site_name','WiseWallet'),
('site_tagline','Domina o teu dinheiro — do euro de hoje à reforma de amanhã.'),
('footer_text','© WiseWallet 2.0 — Plataforma de finanças pessoais.'),
('allow_registration','1'),
('maintenance_mode','0'),
('default_currency','EUR');

-- System categories (user_id NULL, admin-managed).
INSERT INTO `categories` (`id`,`user_id`,`name`,`type`,`icon`,`color`,`is_system`) VALUES
(1,NULL,'Salário','income','wallet','#22c55e',1),
(2,NULL,'Freelance','income','briefcase','#16a34a',1),
(3,NULL,'Investimentos','income','trending-up','#10b981',1),
(4,NULL,'Presentes','income','gift','#34d399',1),
(5,NULL,'Outros Rendimentos','income','plus-circle','#4ade80',1),
(6,NULL,'Alimentação','expense','shopping-cart','#f97316',1),
(7,NULL,'Restaurantes','expense','utensils','#fb923c',1),
(8,NULL,'Transporte','expense','car','#3b82f6',1),
(9,NULL,'Habitação','expense','home','#8b5cf6',1),
(10,NULL,'Saúde','expense','heart-pulse','#ef4444',1),
(11,NULL,'Lazer','expense','gamepad-2','#ec4899',1),
(12,NULL,'Educação','expense','graduation-cap','#06b6d4',1),
(13,NULL,'Compras','expense','shopping-bag','#a855f7',1),
(14,NULL,'Serviços','expense','wrench','#64748b',1),
(15,NULL,'Subscrições','expense','repeat','#f43f5e',1),
(16,NULL,'Viagens','expense','plane','#0ea5e9',1),
(17,NULL,'Impostos','expense','landmark','#71717a',1),
(18,NULL,'Outros','expense','more-horizontal','#94a3b8',1);

-- Achievements (17, with rarity + points).
INSERT INTO `achievements` (`id`,`code`,`name`,`description`,`icon`,`rarity`,`points`) VALUES
(1,'first_transaction','Primeiro Passo','Registaste a tua primeira transação.','footprints','common',10),
(2,'ten_transactions','A Aquecer','Registaste 10 transações.','activity','common',20),
(3,'hundred_transactions','Centurião','Registaste 100 transações.','flame','rare',50),
(4,'first_account','Conta Aberta','Criaste a tua primeira conta.','landmark','common',10),
(5,'first_budget','Planeador','Criaste o teu primeiro orçamento.','clipboard-list','common',15),
(6,'budget_keeper','Disciplinado','Ficaste dentro de todos os orçamentos num mês.','shield-check','rare',40),
(7,'first_goal','Sonhador','Criaste o teu primeiro objetivo.','target','common',15),
(8,'goal_achieved','Conquistador','Completaste um objetivo.','trophy','epic',80),
(9,'saver_25','Poupador','Atingiste 25% de taxa de poupança.','piggy-bank','rare',30),
(10,'emergency_fund','Rede de Segurança','Criaste um fundo de emergência completo.','umbrella','epic',70),
(11,'first_investment','Investidor','Registaste o teu primeiro investimento.','line-chart','rare',30),
(12,'diversified','Diversificado','Carteira com 4+ tipos de ativos.','layers','epic',60),
(13,'net_worth_10k','Cinco Dígitos','Património líquido acima de 10.000 €.','gem','epic',90),
(14,'score_90','Mestre Financeiro','Atingiste um Score de Saúde 90+.','crown','legendary',150),
(15,'first_subscription','Caçador de Vampiros','Registaste a tua primeira subscrição.','ghost','common',10),
(16,'debt_free','Livre de Dívidas','Pagaste todas as faturas pendentes.','badge-check','rare',40),
(17,'streak_30','Imparável','30 dias seguidos a registar.','calendar-check','legendary',120);

-- Blog categories.
INSERT INTO `article_categories` (`id`,`name`,`slug`) VALUES
(1,'Orçamento','orcamento'),
(2,'Poupança','poupanca'),
(3,'Investimento','investimento'),
(4,'Crédito','credito'),
(5,'Educação Financeira','educacao-financeira');

-- Demo blog articles (authored by admin).
INSERT INTO `articles` (`id`,`author_id`,`category_id`,`slug`,`title`,`excerpt`,`body`,`status`,`reading_minutes`,`views`,`published_at`) VALUES
(1,1,1,'regra-50-30-20','A Regra 50/30/20 explicada','O método mais simples para organizar o teu salário sem folhas de cálculo complicadas.',
'<p>A regra <strong>50/30/20</strong> divide o teu rendimento líquido em três fatias:</p><ul><li><strong>50% Necessidades</strong> — renda, alimentação, transportes, contas essenciais.</li><li><strong>30% Desejos</strong> — restaurantes, lazer, subscrições, viagens.</li><li><strong>20% Poupança e dívidas</strong> — fundo de emergência, investimentos, amortizações.</li></ul><p>É um ponto de partida, não uma lei. Ajusta as percentagens à tua realidade, mas mantém o princípio: <em>paga-te primeiro a ti</em>. Usa os orçamentos da WiseWallet para automatizar estes limites e receber alertas quando te aproximas deles.</p>','published',4,128, NOW() - INTERVAL 30 DAY),
(2,1,3,'poder-juros-compostos','O poder dos juros compostos','Porque é que começar a investir cedo vale mais do que investir muito.',
'<p>Os juros compostos são juros que rendem sobre os juros já acumulados. A fórmula é simples:</p><p><code>VF = C × (1 + i)^n</code></p><p>Onde <code>C</code> é o capital, <code>i</code> a taxa por período e <code>n</code> o número de períodos. O fator decisivo é o <strong>tempo</strong>: 100 € investidos a 7% ao ano tornam-se ~761 € em 30 anos, sem qualquer esforço adicional. Experimenta o <a href="/simulators.php">simulador de poupança</a> para veres o teu próprio caso.</p>','published',5,206, NOW() - INTERVAL 21 DAY),
(3,1,2,'fundo-de-emergencia','Como criar um fundo de emergência','Quanto guardar, onde guardar e em quanto tempo lá chegas.',
'<p>Um fundo de emergência cobre <strong>3 a 6 meses</strong> das tuas despesas essenciais. Serve para imprevistos — desemprego, avarias, saúde — sem teres de recorrer a crédito caro.</p><p>Onde guardar? Numa conta poupança ou depósito de acesso fácil, separada da conta do dia a dia. Define um objetivo na WiseWallet e contribui mensalmente: o sistema cria marcos automáticos aos 25%, 50% e 75%.</p>','published',4,154, NOW() - INTERVAL 12 DAY),
(4,1,5,'entender-irs-portugal','Entender o IRS em Portugal','Escalões, taxas marginais e por que razão um aumento nunca reduz o teu líquido.',
'<p>O IRS português é <strong>progressivo por escalões</strong>: cada fatia do rendimento é tributada à sua taxa. Subir de escalão só afeta a parte acima do limite — nunca todo o rendimento. Usa o <a href="/simulators.php">simulador de IRS</a> para estimar o teu líquido com base nos escalões atuais.</p>','published',6,98, NOW() - INTERVAL 5 DAY);

-- ── Demo user (id 2) populated data ──────────────────────────────────────
INSERT INTO `accounts` (`id`,`user_id`,`name`,`type`,`balance`,`currency`,`color`) VALUES
(1,2,'Conta à Ordem','checking',2450.75,'EUR','#6366f1'),
(2,2,'Poupança','savings',8200.00,'EUR','#22c55e'),
(3,2,'Cartão de Crédito','credit',-340.20,'EUR','#ef4444'),
(4,2,'Dinheiro','cash',85.00,'EUR','#f59e0b'),
(5,2,'Carteira Cripto','crypto',1320.50,'EUR','#f97316');

INSERT INTO `transactions` (`user_id`,`account_id`,`to_account_id`,`category_id`,`type`,`amount`,`description`,`notes`,`occurred_on`) VALUES
-- current month
(2,1,NULL,1,'income',1850.00,'Salário mensal',NULL, CURDATE() - INTERVAL 28 DAY),
(2,1,NULL,9,'expense',650.00,'Renda do apartamento',NULL, CURDATE() - INTERVAL 25 DAY),
(2,1,2,NULL,'transfer',200.00,'Poupança automática','Transferência mensal', CURDATE() - INTERVAL 25 DAY),
(2,1,NULL,6,'expense',78.45,'Supermercado Continente',NULL, CURDATE() - INTERVAL 24 DAY),
(2,4,NULL,7,'expense',32.00,'Almoço com colegas','Jantar de equipa', CURDATE() - INTERVAL 23 DAY),
(2,1,NULL,8,'expense',40.00,'Passe mensal de transportes',NULL, CURDATE() - INTERVAL 22 DAY),
(2,1,NULL,15,'expense',13.99,'Netflix',NULL, CURDATE() - INTERVAL 20 DAY),
(2,1,NULL,15,'expense',6.99,'Spotify',NULL, CURDATE() - INTERVAL 20 DAY),
(2,1,NULL,11,'expense',45.00,'Cinema e jantar','Estreia do filme', CURDATE() - INTERVAL 15 DAY),
(2,1,NULL,2,'income',320.00,'Projeto web freelance',NULL, CURDATE() - INTERVAL 12 DAY),
(2,1,NULL,6,'expense',64.20,'Supermercado Pingo Doce',NULL, CURDATE() - INTERVAL 10 DAY),
(2,4,NULL,7,'expense',28.50,'Sushi',NULL, CURDATE() - INTERVAL 8 DAY),
(2,1,NULL,10,'expense',35.00,'Farmácia',NULL, CURDATE() - INTERVAL 6 DAY),
(2,3,NULL,13,'expense',89.90,'Ténis novos',NULL, CURDATE() - INTERVAL 4 DAY),
(2,2,NULL,3,'income',52.30,'Dividendos ETF',NULL, CURDATE() - INTERVAL 3 DAY),
(2,1,NULL,6,'expense',71.10,'Supermercado',NULL, CURDATE() - INTERVAL 2 DAY),
-- previous month
(2,1,NULL,1,'income',1850.00,'Salário mensal',NULL, CURDATE() - INTERVAL 58 DAY),
(2,1,NULL,9,'expense',650.00,'Renda do apartamento',NULL, CURDATE() - INTERVAL 55 DAY),
(2,1,NULL,6,'expense',286.40,'Compras do mês',NULL, CURDATE() - INTERVAL 50 DAY),
(2,1,NULL,8,'expense',40.00,'Passe mensal de transportes',NULL, CURDATE() - INTERVAL 50 DAY),
(2,1,NULL,11,'expense',120.00,'Concerto',NULL, CURDATE() - INTERVAL 45 DAY),
-- two months ago
(2,1,NULL,1,'income',1850.00,'Salário mensal',NULL, CURDATE() - INTERVAL 89 DAY),
(2,1,NULL,9,'expense',650.00,'Renda do apartamento',NULL, CURDATE() - INTERVAL 86 DAY),
(2,1,NULL,16,'expense',410.00,'Fim de semana no Porto',NULL, CURDATE() - INTERVAL 80 DAY);

INSERT INTO `budgets` (`user_id`,`category_id`,`amount`,`period`,`start_date`) VALUES
(2,6,400.00,'monthly', DATE_FORMAT(CURDATE(),'%Y-%m-01')),
(2,7,150.00,'monthly', DATE_FORMAT(CURDATE(),'%Y-%m-01')),
(2,8,100.00,'monthly', DATE_FORMAT(CURDATE(),'%Y-%m-01')),
(2,11,120.00,'monthly', DATE_FORMAT(CURDATE(),'%Y-%m-01')),
(2,15,40.00,'monthly', DATE_FORMAT(CURDATE(),'%Y-%m-01'));

INSERT INTO `goals` (`id`,`user_id`,`name`,`target_amount`,`current_amount`,`deadline`,`color`,`icon`,`status`) VALUES
(1,2,'Fundo de Emergência',6000.00,3500.00, CURDATE() + INTERVAL 6 MONTH,'#22c55e','umbrella','active'),
(2,2,'Férias 2026',2500.00,900.00, CURDATE() + INTERVAL 4 MONTH,'#0ea5e9','plane','active'),
(3,2,'Portátil novo',1400.00,1400.00, CURDATE() - INTERVAL 10 DAY,'#a855f7','laptop','completed');

INSERT INTO `goal_contributions` (`goal_id`,`user_id`,`amount`,`note`,`contributed_on`) VALUES
(1,2,500.00,'Poupança inicial', CURDATE() - INTERVAL 80 DAY),
(1,2,1500.00,'Bónus de trabalho', CURDATE() - INTERVAL 50 DAY),
(1,2,1500.00,'Esforço mensal', CURDATE() - INTERVAL 20 DAY),
(2,2,400.00,'Início', CURDATE() - INTERVAL 40 DAY),
(2,2,500.00,'Mensal', CURDATE() - INTERVAL 10 DAY);

INSERT INTO `bills` (`user_id`,`name`,`amount`,`due_date`,`recurrence`,`category_id`,`account_id`,`status`) VALUES
(2,'Renda',650.00, CURDATE() + INTERVAL 5 DAY,'monthly',9,1,'pending'),
(2,'Eletricidade EDP',64.30, CURDATE() + INTERVAL 12 DAY,'monthly',14,1,'pending'),
(2,'Internet MEO',39.99, CURDATE() + INTERVAL 9 DAY,'monthly',14,1,'pending'),
(2,'Seguro automóvel',45.00, CURDATE() - INTERVAL 3 DAY,'monthly',14,1,'overdue'),
(2,'Água',22.10, CURDATE() - INTERVAL 20 DAY,'monthly',14,1,'paid');

INSERT INTO `subscriptions` (`user_id`,`name`,`amount`,`billing_cycle`,`next_renewal`,`category_id`,`is_active`) VALUES
(2,'Netflix',13.99,'monthly', CURDATE() + INTERVAL 10 DAY,15,1),
(2,'Spotify',6.99,'monthly', CURDATE() + INTERVAL 6 DAY,15,1),
(2,'Ginásio Fitness Hut',29.99,'monthly', CURDATE() + INTERVAL 2 DAY,11,1),
(2,'Amazon Prime',49.90,'yearly', CURDATE() + INTERVAL 120 DAY,13,1),
(2,'iCloud+',2.99,'monthly', CURDATE() + INTERVAL 18 DAY,14,1);

INSERT INTO `investments` (`user_id`,`name`,`symbol`,`type`,`quantity`,`buy_price`,`current_price`,`currency`,`purchased_on`) VALUES
(2,'Vanguard FTSE All-World','VWCE','etf',12.00000000,98.50000000,112.30000000,'EUR', CURDATE() - INTERVAL 200 DAY),
(2,'Apple Inc.','AAPL','stock',5.00000000,165.00000000,189.20000000,'EUR', CURDATE() - INTERVAL 150 DAY),
(2,'Bitcoin','BTC','crypto',0.02500000,38000.00000000,42500.00000000,'EUR', CURDATE() - INTERVAL 120 DAY),
(2,'Obrigações do Tesouro','OTRT','bond',10.00000000,100.00000000,101.50000000,'EUR', CURDATE() - INTERVAL 90 DAY),
(2,'PPR Garantido','PPR','retirement',1.00000000,3000.00000000,3145.00000000,'EUR', CURDATE() - INTERVAL 300 DAY);

INSERT INTO `user_achievements` (`user_id`,`achievement_id`,`unlocked_at`) VALUES
(2,1, NOW() - INTERVAL 85 DAY),
(2,2, NOW() - INTERVAL 80 DAY),
(2,4, NOW() - INTERVAL 88 DAY),
(2,5, NOW() - INTERVAL 70 DAY),
(2,7, NOW() - INTERVAL 75 DAY),
(2,8, NOW() - INTERVAL 10 DAY),
(2,11, NOW() - INTERVAL 60 DAY),
(2,15, NOW() - INTERVAL 55 DAY);

INSERT INTO `net_worth_snapshots` (`user_id`,`net_worth`,`assets`,`liabilities`,`captured_on`) VALUES
(2, 9800.00, 10200.00, 400.00, CURDATE() - INTERVAL 150 DAY),
(2,10650.00, 11050.00, 400.00, CURDATE() - INTERVAL 120 DAY),
(2,11420.00, 11800.00, 380.00, CURDATE() - INTERVAL 90 DAY),
(2,12300.00, 12700.00, 400.00, CURDATE() - INTERVAL 60 DAY),
(2,13180.00, 13540.00, 360.00, CURDATE() - INTERVAL 30 DAY),
(2,14250.00, 14590.00, 340.00, CURDATE());

INSERT INTO `notifications` (`user_id`,`type`,`title`,`body`,`icon`,`is_read`) VALUES
(2,'achievement','Conquista desbloqueada!','Ganhaste "Conquistador" por completar um objetivo.','trophy',0),
(2,'budget','Orçamento em alerta','Já usaste 78% do orçamento de Alimentação este mês.','alert-triangle',0),
(2,'bill','Fatura a vencer','A tua renda vence dentro de 5 dias.','calendar',1);

-- ════════════════════════════════════════════════════════════════════════
--  End of WiseWallet 2.0 schema + seed
-- ════════════════════════════════════════════════════════════════════════
