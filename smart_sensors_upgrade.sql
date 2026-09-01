-- ============================================================
-- Cool AgriStock — Smart Sensor Management Upgrade Script
-- Date: 2026-09-01
-- Run ONCE in Hostinger phpMyAdmin on your production database
-- SAFE: uses IF NOT EXISTS / ADD COLUMN IF NOT EXISTS
--       Existing data and tables are never dropped or overwritten
-- Covers migrations:
--   2026_08_12_000001_add_thingsboard_fields_to_storages_table
--   2026_08_12_000002_create_environmental_profiles_table
--   2026_08_12_000003_create_drying_batches_table
--   2026_08_31_000001_add_min_rh_to_environmental_profiles_table
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";

-- ─────────────────────────────────────────────────────────────
-- Step 1: Link `storages` (used as "Environment/Dryer") to ThingsBoard
-- ─────────────────────────────────────────────────────────────
ALTER TABLE `storages`
  ADD COLUMN IF NOT EXISTS `thingsboard_device_id` VARCHAR(255) NULL AFTER `capacity`,
  ADD COLUMN IF NOT EXISTS `stale_threshold_minutes` INT UNSIGNED NOT NULL DEFAULT 15 AFTER `thingsboard_device_id`;

ALTER TABLE `storages` ADD UNIQUE INDEX IF NOT EXISTS `storages_thingsboard_device_id_unique` (`thingsboard_device_id`);

-- ─────────────────────────────────────────────────────────────
-- Step 2: New table — environmental_profiles (per-product target ranges)
-- Includes min_rh directly (covers both the 08-12 and 08-31 migrations)
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `environmental_profiles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `product_id` int NOT NULL,
  `min_temperature` decimal(5,2) DEFAULT NULL,
  `max_temperature` decimal(5,2) DEFAULT NULL,
  `min_rh` decimal(5,2) DEFAULT NULL,
  `max_rh` decimal(5,2) DEFAULT NULL,
  `min_airflow` decimal(5,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `environmental_profiles_product_id_unique` (`product_id`),
  CONSTRAINT `environmental_profiles_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- If environmental_profiles already existed WITHOUT min_rh (e.g. a partial prior deploy), add it:
ALTER TABLE `environmental_profiles` ADD COLUMN IF NOT EXISTS `min_rh` decimal(5,2) DEFAULT NULL AFTER `max_temperature`;

-- ─────────────────────────────────────────────────────────────
-- Step 3: New table — drying_batches (traceability: environment → product → profile → customer)
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `drying_batches` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `batch_code` varchar(255) NOT NULL,
  `storage_id` int NOT NULL,
  `product_id` int NOT NULL,
  `environmental_profile_id` bigint unsigned DEFAULT NULL,
  `customer_id` int DEFAULT NULL,
  `operator_id` int DEFAULT NULL,
  `start_time` timestamp NOT NULL,
  `end_time` timestamp NULL DEFAULT NULL,
  `status` enum('in_progress','completed','cancelled') NOT NULL DEFAULT 'in_progress',
  `outcome` varchar(255) DEFAULT NULL,
  `notes` text,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `drying_batches_batch_code_unique` (`batch_code`),
  KEY `drying_batches_storage_id_status_index` (`storage_id`,`status`),
  KEY `drying_batches_customer_id_index` (`customer_id`),
  KEY `drying_batches_product_id_foreign` (`product_id`),
  KEY `drying_batches_environmental_profile_id_foreign` (`environmental_profile_id`),
  KEY `drying_batches_operator_id_foreign` (`operator_id`),
  CONSTRAINT `drying_batches_storage_id_foreign` FOREIGN KEY (`storage_id`) REFERENCES `storages` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `drying_batches_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `drying_batches_environmental_profile_id_foreign` FOREIGN KEY (`environmental_profile_id`) REFERENCES `environmental_profiles` (`id`) ON DELETE SET NULL,
  CONSTRAINT `drying_batches_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `drying_batches_operator_id_foreign` FOREIGN KEY (`operator_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────────────────────
-- Step 4: Mark these migrations as run, so `php artisan migrate` (if you
-- ever do get shell access) won't try to redo them. Uses the next free
-- batch number automatically — safe no matter what's already in there.
-- ─────────────────────────────────────────────────────────────
INSERT IGNORE INTO `migrations` (`migration`, `batch`)
SELECT * FROM (
  SELECT '2026_08_12_000001_add_thingsboard_fields_to_storages_table' AS migration, (SELECT COALESCE(MAX(batch), 0) + 1 FROM `migrations`) AS batch
  UNION ALL SELECT '2026_08_12_000002_create_environmental_profiles_table', (SELECT COALESCE(MAX(batch), 0) + 1 FROM `migrations`)
  UNION ALL SELECT '2026_08_12_000003_create_drying_batches_table', (SELECT COALESCE(MAX(batch), 0) + 1 FROM `migrations`)
  UNION ALL SELECT '2026_08_31_000001_add_min_rh_to_environmental_profiles_table', (SELECT COALESCE(MAX(batch), 0) + 1 FROM `migrations`)
) AS t;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- END OF UPGRADE SCRIPT
-- After running this: log into COOL AGRISTOCK, open Storages, and set a
-- "ThingsBoard Device ID" on one to see it appear under Smart Sensors.
-- If you also have SSH, running `php artisan config:clear` afterward
-- is a good idea (not required, this script doesn't touch config).
-- ============================================================
