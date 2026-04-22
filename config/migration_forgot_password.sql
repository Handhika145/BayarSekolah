-- =============================================
-- Migration: Forgot Password Feature
-- Tanggal: 2026-04-09
-- =============================================

-- 1. Tambah kolom email ke tabel users
ALTER TABLE `users` ADD COLUMN `email` VARCHAR(100) NULL AFTER `username`;

-- 2. Buat tabel password_resets untuk menyimpan token reset
CREATE TABLE IF NOT EXISTS `password_resets` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `email` VARCHAR(100) NOT NULL,
    `token` VARCHAR(64) NOT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_token` (`token`),
    INDEX `idx_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
