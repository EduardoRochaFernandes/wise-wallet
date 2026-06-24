-- WiseWallet 2.0 — security hardening migration (idempotent).
-- Apply:  mysql -u root wisewallet < database/migrations/001_security.sql
USE `wisewallet`;

ALTER TABLE `users`
  ADD COLUMN IF NOT EXISTS `totp_secret`  VARCHAR(64) NULL,
  ADD COLUMN IF NOT EXISTS `totp_enabled` TINYINT(1) NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS `locked_until` DATETIME NULL;

CREATE TABLE IF NOT EXISTS `rate_limits` (
  `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `rl_key`       VARCHAR(190) NOT NULL,
  `hits`         INT NOT NULL DEFAULT 0,
  `window_start` INT NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_rl_key` (`rl_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `password_resets` (
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

CREATE TABLE IF NOT EXISTS `api_keys` (
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

CREATE TABLE IF NOT EXISTS `security_events` (
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
