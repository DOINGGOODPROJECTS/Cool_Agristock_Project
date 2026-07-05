-- ============================================================
-- Cool AgriStock — Production Upgrade Script
-- Date: 2026-07-05
-- Run ONCE in Hostinger phpMyAdmin on u495079612_agristock_db
-- SAFE: uses IF NOT EXISTS and ADD COLUMN IF NOT EXISTS
--       Existing data and tables are never dropped or overwritten
-- Covers: users table missing columns (blocks login),
--         Accounting module (financial events, invoices,
--         journal entries, Odoo integration, customer fields)
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";

-- ─────────────────────────────────────────────────────────────
-- Step 0: Fix users table — missing columns (BLOCKS LOGIN)
-- These columns are required by LocaleMiddleware and auth system
-- ─────────────────────────────────────────────────────────────
ALTER TABLE `users`
  ADD COLUMN IF NOT EXISTS `group_id` BIGINT UNSIGNED NOT NULL DEFAULT 4 AFTER `email`,
  ADD COLUMN IF NOT EXISTS `phone` VARCHAR(50) NULL AFTER `email`,
  ADD COLUMN IF NOT EXISTS `username` VARCHAR(100) NULL AFTER `phone`,
  ADD COLUMN IF NOT EXISTS `language` VARCHAR(10) NOT NULL DEFAULT 'fr' AFTER `email`;

-- Add unique index on username if not already present
ALTER TABLE `users` ADD UNIQUE INDEX IF NOT EXISTS `users_username_unique` (`username`);

-- Mark users-table migrations as run so artisan migrate skips them
INSERT IGNORE INTO `migrations` (`migration`, `batch`) VALUES
  ('2025_11_18_000000_add_language_to_users_table', 1),
  ('2025_11_18_182753_add_deleted_at_to_users_table', 1),
  ('2025_11_18_183356_add_group_id_to_users_table', 1),
  ('2025_11_18_185330_add_phone_and_username_to_users_table', 1);

-- ─────────────────────────────────────────────────────────────
-- Step 1: New table — financial_events
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `financial_events` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `financial_event_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `idempotency_key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `event_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `version` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '1.0',
  `source_reference` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `source_model` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `customer_id` bigint unsigned NOT NULL,
  `stock_id` bigint unsigned DEFAULT NULL,
  `product_id` bigint unsigned DEFAULT NULL,
  `storage_id` bigint unsigned DEFAULT NULL,
  `quantity` decimal(14,4) DEFAULT NULL,
  `unit` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `amount` decimal(14,4) NOT NULL,
  `currency` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'XOF',
  `event_date` date NOT NULL,
  `due_date` date DEFAULT NULL,
  `service_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tax_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `accounting_status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `approval_status` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `approved_by` bigint unsigned DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `odoo_model` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `odoo_record_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `odoo_reference` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `export_batch_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sync_error_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sync_error_message` text COLLATE utf8mb4_unicode_ci,
  `retry_count` smallint unsigned NOT NULL DEFAULT '0',
  `last_sync_at` timestamp NULL DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `financial_events_financial_event_id_unique` (`financial_event_id`),
  UNIQUE KEY `financial_events_idempotency_key_unique` (`idempotency_key`),
  KEY `financial_events_event_type_index` (`event_type`),
  KEY `financial_events_customer_id_index` (`customer_id`),
  KEY `financial_events_accounting_status_index` (`accounting_status`),
  KEY `financial_events_export_batch_id_index` (`export_batch_id`),
  KEY `financial_events_created_at_index` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────────────────────
-- Step 2: New table — export_batches
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `export_batches` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `batch_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `export_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `file_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `file_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `file_checksum` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `row_count` int unsigned NOT NULL DEFAULT '0',
  `file_size_bytes` bigint unsigned DEFAULT NULL,
  `period_from` date DEFAULT NULL,
  `period_to` date DEFAULT NULL,
  `filters` json DEFAULT NULL,
  `validation_errors` int unsigned NOT NULL DEFAULT '0',
  `validation_report` text COLLATE utf8mb4_unicode_ci,
  `error_message` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `generated_by` bigint unsigned DEFAULT NULL,
  `generated_at` timestamp NULL DEFAULT NULL,
  `downloaded_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `export_batches_batch_id_unique` (`batch_id`),
  KEY `export_batches_export_type_index` (`export_type`),
  KEY `export_batches_status_index` (`status`),
  KEY `export_batches_generated_at_index` (`generated_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────────────────────
-- Step 3: New table — journal_entries
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `journal_entries` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `reference` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `entry_date` date NOT NULL,
  `entry_type` enum('manual','auto_billing','auto_payment','claim','adjustment') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'manual',
  `journal_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `document_reference` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `event_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `provenance_category` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('draft','submitted','approved','posted','rejected') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `source_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `source_id` bigint unsigned DEFAULT NULL,
  `financial_event_id` bigint unsigned DEFAULT NULL,
  `total_debit` decimal(15,2) NOT NULL DEFAULT '0.00',
  `total_credit` decimal(15,2) NOT NULL DEFAULT '0.00',
  `currency` varchar(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'XOF',
  `odoo_status` enum('not_queued','pending_admin_approval','approved_for_odoo','exported','synced','rejected') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'not_queued',
  `send_to_odoo` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Hold for Finance',
  `odoo_approved_by` int DEFAULT NULL,
  `odoo_approved_at` timestamp NULL DEFAULT NULL,
  `odoo_move_id` bigint unsigned DEFAULT NULL,
  `odoo_move_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `odoo_push_error` text COLLATE utf8mb4_unicode_ci,
  `odoo_rejection_reason` text COLLATE utf8mb4_unicode_ci,
  `comments` text COLLATE utf8mb4_unicode_ci,
  `created_by` int NOT NULL,
  `approved_by` int DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `posted_by` int DEFAULT NULL,
  `posted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `journal_entries_reference_unique` (`reference`),
  KEY `journal_entries_source_type_source_id_index` (`source_type`,`source_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────────────────────
-- Step 4: New table — journal_lines
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `journal_lines` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `journal_entry_id` bigint unsigned NOT NULL,
  `line_date` date DEFAULT NULL,
  `journal` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `document_reference` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `customer_id` bigint unsigned DEFAULT NULL,
  `customer_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `event_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `provenance_category` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `account_code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `account_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `label` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `debit` decimal(15,2) NOT NULL DEFAULT '0.00',
  `credit` decimal(15,2) NOT NULL DEFAULT '0.00',
  `currency` varchar(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'XOF',
  `send_to_odoo` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Hold for Finance',
  `odoo_status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Not exported',
  `comments` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `journal_lines_journal_entry_id_foreign` (`journal_entry_id`),
  CONSTRAINT `journal_lines_journal_entry_id_foreign` FOREIGN KEY (`journal_entry_id`) REFERENCES `journal_entries` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────────────────────
-- Step 5: New table — invoices (with all fields)
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `invoices` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `invoice_number` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `billing_id` int DEFAULT NULL,
  `customer_id` int DEFAULT NULL,
  `customer_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `invoice_date` date NOT NULL,
  `due_date` date DEFAULT NULL,
  `stock_lot` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payment_terms` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `odoo_partner_ref` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `subtotal` decimal(15,2) NOT NULL DEFAULT '0.00',
  `tax_amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `total_amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `currency` varchar(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'XOF',
  `status` enum('draft','issued','paid','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `finance_status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'To review',
  `send_to_odoo` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Hold for Finance',
  `odoo_decision_reason` text COLLATE utf8mb4_unicode_ci,
  `accounting_check` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'OK',
  `odoo_invoice_id` bigint unsigned DEFAULT NULL,
  `odoo_invoice_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `odoo_status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'not_exported',
  `odoo_push_error` text COLLATE utf8mb4_unicode_ci,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `generated_by` int NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `invoices_invoice_number_unique` (`invoice_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────────────────────
-- Step 6: New table — invoice_lines
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `invoice_lines` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `invoice_id` bigint unsigned NOT NULL,
  `line_no` int unsigned NOT NULL,
  `service` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `category` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `product` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `unit` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `quantity` decimal(14,4) NOT NULL DEFAULT '0.0000',
  `unit_price` decimal(15,2) NOT NULL DEFAULT '0.00',
  `discount_fixed_amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `amount_before_vat` decimal(15,2) NOT NULL DEFAULT '0.00',
  `vat_rate` decimal(8,4) NOT NULL DEFAULT '0.0000',
  `vat_amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `total_amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `journal_entry_no` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `send_to_odoo` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Hold for Finance',
  `odoo_decision_reason` text COLLATE utf8mb4_unicode_ci,
  `comments` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `invoice_lines_invoice_id_foreign` (`invoice_id`),
  CONSTRAINT `invoice_lines_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────────────────────
-- Step 7: Add Odoo config key to migrations tracking
-- (only adds columns to existing tables — all use IF NOT EXISTS)
-- ─────────────────────────────────────────────────────────────

-- journal_entries extra columns (safety net for existing installs)
ALTER TABLE `journal_entries`
  ADD COLUMN IF NOT EXISTS `journal_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `document_reference` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `event_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `provenance_category` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `send_to_odoo` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Hold for Finance',
  ADD COLUMN IF NOT EXISTS `comments` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `odoo_move_id` bigint unsigned DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `odoo_move_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `odoo_push_error` text COLLATE utf8mb4_unicode_ci DEFAULT NULL;

-- journal_lines extra columns (safety net for existing installs)
ALTER TABLE `journal_lines`
  ADD COLUMN IF NOT EXISTS `line_date` date DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `journal` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `document_reference` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `customer_id` bigint unsigned DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `customer_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `event_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `provenance_category` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `description` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `send_to_odoo` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Hold for Finance',
  ADD COLUMN IF NOT EXISTS `odoo_status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Not exported',
  ADD COLUMN IF NOT EXISTS `comments` text COLLATE utf8mb4_unicode_ci DEFAULT NULL;

-- invoices extra columns (safety net for existing installs)
ALTER TABLE `invoices`
  ADD COLUMN IF NOT EXISTS `customer_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `stock_lot` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `payment_terms` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `odoo_partner_ref` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `finance_status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'To review',
  ADD COLUMN IF NOT EXISTS `send_to_odoo` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Hold for Finance',
  ADD COLUMN IF NOT EXISTS `odoo_decision_reason` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `accounting_check` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'OK',
  ADD COLUMN IF NOT EXISTS `odoo_invoice_id` bigint unsigned DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `odoo_invoice_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `odoo_status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'not_exported',
  ADD COLUMN IF NOT EXISTS `odoo_push_error` text COLLATE utf8mb4_unicode_ci DEFAULT NULL;

-- Make customer_id nullable on invoices (in case FK was strict before)
ALTER TABLE `invoices` MODIFY COLUMN `customer_id` int DEFAULT NULL;

-- ─────────────────────────────────────────────────────────────
-- Step 8: Mark new migrations as run in the migrations table
-- ─────────────────────────────────────────────────────────────
INSERT IGNORE INTO `migrations` (`migration`, `batch`) VALUES
  ('2026_06_24_000001_create_financial_events_table', 5),
  ('2026_06_25_000001_create_export_batches_table', 5),
  ('2026_06_29_100001_create_invoices_table', 6),
  ('2026_06_29_100002_create_journal_entries_table', 6),
  ('2026_06_29_100003_create_journal_lines_table', 6),
  ('2026_07_01_000001_add_workbook_fields_to_accounting_journal', 7),
  ('2026_07_01_000002_add_invoice_generator_fields', 7),
  ('2026_07_03_000001_add_odoo_push_fields_to_journal_entries', 8),
  ('2026_07_05_152411_add_odoo_move_name_to_journal_entries', 9),
  ('2026_07_05_200001_add_customer_name_to_invoices', 10),
  ('2026_07_05_210001_add_odoo_fields_to_invoices', 11),
  ('2026_07_05_220001_add_customer_to_journal_lines', 12);

-- ─────────────────────────────────────────────────────────────
-- Step 9: Seed default system administrator user
-- Uses ON DUPLICATE KEY UPDATE so it's safe to re-run
-- Login: sysadmin@coolagristock.com / Dev5555!
-- ─────────────────────────────────────────────────────────────
INSERT INTO `users`
  (`name`, `email`, `username`, `phone`, `password`, `language`, `group_id`, `email_verified_at`, `created_at`, `updated_at`)
VALUES (
  'System Administrator',
  'sysadmin@coolagristock.com',
  'sysadmin',
  '0500000000',
  '$2y$10$vldwjevm7FfNJtlq00j3Pe8zV5pLdcN0uo6oGxX8YoK9UjuHdxluO',
  'fr',
  1,
  NOW(),
  NOW(),
  NOW()
)
ON DUPLICATE KEY UPDATE
  `password`   = '$2y$10$vldwjevm7FfNJtlq00j3Pe8zV5pLdcN0uo6oGxX8YoK9UjuHdxluO',
  `group_id`   = 1,
  `language`   = 'fr',
  `deleted_at` = NULL,
  `updated_at` = NOW();

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- END OF UPGRADE SCRIPT
-- Default admin: sysadmin@coolagristock.com / Dev5555!
-- Change the password after first login!
-- All done! Run php artisan config:clear on the server after.
-- ============================================================
