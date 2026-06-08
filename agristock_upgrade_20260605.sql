-- ============================================================
-- Cool AgriStock — Production Upgrade Script
-- Date: 2026-06-05
-- Run ONCE in Hostinger phpMyAdmin on u495079612_agristock_db
-- SAFE: uses IF NOT EXISTS and ADD COLUMN IF NOT EXISTS
--       Existing data and tables are never dropped or overwritten
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";

-- ── Step 1: Add missing columns to users table ───────────────────────────
ALTER TABLE `users`
  ADD COLUMN IF NOT EXISTS `group_id`       bigint unsigned NOT NULL DEFAULT 4 AFTER `username`,
  ADD COLUMN IF NOT EXISTS `cooperative_id` bigint unsigned DEFAULT NULL AFTER `group_id`,
  ADD COLUMN IF NOT EXISTS `language`       varchar(255) NOT NULL DEFAULT 'fr' AFTER `cooperative_id`,
  ADD COLUMN IF NOT EXISTS `deleted_at`     timestamp NULL DEFAULT NULL AFTER `updated_at`;

-- New table: cooperatives
CREATE TABLE IF NOT EXISTS `cooperatives` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `location` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cooperatives_code_unique` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- New table: groups
CREATE TABLE IF NOT EXISTS `groups` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL,
  `updated_at` timestamp NOT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf32 ROW_FORMAT=DYNAMIC;

-- New table: failed_jobs
CREATE TABLE IF NOT EXISTS `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- New table: personal_access_tokens
CREATE TABLE IF NOT EXISTS `personal_access_tokens` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tokenable_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `abilities` text COLLATE utf8mb4_unicode_ci,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- New table: password_reset_tokens
CREATE TABLE IF NOT EXISTS `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- New table: inventory_ops
CREATE TABLE IF NOT EXISTS `inventory_ops` (
  `op_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` int NOT NULL,
  `device_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `logical_seq` bigint unsigned NOT NULL,
  `storage_id` int NOT NULL,
  `product_id` int NOT NULL,
  `stock_id` int DEFAULT NULL,
  `op_type` enum('stock_in','stock_out','adjustment','spoilage','transfer') COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantity_delta` decimal(12,3) NOT NULL,
  `unit` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `sync_status` enum('pending','applied','conflict','superseded','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `client_created_at` timestamp NULL DEFAULT NULL,
  `server_received_at` timestamp NULL DEFAULT NULL,
  `applied_at` timestamp NULL DEFAULT NULL,
  `conflict_with_op_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `conflict_reason` text COLLATE utf8mb4_unicode_ci,
  `resolved_by` int DEFAULT NULL,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `cancelled_by` int DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `edited_from_op_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`op_id`),
  KEY `inventory_ops_product_id_foreign` (`product_id`),
  KEY `inventory_ops_stock_id_foreign` (`stock_id`),
  KEY `inventory_ops_resolved_by_foreign` (`resolved_by`),
  KEY `inventory_ops_cancelled_by_foreign` (`cancelled_by`),
  KEY `inventory_ops_user_id_logical_seq_index` (`user_id`,`logical_seq`),
  KEY `inventory_ops_sync_status_index` (`sync_status`),
  KEY `inventory_ops_storage_id_index` (`storage_id`),
  CONSTRAINT `inventory_ops_cancelled_by_foreign` FOREIGN KEY (`cancelled_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `inventory_ops_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `inventory_ops_resolved_by_foreign` FOREIGN KEY (`resolved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `inventory_ops_stock_id_foreign` FOREIGN KEY (`stock_id`) REFERENCES `stocks` (`id`) ON DELETE SET NULL,
  CONSTRAINT `inventory_ops_storage_id_foreign` FOREIGN KEY (`storage_id`) REFERENCES `storages` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `inventory_ops_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- New table: inventory_stock
CREATE TABLE IF NOT EXISTS `inventory_stock` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `storage_id` int NOT NULL,
  `product_id` int NOT NULL,
  `stock_id` int NOT NULL,
  `quantity` decimal(12,3) NOT NULL DEFAULT '0.000',
  `unit` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_op_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `inventory_stock_storage_id_product_id_stock_id_unique` (`storage_id`,`product_id`,`stock_id`),
  KEY `inventory_stock_product_id_foreign` (`product_id`),
  KEY `inventory_stock_stock_id_foreign` (`stock_id`)
) ENGINE=InnoDB AUTO_INCREMENT=53 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- New table: member_phones
CREATE TABLE IF NOT EXISTS `member_phones` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `phone` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `verified_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `member_phones_phone_unique` (`phone`),
  KEY `member_phones_user_id_foreign` (`user_id`),
  CONSTRAINT `member_phones_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=71 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- New table: sync_audit_log
CREATE TABLE IF NOT EXISTS `sync_audit_log` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `op_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `actor_id` int NOT NULL,
  `actor_group_id` int NOT NULL,
  `action` enum('submitted','applied','reconciled','conflict_flagged','accepted','discarded','cancelled','merged','edited','overridden') COLLATE utf8mb4_unicode_ci NOT NULL,
  `before_value` json DEFAULT NULL,
  `after_value` json DEFAULT NULL,
  `reason` text COLLATE utf8mb4_unicode_ci,
  `device_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ip_address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `sync_audit_log_op_id_index` (`op_id`),
  KEY `sync_audit_log_actor_id_index` (`actor_id`),
  CONSTRAINT `sync_audit_log_actor_id_foreign` FOREIGN KEY (`actor_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=3584 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- New table: sync_permissions
CREATE TABLE IF NOT EXISTS `sync_permissions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `group_id` int NOT NULL,
  `action` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `allowed` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sync_permissions_group_id_action_unique` (`group_id`,`action`),
  CONSTRAINT `sync_permissions_group_id_foreign` FOREIGN KEY (`group_id`) REFERENCES `groups` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=95 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- New table: sync_sessions
CREATE TABLE IF NOT EXISTS `sync_sessions` (
  `session_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` int NOT NULL,
  `device_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `client_logical_seq` bigint NOT NULL DEFAULT '0',
  `ops_submitted` int unsigned NOT NULL DEFAULT '0',
  `ops_applied` int unsigned NOT NULL DEFAULT '0',
  `ops_conflicted` int unsigned NOT NULL DEFAULT '0',
  `status` enum('in_progress','completed','failed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'in_progress',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`session_id`),
  KEY `sync_sessions_user_id_status_index` (`user_id`,`status`),
  CONSTRAINT `sync_sessions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ── Step 3: Seed groups (INSERT IGNORE = skip if already exists) ─────────
INSERT IGNORE INTO `groups` (`id`, `name`, `created_at`, `updated_at`) VALUES
(1,  'Admin',                       NOW(), NOW()),
(2,  'Superviseur',                 NOW(), NOW()),
(3,  'Comptable',                   NOW(), NOW()),
(4,  'Caissière',                   NOW(), NOW()),
(5,  'Coopérative Agricole',        NOW(), NOW()),
(6,  'Coopératives de Pêche',       NOW(), NOW()),
(7,  'Grossiste',                   NOW(), NOW()),
(8,  'Entreprises Agroalimentaire', NOW(), NOW()),
(10, 'Particulier',                 NOW(), NOW());

-- ── Step 4: Seed sync_permissions ────────────────────────────────────────
INSERT IGNORE INTO `sync_permissions` (`group_id`,`action`,`allowed`,`created_at`,`updated_at`) VALUES
(1,'sync.push',1,NOW(),NOW()),(1,'sync.pull',1,NOW(),NOW()),(1,'sync.reconcile',1,NOW(),NOW()),
(1,'sync.accept',1,NOW(),NOW()),(1,'sync.discard',1,NOW(),NOW()),(1,'sync.cancel',1,NOW(),NOW()),
(1,'sync.merge',1,NOW(),NOW()),(1,'sync.edit',1,NOW(),NOW()),(1,'log.view',1,NOW(),NOW()),(1,'log.export',1,NOW(),NOW()),
(2,'sync.push',1,NOW(),NOW()),(2,'sync.pull',1,NOW(),NOW()),(2,'sync.reconcile',1,NOW(),NOW()),
(2,'sync.accept',1,NOW(),NOW()),(2,'sync.discard',1,NOW(),NOW()),(2,'sync.cancel',1,NOW(),NOW()),
(2,'sync.merge',0,NOW(),NOW()),(2,'sync.edit',1,NOW(),NOW()),(2,'log.view',1,NOW(),NOW()),(2,'log.export',0,NOW(),NOW()),
(3,'sync.push',1,NOW(),NOW()),(3,'sync.pull',1,NOW(),NOW()),(3,'sync.reconcile',1,NOW(),NOW()),
(3,'sync.accept',1,NOW(),NOW()),(3,'sync.discard',1,NOW(),NOW()),(3,'sync.cancel',1,NOW(),NOW()),
(3,'sync.merge',0,NOW(),NOW()),(3,'sync.edit',1,NOW(),NOW()),(3,'log.view',1,NOW(),NOW()),(3,'log.export',0,NOW(),NOW()),
(4,'sync.push',1,NOW(),NOW()),(4,'sync.pull',1,NOW(),NOW()),(4,'sync.reconcile',1,NOW(),NOW()),
(4,'sync.accept',1,NOW(),NOW()),(4,'sync.discard',1,NOW(),NOW()),(4,'sync.cancel',1,NOW(),NOW()),
(4,'sync.merge',0,NOW(),NOW()),(4,'sync.edit',1,NOW(),NOW()),(4,'log.view',1,NOW(),NOW()),(4,'log.export',0,NOW(),NOW()),
(5,'sync.push',1,NOW(),NOW()),(5,'sync.pull',1,NOW(),NOW()),(5,'sync.reconcile',1,NOW(),NOW()),
(5,'sync.accept',0,NOW(),NOW()),(5,'sync.discard',1,NOW(),NOW()),(5,'sync.cancel',1,NOW(),NOW()),
(5,'sync.merge',0,NOW(),NOW()),(5,'sync.edit',1,NOW(),NOW()),(5,'log.view',1,NOW(),NOW()),(5,'log.export',0,NOW(),NOW()),
(6,'sync.push',1,NOW(),NOW()),(6,'sync.pull',1,NOW(),NOW()),(6,'sync.reconcile',1,NOW(),NOW()),
(6,'sync.accept',0,NOW(),NOW()),(6,'sync.discard',1,NOW(),NOW()),(6,'sync.cancel',1,NOW(),NOW()),
(6,'sync.merge',0,NOW(),NOW()),(6,'sync.edit',1,NOW(),NOW()),(6,'log.view',1,NOW(),NOW()),(6,'log.export',0,NOW(),NOW()),
(7,'sync.push',1,NOW(),NOW()),(7,'sync.pull',1,NOW(),NOW()),(7,'sync.reconcile',1,NOW(),NOW()),
(7,'sync.accept',0,NOW(),NOW()),(7,'sync.discard',1,NOW(),NOW()),(7,'sync.cancel',1,NOW(),NOW()),
(7,'sync.merge',0,NOW(),NOW()),(7,'sync.edit',1,NOW(),NOW()),(7,'log.view',1,NOW(),NOW()),(7,'log.export',0,NOW(),NOW()),
(8,'sync.push',1,NOW(),NOW()),(8,'sync.pull',1,NOW(),NOW()),(8,'sync.reconcile',1,NOW(),NOW()),
(8,'sync.accept',0,NOW(),NOW()),(8,'sync.discard',1,NOW(),NOW()),(8,'sync.cancel',1,NOW(),NOW()),
(8,'sync.merge',0,NOW(),NOW()),(8,'sync.edit',1,NOW(),NOW()),(8,'log.view',1,NOW(),NOW()),(8,'log.export',0,NOW(),NOW()),
(10,'sync.push',1,NOW(),NOW()),(10,'sync.pull',1,NOW(),NOW()),(10,'sync.reconcile',1,NOW(),NOW()),
(10,'sync.accept',0,NOW(),NOW()),(10,'sync.discard',1,NOW(),NOW()),(10,'sync.cancel',1,NOW(),NOW()),
(10,'sync.merge',0,NOW(),NOW()),(10,'sync.edit',1,NOW(),NOW()),(10,'log.view',1,NOW(),NOW()),(10,'log.export',0,NOW(),NOW());

-- ── Step 5: Ensure sysadmin has group_id = 1 ─────────────────────────────
UPDATE `users` SET `group_id` = 1
WHERE `email` = 'sysadmin@coolagristock.com';

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- Done. Refresh the page and login should work.
-- ============================================================
