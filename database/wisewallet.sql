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
DROP TABLE IF EXISTS `security_events`;
DROP TABLE IF EXISTS `api_keys`;
DROP TABLE IF EXISTS `password_resets`;
DROP TABLE IF EXISTS `rate_limits`;
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
  `theme`         ENUM('dark','light') NOT NULL DEFAULT 'light',
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
(1,'Administrator','admin@wisewallet.local','$argon2id$v=19$m=65536,t=4,p=2$bzAyL2ZBZUo4dC5ERUlBcA$bSyqYugiPL8K8xMHXANlI+r1/wZ7lRgOq3FjL2lgfIs','admin','EUR','light',0,1, NOW() - INTERVAL 120 DAY),
(2,'Eduardo Demo','demo@wisewallet.local','$argon2id$v=19$m=65536,t=4,p=2$ZUxnV2xuaDluUm1sT0xwMg$TPK3+Xv83GDwldp/wiEXto0mjOHaBK+9GuLTk6MiKbE','user','EUR','light',85,1, NOW() - INTERVAL 90 DAY);

-- Global settings (admin-editable).
INSERT INTO `settings` (`key`,`value`) VALUES
('site_name','WiseWallet'),
('site_tagline','A clear ledger for your whole financial life.'),
('footer_text','© WiseWallet 2.0 — personal finance platform.'),
('allow_registration','1'),
('maintenance_mode','0'),
('default_currency','EUR');

-- System categories (user_id NULL, admin-managed).
INSERT INTO `categories` (`id`,`user_id`,`name`,`type`,`icon`,`color`,`is_system`) VALUES
(1,NULL,'Salary','income','wallet','#2f6f4f',1),
(2,NULL,'Freelance','income','briefcase','#3f7d5a',1),
(3,NULL,'Investments','income','trending-up','#4f8e6c',1),
(4,NULL,'Gifts','income','gift','#5fa07c',1),
(5,NULL,'Other income','income','plus-circle','#6fae8a',1),
(6,NULL,'Groceries','expense','shopping-cart','#9e6b2a',1),
(7,NULL,'Dining','expense','utensils','#b07d38',1),
(8,NULL,'Transport','expense','car','#2f6f7f',1),
(9,NULL,'Housing','expense','home','#6b4a8a',1),
(10,NULL,'Health','expense','heart-pulse','#a53a2a',1),
(11,NULL,'Leisure','expense','gamepad-2','#b5852a',1),
(12,NULL,'Education','expense','graduation-cap','#3f6b5a',1),
(13,NULL,'Shopping','expense','shopping-bag','#7c5a8a',1),
(14,NULL,'Services','expense','wrench','#7c6f64',1),
(15,NULL,'Subscriptions','expense','repeat','#a5523f',1),
(16,NULL,'Travel','expense','plane','#2f7f7a',1),
(17,NULL,'Taxes','expense','landmark','#6b6f4a',1),
(18,NULL,'Other','expense','more-horizontal','#8a8378',1);

-- Achievements (17, with rarity + points).
INSERT INTO `achievements` (`id`,`code`,`name`,`description`,`icon`,`rarity`,`points`) VALUES
(1,'first_transaction','First Step','You recorded your first transaction.','footprints','common',10),
(2,'ten_transactions','Warming Up','You recorded 10 transactions.','activity','common',20),
(3,'hundred_transactions','Centurion','You recorded 100 transactions.','flame','rare',50),
(4,'first_account','Account Opened','You created your first account.','landmark','common',10),
(5,'first_budget','Planner','You created your first budget.','clipboard-list','common',15),
(6,'budget_keeper','Disciplined','You stayed within every budget for a month.','shield-check','rare',40),
(7,'first_goal','Dreamer','You created your first goal.','target','common',15),
(8,'goal_achieved','Achiever','You completed a goal.','trophy','epic',80),
(9,'saver_25','Saver','You reached a 25% savings rate.','piggy-bank','rare',30),
(10,'emergency_fund','Safety Net','You built a full emergency fund.','umbrella','epic',70),
(11,'first_investment','Investor','You recorded your first investment.','line-chart','rare',30),
(12,'diversified','Diversified','A portfolio with 4+ asset types.','layers','epic',60),
(13,'net_worth_10k','Five Figures','Net worth above 10,000 EUR.','gem','epic',90),
(14,'score_90','Money Master','You reached a Health Score of 90+.','crown','legendary',150),
(15,'first_subscription','Vampire Hunter','You recorded your first subscription.','ghost','common',10),
(16,'debt_free','Debt-Free','You cleared all pending bills.','badge-check','rare',40),
(17,'streak_30','Unstoppable','30 days of logging in a row.','calendar-check','legendary',120);


-- Additional achievements (more milestones to unlock).
INSERT INTO `achievements` (`id`,`code`,`name`,`description`,`icon`,`rarity`,`points`) VALUES
(18,'multi_account','Multi-Banker','Track three or more accounts at once.','landmark','common',15),
(19,'big_saver','High Roller','Net worth above 50,000 EUR.','gem','legendary',180),
(20,'goal_master','Goal Master','Completed three goals.','trophy','epic',100),
(21,'bill_payer','On Top of It','Paid ten bills on time.','badge-check','rare',45),
(22,'security_pro','Security Pro','Enabled two-factor authentication.','shield-check','epic',70),
(23,'subscription_trimmed','Vampire Slayer','Paused at least one subscription.','ghost','rare',25),
(24,'exporter','Record Keeper','Exported your data at least once.','newspaper','common',15),
(25,'investor_5','Portfolio Builder','Tracked five or more investments.','line-chart','epic',65);

-- Blog categories.
INSERT INTO `article_categories` (`id`,`name`,`slug`) VALUES
(1,'Budgeting','orcamento'),
(2,'Saving','poupanca'),
(3,'Investing','investimento'),
(4,'Credit','credito'),
(5,'Financial education','educacao-financeira');

-- Demo blog articles (authored by admin).
INSERT INTO `articles` (`id`,`author_id`,`category_id`,`slug`,`title`,`excerpt`,`body`,`status`,`reading_minutes`,`views`,`published_at`) VALUES
(1,1,1,'regra-50-30-20','The 50/30/20 rule, explained','The simplest way to organise your salary without complicated spreadsheets.',
'<p>The <strong>50/30/20 rule</strong> splits your take-home pay into three parts:</p><ul><li><strong>50% Needs</strong> — rent, groceries, transport, essential bills.</li><li><strong>30% Wants</strong> — dining out, leisure, subscriptions, travel.</li><li><strong>20% Saving &amp; debt</strong> — emergency fund, investments, repayments.</li></ul><p>It is a starting point, not a law. Adjust the percentages to your reality, but keep the principle: <em>pay yourself first</em>. Use WiseWallet budgets to automate these limits and get a quiet warning as you approach them.</p>','published',4,128, NOW() - INTERVAL 30 DAY),
(2,1,3,'poder-juros-compostos','The power of compound interest','Why starting to invest early beats investing a lot.',
'<p>Compound interest is interest that earns interest on the interest already accumulated. The formula is simple:</p><p><code>FV = C × (1 + i)^n</code></p><p>where <code>C</code> is the capital, <code>i</code> the rate per period and <code>n</code> the number of periods. The decisive factor is <strong>time</strong>: 100 € invested at 7% a year becomes about 761 € in 30 years, with no extra effort. Try the <a href="/simulators.php">savings simulator</a> to see your own case.</p>','published',5,206, NOW() - INTERVAL 21 DAY),
(3,1,2,'fundo-de-emergencia','How to build an emergency fund','How much to save, where to keep it, and how long it takes.',
'<p>An emergency fund covers <strong>3 to 6 months</strong> of your essential expenses. It is there for the unexpected — job loss, breakdowns, health — without resorting to expensive credit.</p><p>Where to keep it? In an easy-access savings account, separate from your day-to-day account. Set a goal in WiseWallet and contribute monthly: the system creates automatic milestones at 25%, 50% and 75%.</p>','published',4,154, NOW() - INTERVAL 12 DAY),
(4,1,5,'entender-irs-portugal','Understanding income tax in Portugal','Brackets, marginal rates, and why a raise never lowers your take-home pay.',
'<p>Portuguese income tax (IRS) is <strong>progressive by bracket</strong>: each slice of income is taxed at its own rate. Moving up a bracket only affects the part above the threshold — never your whole income. Use the <a href="/simulators.php">income-tax simulator</a> to estimate your net pay based on the current brackets.</p>','published',6,98, NOW() - INTERVAL 5 DAY);

-- ── Demo user (id 2) populated data ──────────────────────────────────────
INSERT INTO `accounts` (`id`,`user_id`,`name`,`type`,`balance`,`currency`,`color`) VALUES
(1,2,'Checking','checking',2450.75,'EUR','#1f5a3f'),
(2,2,'Savings','savings',8200.00,'EUR','#2f6f4f'),
(3,2,'Credit card','credit',-340.20,'EUR','#a53a2a'),
(4,2,'Cash','cash',85.00,'EUR','#9e6b2a'),
(5,2,'Crypto wallet','crypto',1320.50,'EUR','#2f6f7f');

INSERT INTO `transactions` (`user_id`,`account_id`,`to_account_id`,`category_id`,`type`,`amount`,`description`,`notes`,`occurred_on`) VALUES
-- current month
(2,1,NULL,1,'income',1850.00,'Monthly salary',NULL, CURDATE() - INTERVAL 28 DAY),
(2,1,NULL,9,'expense',650.00,'Apartment rent',NULL, CURDATE() - INTERVAL 25 DAY),
(2,1,2,NULL,'transfer',200.00,'Automatic savings','Monthly transfer', CURDATE() - INTERVAL 25 DAY),
(2,1,NULL,6,'expense',78.45,'Supermarket',NULL, CURDATE() - INTERVAL 24 DAY),
(2,4,NULL,7,'expense',32.00,'Lunch with colleagues','Team lunch', CURDATE() - INTERVAL 23 DAY),
(2,1,NULL,8,'expense',40.00,'Monthly transit pass',NULL, CURDATE() - INTERVAL 22 DAY),
(2,1,NULL,15,'expense',13.99,'Netflix',NULL, CURDATE() - INTERVAL 20 DAY),
(2,1,NULL,15,'expense',6.99,'Spotify',NULL, CURDATE() - INTERVAL 20 DAY),
(2,1,NULL,11,'expense',45.00,'Cinema and dinner','Film premiere', CURDATE() - INTERVAL 15 DAY),
(2,1,NULL,2,'income',320.00,'Freelance web project',NULL, CURDATE() - INTERVAL 12 DAY),
(2,1,NULL,6,'expense',64.20,'Supermarket',NULL, CURDATE() - INTERVAL 10 DAY),
(2,4,NULL,7,'expense',28.50,'Sushi',NULL, CURDATE() - INTERVAL 8 DAY),
(2,1,NULL,10,'expense',35.00,'Pharmacy',NULL, CURDATE() - INTERVAL 6 DAY),
(2,3,NULL,13,'expense',89.90,'New trainers',NULL, CURDATE() - INTERVAL 4 DAY),
(2,2,NULL,3,'income',52.30,'ETF dividends',NULL, CURDATE() - INTERVAL 3 DAY),
(2,1,NULL,6,'expense',71.10,'Supermarket',NULL, CURDATE() - INTERVAL 2 DAY),
-- previous month
(2,1,NULL,1,'income',1850.00,'Monthly salary',NULL, CURDATE() - INTERVAL 58 DAY),
(2,1,NULL,9,'expense',650.00,'Apartment rent',NULL, CURDATE() - INTERVAL 55 DAY),
(2,1,NULL,6,'expense',286.40,'Monthly shopping',NULL, CURDATE() - INTERVAL 50 DAY),
(2,1,NULL,8,'expense',40.00,'Monthly transit pass',NULL, CURDATE() - INTERVAL 50 DAY),
(2,1,NULL,11,'expense',120.00,'Concert',NULL, CURDATE() - INTERVAL 45 DAY),
-- two months ago
(2,1,NULL,1,'income',1850.00,'Monthly salary',NULL, CURDATE() - INTERVAL 89 DAY),
(2,1,NULL,9,'expense',650.00,'Apartment rent',NULL, CURDATE() - INTERVAL 86 DAY),
(2,1,NULL,16,'expense',410.00,'Weekend in Porto',NULL, CURDATE() - INTERVAL 80 DAY);

INSERT INTO `budgets` (`user_id`,`category_id`,`amount`,`period`,`start_date`) VALUES
(2,6,400.00,'monthly', DATE_FORMAT(CURDATE(),'%Y-%m-01')),
(2,7,150.00,'monthly', DATE_FORMAT(CURDATE(),'%Y-%m-01')),
(2,8,100.00,'monthly', DATE_FORMAT(CURDATE(),'%Y-%m-01')),
(2,11,120.00,'monthly', DATE_FORMAT(CURDATE(),'%Y-%m-01')),
(2,15,40.00,'monthly', DATE_FORMAT(CURDATE(),'%Y-%m-01'));

INSERT INTO `goals` (`id`,`user_id`,`name`,`target_amount`,`current_amount`,`deadline`,`color`,`icon`,`status`) VALUES
(1,2,'Emergency fund',6000.00,3500.00, CURDATE() + INTERVAL 6 MONTH,'#2f6f4f','umbrella','active'),
(2,2,'Holiday 2026',2500.00,900.00, CURDATE() + INTERVAL 4 MONTH,'#2f6f7f','plane','active'),
(3,2,'New laptop',1400.00,1400.00, CURDATE() - INTERVAL 10 DAY,'#6b4a8a','laptop','completed');

INSERT INTO `goal_contributions` (`goal_id`,`user_id`,`amount`,`note`,`contributed_on`) VALUES
(1,2,500.00,'Initial saving', CURDATE() - INTERVAL 80 DAY),
(1,2,1500.00,'Work bonus', CURDATE() - INTERVAL 50 DAY),
(1,2,1500.00,'Monthly effort', CURDATE() - INTERVAL 20 DAY),
(2,2,400.00,'Start', CURDATE() - INTERVAL 40 DAY),
(2,2,500.00,'Monthly', CURDATE() - INTERVAL 10 DAY);

INSERT INTO `bills` (`user_id`,`name`,`amount`,`due_date`,`recurrence`,`category_id`,`account_id`,`status`) VALUES
(2,'Rent',650.00, CURDATE() + INTERVAL 5 DAY,'monthly',9,1,'pending'),
(2,'Electricity',64.30, CURDATE() + INTERVAL 12 DAY,'monthly',14,1,'pending'),
(2,'Internet',39.99, CURDATE() + INTERVAL 9 DAY,'monthly',14,1,'pending'),
(2,'Car insurance',45.00, CURDATE() - INTERVAL 3 DAY,'monthly',14,1,'overdue'),
(2,'Water',22.10, CURDATE() - INTERVAL 20 DAY,'monthly',14,1,'paid');

INSERT INTO `subscriptions` (`user_id`,`name`,`amount`,`billing_cycle`,`next_renewal`,`category_id`,`is_active`) VALUES
(2,'Netflix',13.99,'monthly', CURDATE() + INTERVAL 10 DAY,15,1),
(2,'Spotify',6.99,'monthly', CURDATE() + INTERVAL 6 DAY,15,1),
(2,'Gym membership',29.99,'monthly', CURDATE() + INTERVAL 2 DAY,11,1),
(2,'Amazon Prime',49.90,'yearly', CURDATE() + INTERVAL 120 DAY,13,1),
(2,'iCloud+',2.99,'monthly', CURDATE() + INTERVAL 18 DAY,14,1);

INSERT INTO `investments` (`user_id`,`name`,`symbol`,`type`,`quantity`,`buy_price`,`current_price`,`currency`,`purchased_on`) VALUES
(2,'Vanguard FTSE All-World','VWCE','etf',12.00000000,98.50000000,112.30000000,'EUR', CURDATE() - INTERVAL 200 DAY),
(2,'Apple Inc.','AAPL','stock',5.00000000,165.00000000,189.20000000,'EUR', CURDATE() - INTERVAL 150 DAY),
(2,'Bitcoin','BTC','crypto',0.02500000,38000.00000000,42500.00000000,'EUR', CURDATE() - INTERVAL 120 DAY),
(2,'Treasury bonds','OTRT','bond',10.00000000,100.00000000,101.50000000,'EUR', CURDATE() - INTERVAL 90 DAY),
(2,'Retirement plan (PPR)','PPR','retirement',1.00000000,3000.00000000,3145.00000000,'EUR', CURDATE() - INTERVAL 300 DAY);

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
(2,'achievement','Achievement unlocked','You earned "Achiever" for completing a goal.','trophy',0),
(2,'budget','Budget warning','You have used 78% of your Groceries budget this month.','alert-triangle',0),
(2,'bill','Bill due soon','Your rent is due in 5 days.','calendar',1);


-- Additional blog articles (more educational content + recommendation coverage).
INSERT INTO `articles` (`id`,`author_id`,`category_id`,`slug`,`title`,`excerpt`,`body`,`status`,`reading_minutes`,`views`,`published_at`) VALUES
(5,1,4,'como-funciona-credit-score','How credit scores actually work','The factors that move your score up or down, and the ones that barely matter.','<p>A credit score is a single number that summarises how risky you look to a lender. In most scoring models, five factors matter:</p><ul><li><strong>Payment history (~35%)</strong> — paying on time, every time, is the single biggest lever.</li><li><strong>Amounts owed (~30%)</strong> — how much of your available credit you are using (your "utilisation").</li><li><strong>Length of history (~15%)</strong> — older accounts in good standing help.</li><li><strong>New credit (~10%)</strong> — too many applications in a short window looks risky.</li><li><strong>Credit mix (~10%)</strong> — a healthy blend of credit types.</li></ul><p>The practical takeaway: automate your payments so you never miss one, and try to keep utilisation below 30% of your limit. Everything else is secondary.</p>','published',5,142, '2026-06-06 10:43:12'),
(6,1,4,'snowball-vs-avalanche','Debt snowball vs avalanche: which payoff method wins','Two popular strategies for clearing multiple debts — and when each makes sense.','<p>When you have several debts, the order in which you pay them off matters less for the maths and more for your motivation.</p><p><strong>Avalanche:</strong> pay minimums on everything, then throw every spare euro at the debt with the <em>highest interest rate</em>. This minimises total interest paid — it is mathematically optimal.</p><p><strong>Snowball:</strong> pay minimums on everything, then attack the <em>smallest balance</em> first, regardless of its rate. You clear accounts faster, which builds momentum and confidence.</p><p>If you tend to lose motivation, the snowball''s quick wins often beat the avalanche''s theoretical savings. If you are disciplined and the rate gap is large, avalanche saves real money. Either beats doing nothing.</p>','published',4,118, '2026-06-08 10:43:12'),
(7,1,2,'onde-guardar-fundo-emergencia','Where to keep your emergency fund for the best return','Liquidity matters more than yield here — but you do not have to earn zero.','<p>The job of an emergency fund is to be there <em>exactly</em> when you need it — not to maximise returns. That rules out stocks (too volatile) and locked term deposits (not liquid enough).</p><p>Good options, roughly in order of liquidity:</p><ul><li><strong>High-interest savings account</strong> — instant access, modest but real interest.</li><li><strong>Easy-access deposit account</strong> — slightly better rates, usually same-day or next-day access.</li><li><strong>Short-term government bond funds</strong> — for the portion you are unlikely to touch within a few months.</li></ul><p>A simple split many people use: keep one month of expenses in your current account for instant access, and the rest in a separate high-interest savings account so it is not mixed with day-to-day spending money.</p>','published',4,97, '2026-06-10 10:43:12'),
(8,1,3,'diversificacao-sem-jargao','Diversification, explained without the jargon','Why "don''t put all your eggs in one basket" is harder than it sounds — and how to actually do it.','<p>Diversification means spreading your money across assets that do not all move the same way at the same time. The benefit is not higher returns — it is fewer terrifying drawdowns.</p><p>Three dimensions to diversify across:</p><ul><li><strong>Asset class</strong> — stocks, bonds, real estate, cash. They react differently to the same economic news.</li><li><strong>Geography</strong> — a portfolio that is 100% one country carries that country''s specific risk.</li><li><strong>Number of holdings</strong> — owning three stocks is a bet; owning a broad index fund of 1,500+ companies is closer to owning "the market".</li></ul><p>In practice, a single low-cost, globally diversified ETF can do most of this work for you in one purchase — which is why so many long-term investors keep their portfolio deliberately simple.</p>','published',5,176, '2026-06-13 10:43:12'),
(9,1,1,'orcamento-base-zero','Zero-based budgeting: give every euro a job','A stricter alternative to the 50/30/20 rule — every euro is assigned before the month starts.','<p>In zero-based budgeting, you start each month by allocating <strong>every euro of income</strong> to a category — bills, groceries, savings, fun — until income minus allocations equals zero. Nothing is left unassigned to be spent thoughtlessly.</p><p>The method forces two useful habits: it makes saving a planned category instead of "whatever is left", and it surfaces categories that quietly grow over time (subscriptions are a classic offender).</p><p>It takes more effort upfront than 50/30/20, but many people find it gives a clearer sense of control, especially right after a change in income or when trying to hit an aggressive savings goal.</p>','published',4,103, '2026-06-15 10:43:12'),
(10,1,5,'inflacao-de-estilo-de-vida','Lifestyle inflation: the silent budget killer','Why a pay rise so rarely seems to translate into extra savings.','<p>Lifestyle inflation is the tendency to increase spending automatically as income rises — a bigger flat, more takeaways, the newest phone every cycle — until the raise has vanished into the new "normal" cost of living.</p><p>It is not that spending more is wrong; it is that it usually happens <em>without a decision</em>. The fix is simple in principle: when your income increases, decide in advance what share goes to savings before your spending has a chance to expand to fill it. Automating a transfer the day your salary lands works far better than relying on willpower at the end of the month.</p>','published',3,89, '2026-06-17 10:43:12'),
(11,1,5,'inflacao-poupanca','How inflation quietly taxes your savings','Money sitting in a 0% account is not "safe" — it is slowly losing purchasing power.','<p>If inflation runs at 3% a year and your savings account pays 0%, your money has not stood still — it has lost about 3% of its purchasing power, every year, silently.</p><p>This is why "playing it safe" by holding everything in cash is itself a risk, just a slower and less visible one than a stock market drop. The practical response is not to avoid cash entirely — you still need an emergency fund — but to make sure money you will not need for five-plus years is invested somewhere with a realistic chance of beating inflation over time.</p>','published',4,121, '2026-06-20 10:43:12'),
(12,1,3,'etf-vs-acoes-individuais','ETFs vs individual stocks for beginners','Two very different amounts of research, risk, and time — pick the one that matches yours.','<p><strong>An ETF</strong> (exchange-traded fund) bundles many companies into a single tradeable share. Buying one globally diversified ETF spreads your risk across hundreds or thousands of companies instantly, with no need to analyse any of them individually.</p><p><strong>Individual stocks</strong> can outperform the market — but they can also underperform badly, and picking the winners in advance is famously difficult, even for professionals. They demand real research, ongoing attention, and emotional discipline.</p><p>A common approach: build the core of a portfolio with low-cost ETFs, and — only with money you can afford to risk — allocate a small "satellite" portion to individual stocks you have researched and believe in.</p>','published',5,134, '2026-06-22 10:43:12');

-- Sample market-news cache (so the news page has content offline).
INSERT INTO `news_cache` (`source`,`title`,`url`,`summary`,`published_at`,`fetched_at`) VALUES
('Reuters','ECB holds interest rates steady as inflation cools','https://www.reuters.com/markets/europe/','The European Central Bank kept its key rate unchanged, citing easing price pressures across the euro area.', NOW() - INTERVAL 2 HOUR, NOW()),
('Financial Times','Global equities rally on strong technology earnings','https://www.ft.com/markets','Major indices climbed after upbeat results from large-cap tech lifted investor sentiment.', NOW() - INTERVAL 5 HOUR, NOW()),
('Bloomberg','Euro firms against the dollar ahead of data','https://www.bloomberg.com/markets','The single currency advanced as traders positioned for upcoming inflation figures.', NOW() - INTERVAL 8 HOUR, NOW()),
('CNBC','Bitcoin tops $42,000 amid renewed institutional demand','https://www.cnbc.com/markets/','The largest cryptocurrency extended gains as inflows into spot products picked up.', NOW() - INTERVAL 11 HOUR, NOW()),
('Investopedia','How to build a recession-proof budget','https://www.investopedia.com/personal-finance/','A practical framework for prioritising essentials, savings and an emergency buffer.', NOW() - INTERVAL 1 DAY, NOW()),
('Morningstar','ETFs vs index funds: what investors should know','https://www.morningstar.com/','A clear comparison of costs, taxes and flexibility between the two popular vehicles.', NOW() - INTERVAL 1 DAY - INTERVAL 3 HOUR, NOW()),
('The Economist','The quiet power of compound interest','https://www.economist.com/finance-and-economics','Why time in the market tends to beat timing the market for long-term savers.', NOW() - INTERVAL 2 DAY, NOW()),
('Banco de Portugal','Household savings rate edges higher','https://www.bportugal.pt/en','Recent data points to a modest rise in the share of disposable income being saved.', NOW() - INTERVAL 2 DAY - INTERVAL 6 HOUR, NOW());

-- ════════════════════════════════════════════════════════════════════════
--  End of WiseWallet 2.0 schema + seed
-- ════════════════════════════════════════════════════════════════════════
