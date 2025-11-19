-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1:3306
-- Généré le : mar. 03 juin 2025 à 09:52
-- Version du serveur : 10.11.10-MariaDB
-- Version de PHP : 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `u138627370_coolagristock`
--

-- --------------------------------------------------------

--
-- Structure de la table `activities`
--

CREATE TABLE `activities` (
  `id` int(11) NOT NULL,
  `description` varchar(50) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `login_at` timestamp NOT NULL,
  `logout_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf32 COLLATE=utf32_general_ci;

--
-- Déchargement des données de la table `activities`
--

INSERT INTO `activities` (`id`, `description`, `user_id`, `login_at`, `logout_at`) VALUES
(1, NULL, 1, '2025-03-06 17:04:58', '2025-03-06 17:07:24'),
(2, NULL, 1, '2025-03-06 17:07:03', '2025-03-06 17:07:24'),
(3, NULL, 1, '2025-03-06 17:36:36', '2025-03-10 11:33:49'),
(4, NULL, 1, '2025-03-10 17:04:56', '2025-03-10 17:05:05'),
(5, NULL, 1, '2025-03-10 17:12:22', '2025-03-10 17:22:46'),
(6, NULL, 1, '2025-03-10 17:24:45', '2025-03-10 17:26:54'),
(7, NULL, 1, '2025-03-11 09:34:07', '2025-03-11 17:45:12'),
(8, NULL, 1, '2025-03-11 17:50:56', '2025-03-11 18:00:03'),
(9, NULL, 1, '2025-03-12 10:27:27', '2025-03-12 10:54:38'),
(10, NULL, 1, '2025-03-12 11:16:11', '2025-03-12 17:57:27'),
(11, NULL, 1, '2025-03-13 10:49:49', '2025-03-13 10:52:41'),
(12, NULL, 13, '2025-03-13 10:56:32', '2025-03-13 11:05:42'),
(13, NULL, 4, '2025-03-13 11:06:02', '2025-03-13 11:37:56'),
(14, NULL, 1, '2025-03-13 11:38:32', '2025-03-13 14:36:30'),
(15, NULL, 14, '2025-03-13 14:36:58', '2025-03-13 15:12:31'),
(16, NULL, 1, '2025-03-13 15:12:47', '2025-03-13 15:14:31'),
(17, NULL, 14, '2025-03-13 15:43:17', '2025-03-13 16:59:40'),
(18, NULL, 1, '2025-03-13 16:59:58', '2025-03-13 17:15:04'),
(19, NULL, 1, '2025-03-14 11:32:27', '2025-03-14 15:25:14'),
(20, NULL, 1, '2025-03-14 14:16:32', '2025-03-14 15:25:14'),
(21, NULL, 1, '2025-03-17 09:22:48', '2025-03-17 11:01:43'),
(22, NULL, 14, '2025-03-17 11:02:24', '2025-03-17 11:33:25'),
(23, NULL, 1, '2025-03-17 11:34:44', '2025-03-17 12:30:25'),
(24, NULL, 1, '2025-03-18 10:13:52', '2025-03-18 17:34:42'),
(25, NULL, 1, '2025-03-18 14:50:13', '2025-03-18 17:34:42'),
(26, NULL, 1, '2025-03-19 10:22:03', '2025-03-21 15:20:48'),
(27, NULL, 14, '2025-03-20 16:36:04', '2025-03-20 16:42:40'),
(28, NULL, 1, '2025-03-21 15:00:56', '2025-03-21 15:20:48'),
(29, NULL, 14, '2025-03-21 15:21:10', '2025-03-21 16:13:59'),
(30, NULL, 1, '2025-03-21 16:14:20', '2025-03-21 16:21:27'),
(31, NULL, 1, '2025-03-24 09:21:55', '2025-03-24 09:23:27'),
(32, NULL, 1, '2025-03-24 09:25:17', '2025-03-24 09:58:28'),
(33, NULL, 1, '2025-03-24 11:10:06', '2025-03-24 17:41:29'),
(34, NULL, 1, '2025-03-25 12:39:26', '2025-03-25 16:21:53'),
(35, NULL, 1, '2025-03-25 17:40:03', '2025-03-26 12:34:07'),
(36, NULL, 1, '2025-03-26 12:25:05', '2025-03-26 12:34:07'),
(37, NULL, 1, '2025-03-26 13:00:01', '2025-03-26 13:00:23'),
(38, NULL, 1, '2025-03-26 14:29:02', '2025-03-26 15:04:05'),
(39, NULL, 1, '2025-03-26 15:12:39', '2025-03-26 15:23:56'),
(40, NULL, 1, '2025-03-26 15:18:14', '2025-03-26 15:23:56'),
(41, NULL, 1, '2025-03-26 15:50:53', '2025-03-26 15:52:35'),
(42, NULL, 1, '2025-03-26 15:53:38', '2025-03-26 15:54:11'),
(43, NULL, 1, '2025-03-26 15:57:57', '2025-03-26 15:58:42'),
(44, NULL, 1, '2025-03-26 15:58:34', '2025-03-26 15:58:42'),
(45, NULL, 1, '2025-03-28 09:51:14', '2025-03-28 13:01:26'),
(46, NULL, 1, '2025-03-28 10:06:38', '2025-03-28 13:01:26'),
(47, NULL, 1, '2025-03-28 12:13:41', '2025-03-28 13:01:26'),
(48, NULL, 1, '2025-03-28 14:10:22', '2025-03-28 14:27:32'),
(49, NULL, 1, '2025-03-28 14:21:06', '2025-03-28 14:27:32'),
(50, NULL, 1, '2025-03-28 14:35:48', '2025-03-28 14:36:46'),
(51, NULL, 1, '2025-03-28 17:20:22', '2025-03-28 17:21:50'),
(52, NULL, 4, '2025-03-28 17:21:11', '2025-03-28 18:01:35'),
(53, NULL, 1, '2025-03-28 17:22:11', '2025-03-28 17:22:22'),
(54, NULL, 4, '2025-03-28 17:22:52', '2025-03-28 18:01:35'),
(55, NULL, 4, '2025-03-28 18:00:50', '2025-03-28 18:01:35'),
(56, NULL, 4, '2025-03-28 18:02:45', '2025-03-28 18:04:53'),
(57, NULL, 4, '2025-03-28 18:05:09', '2025-03-28 18:06:43'),
(58, NULL, 1, '2025-04-02 13:11:52', '2025-04-02 13:32:36'),
(59, NULL, 1, '2025-04-07 12:24:37', '2025-04-07 13:30:25'),
(60, NULL, 1, '2025-04-11 10:50:55', '2025-04-11 10:57:10'),
(61, NULL, 1, '2025-05-06 15:21:24', '2025-05-06 17:58:50'),
(62, NULL, 1, '2025-05-14 12:43:51', '2025-05-14 13:36:20'),
(63, NULL, 1, '2025-05-14 13:36:37', '2025-05-14 13:38:09'),
(64, NULL, 1, '2025-05-14 13:37:13', '2025-05-14 13:38:09'),
(65, NULL, 1, '2025-05-16 12:30:51', '2025-05-16 12:38:23');

-- --------------------------------------------------------

--
-- Structure de la table `billings`
--

CREATE TABLE `billings` (
  `id` int(11) NOT NULL,
  `ref` varchar(50) NOT NULL,
  `amount` double NOT NULL,
  `discount` double NOT NULL DEFAULT 0,
  `stock_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL,
  `delayed_at` timestamp NULL DEFAULT (`created_at` + interval 48 hour),
  `updated_at` timestamp NOT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf32 COLLATE=utf32_general_ci ROW_FORMAT=DYNAMIC;

--
-- Déchargement des données de la table `billings`
--

INSERT INTO `billings` (`id`, `ref`, `amount`, `discount`, `stock_id`, `customer_id`, `created_at`, `delayed_at`, `updated_at`, `deleted_at`) VALUES
(6, 'F-20250001', 750, 0, 19, 4, '2025-03-14 15:15:23', '2025-03-16 15:15:23', '2025-03-14 15:15:23', NULL),
(7, 'F-2025-0007-5911', 1050, 0, 22, 4, '2025-03-25 13:50:09', '2025-03-27 13:50:09', '2025-03-25 13:50:09', NULL),
(8, 'F-2025-0001-2221', 120000, 0, 25, 14, '2025-03-28 11:02:12', '2025-03-30 11:02:12', '2025-03-28 11:02:12', NULL),
(9, 'F-2025-0001-8193', 60000, 0, 26, 4, '2025-03-28 11:03:47', '2025-03-30 11:03:47', '2025-03-28 11:03:47', NULL),
(10, 'F-2025-0007-5029', 119880, 0, 27, 4, '2025-03-28 11:32:06', '2025-03-30 11:32:06', '2025-03-28 11:32:06', NULL),
(11, 'F-2025-0009-2094', 120000, 0, 28, 4, '2025-03-28 11:35:48', '2025-03-30 11:35:48', '2025-03-28 11:35:48', NULL),
(12, 'F-2025-0008-5094', 9000, 0, 29, 15, '2025-03-28 11:37:56', '2025-03-30 11:37:56', '2025-03-28 11:37:56', NULL);

-- --------------------------------------------------------

--
-- Structure de la table `capacities`
--

CREATE TABLE `capacities` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL,
  `updated_at` timestamp NOT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf32 COLLATE=utf32_general_ci ROW_FORMAT=DYNAMIC;

--
-- Déchargement des données de la table `capacities`
--

INSERT INTO `capacities` (`id`, `name`, `created_at`, `updated_at`, `deleted_at`) VALUES
(6, 'Sac en Jute', '2025-03-12 14:04:11', '2025-03-18 16:33:33', NULL),
(7, 'Bassine, Seau', '2025-03-12 14:04:58', '2025-03-18 16:32:39', NULL),
(8, 'Panier', '2025-03-12 14:05:07', '2025-03-12 14:05:07', NULL),
(9, 'Ballot', '2025-03-12 14:05:18', '2025-03-12 14:05:18', NULL),
(10, 'Barrique ou Fût en Plastique', '2025-03-12 14:05:35', '2025-03-18 16:32:15', NULL),
(11, 'Barrique ou Fût en Métal', '2025-03-12 14:06:46', '2025-03-18 16:32:25', NULL),
(12, 'Conteneur', '2025-03-12 16:55:04', '2025-03-12 16:55:04', NULL),
(13, '1/2 Conteneur', '2025-03-12 16:55:16', '2025-03-12 16:55:16', NULL),
(14, 'Bidon', '2025-03-18 16:33:13', '2025-03-18 16:33:13', NULL),
(15, 'Sac en Plastique', '2025-03-18 16:33:45', '2025-03-18 16:33:45', NULL),
(16, 'Palette', '2025-03-18 16:34:17', '2025-03-18 16:34:17', NULL),
(17, 'Filet', '2025-03-18 16:34:38', '2025-03-18 16:34:38', NULL),
(18, 'Caisse en Bois', '2025-03-18 16:35:17', '2025-03-18 16:35:17', NULL),
(19, 'Carton', '2025-03-18 16:35:22', '2025-03-18 16:35:22', NULL);

-- --------------------------------------------------------

--
-- Structure de la table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(90) NOT NULL,
  `created_at` timestamp NOT NULL,
  `updated_at` timestamp NOT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf32 COLLATE=utf32_general_ci ROW_FORMAT=DYNAMIC;

--
-- Déchargement des données de la table `categories`
--

INSERT INTO `categories` (`id`, `name`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'Legumes', '2025-03-06 17:50:16', '2025-03-06 17:50:17', NULL),
(2, 'Fruits', '2025-03-06 17:50:36', '2025-03-06 17:50:37', NULL),
(3, 'Oléagineux', '2025-03-10 09:33:49', '2025-03-19 10:22:25', NULL),
(4, 'Tubercules', '2025-03-19 10:22:33', '2025-03-19 10:22:33', NULL),
(5, 'Céréales', '2025-03-19 10:22:43', '2025-03-19 10:22:43', NULL),
(6, 'Légumineuses', '2025-03-19 10:22:54', '2025-03-19 10:22:54', NULL),
(7, 'Cultures industrielles / Plantes à fibres ou à latex', '2025-03-19 10:23:56', '2025-03-19 10:23:56', NULL);

-- --------------------------------------------------------

--
-- Structure de la table `cities`
--

CREATE TABLE `cities` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL,
  `updated_at` timestamp NOT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf32 COLLATE=utf32_general_ci ROW_FORMAT=DYNAMIC;

--
-- Déchargement des données de la table `cities`
--

INSERT INTO `cities` (`id`, `name`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'Abidjan', '2025-03-06 17:58:05', '2025-03-06 17:58:06', NULL),
(2, 'Yamoussoukro', '2025-03-06 17:58:32', '2025-03-06 17:58:33', NULL);

-- --------------------------------------------------------

--
-- Structure de la table `claims`
--

CREATE TABLE `claims` (
  `id` int(11) NOT NULL,
  `name` set('ENTREE STOCK','SORTIE STOCK','REQUÊTE DE TRI','CONDITIONNEMENT SPECIAL','CONDITIONNEMENT GENERAL','LIVRAISON AVEC ENCAISSEMENT','LIVRAISON SANS ENCAISSEMENT','RENOUVELLEMENT DUREE STOCK','AUTRES') NOT NULL DEFAULT 'ENTREE STOCK',
  `message` text NOT NULL,
  `status` set('EN COURS','TRAITEE','NON TRAITEE','DISPUTEE') NOT NULL DEFAULT 'EN COURS',
  `customer_id` int(11) NOT NULL COMMENT 'CUSTOMER',
  `storage_id` int(11) NOT NULL COMMENT 'STORAGE',
  `created_at` timestamp NOT NULL,
  `updated_at` timestamp NOT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf32 COLLATE=utf32_general_ci ROW_FORMAT=DYNAMIC;

--
-- Déchargement des données de la table `claims`
--

INSERT INTO `claims` (`id`, `name`, `message`, `status`, `customer_id`, `storage_id`, `created_at`, `updated_at`, `deleted_at`) VALUES
(5, 'ENTREE STOCK', 'Je vous fais une demande d\'entreé de stock dans le but qu\'elle soit entièrement pris en charge', 'TRAITEE', 14, 1, '2025-03-21 15:33:23', '2025-03-21 16:20:48', NULL),
(6, 'ENTREE STOCK', 'Je vous fais une réclamation d\'entrée de sortie', 'TRAITEE', 14, 1, '2025-03-21 15:48:57', '2025-03-21 16:20:51', NULL),
(7, 'ENTREE STOCK', 'Je vous fais une réclamation d\'entrée de sortie', 'TRAITEE', 14, 1, '2025-03-21 15:51:34', '2025-03-21 16:20:56', NULL),
(8, 'ENTREE STOCK', 'Je vous fais une réclamation d\'entrée de sortie', 'TRAITEE', 14, 1, '2025-03-21 15:53:36', '2025-03-21 16:21:13', NULL),
(9, 'RENOUVELLEMENT DUREE STOCK', 'Je souhaite renouveler la durée de mon Stock', 'EN COURS', 4, 1, '2025-03-28 18:01:26', '2025-03-28 18:01:26', NULL),
(10, 'ENTREE STOCK', 'Je souhaite faire une entrée de Stock', 'EN COURS', 4, 1, '2025-03-28 18:04:36', '2025-03-28 18:04:36', NULL),
(11, 'ENTREE STOCK', 'Je souhaite faire une entrée de Stock', 'EN COURS', 4, 1, '2025-03-28 18:04:37', '2025-03-28 18:04:37', NULL),
(12, 'ENTREE STOCK', 'Entrée de Stock', 'EN COURS', 4, 1, '2025-03-28 18:06:17', '2025-03-28 18:06:17', NULL);

-- --------------------------------------------------------

--
-- Structure de la table `details`
--

CREATE TABLE `details` (
  `id` int(11) NOT NULL,
  `qty` double NOT NULL,
  `container_id` int(11) NOT NULL DEFAULT 1,
  `stock_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL,
  `updated_at` timestamp NOT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf32 COLLATE=utf32_general_ci ROW_FORMAT=DYNAMIC;

--
-- Déchargement des données de la table `details`
--

INSERT INTO `details` (`id`, `qty`, `container_id`, `stock_id`, `product_id`, `created_at`, `updated_at`, `deleted_at`) VALUES
(35, 0, 6, 19, 6, '2025-03-14 15:16:06', '2025-03-14 15:18:42', NULL),
(36, 50, 6, 19, 7, '2025-03-14 15:16:06', '2025-03-14 15:16:06', NULL),
(37, 70, 6, 19, 5, '2025-03-14 15:16:06', '2025-03-14 15:16:06', NULL),
(44, 20, 6, 22, 5, '2025-03-25 14:02:31', '2025-03-25 14:02:31', NULL),
(51, 10000, 6, 25, 5, '2025-03-28 11:02:12', '2025-03-28 11:02:12', NULL),
(52, 10000, 6, 25, 7, '2025-03-28 11:02:12', '2025-03-28 11:02:12', NULL),
(53, 5000, 6, 26, 5, '2025-03-28 11:03:47', '2025-03-28 11:03:47', NULL),
(54, 3000, 6, 26, 16, '2025-03-28 11:03:47', '2025-03-28 11:03:47', NULL),
(55, 2000, 6, 26, 11, '2025-03-28 11:03:47', '2025-03-28 11:03:47', NULL),
(56, 1980, 16, 27, 13, '2025-03-28 11:32:06', '2025-03-28 11:32:06', NULL),
(57, 18000, 18, 27, 8, '2025-03-28 11:32:06', '2025-03-28 11:32:06', NULL),
(58, 20000, 13, 28, 10, '2025-03-28 11:35:48', '2025-03-28 11:35:48', NULL),
(59, 1500, 6, 29, 5, '2025-03-28 11:37:56', '2025-03-28 11:37:56', NULL);

-- --------------------------------------------------------

--
-- Structure de la table `groups`
--

CREATE TABLE `groups` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL,
  `updated_at` timestamp NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf32 COLLATE=utf32_general_ci ROW_FORMAT=DYNAMIC;

--
-- Déchargement des données de la table `groups`
--

INSERT INTO `groups` (`id`, `name`, `created_at`, `updated_at`) VALUES
(1, 'Admin', '2024-08-19 17:54:28', '2024-08-19 17:54:29'),
(2, 'Superviseur', '2024-08-23 15:35:03', '2024-08-23 15:35:04'),
(3, 'Comptable', '2025-03-05 10:35:42', '2025-03-05 10:35:43'),
(4, 'Caissière', '2025-03-05 10:36:00', '2025-03-05 10:36:01'),
(5, 'Coopérative Agricole', '2025-03-06 12:23:18', '2025-03-06 12:23:19'),
(6, 'Coopératives de Pêche', '2025-03-06 12:23:18', '2025-03-06 12:23:19'),
(7, 'Grossiste', '2025-03-06 12:23:18', '2025-03-06 12:23:19'),
(8, 'Entreprises Agroalimentaire', '2025-03-06 12:23:18', '2025-03-06 12:23:19'),
(10, 'Particulier', '2025-03-26 15:46:11', '2025-03-26 15:46:11');

-- --------------------------------------------------------

--
-- Structure de la table `incidents`
--

CREATE TABLE `incidents` (
  `id` int(11) NOT NULL,
  `type` set('INCIDENT SPECIFIQUE','INCIDENT GENERAL') NOT NULL DEFAULT 'INCIDENT GENERAL',
  `description` text NOT NULL,
  `status` set('EN COURS','RESOLU','NON RESOLU') NOT NULL DEFAULT 'EN COURS',
  `created_at` timestamp NOT NULL,
  `updated_at` timestamp NOT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf32 COLLATE=utf32_general_ci ROW_FORMAT=DYNAMIC;

--
-- Déchargement des données de la table `incidents`
--

INSERT INTO `incidents` (`id`, `type`, `description`, `status`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'INCIDENT SPECIFIQUE', 'Lorem ipsum dolor sit amet consectetur adipisicing elit. Placeat optio at vitae unde dolorem excepturi quidem, rerum dicta delectus, dolore in qui assumenda ducimus, numquam quas. Quae sequi est excepturi.', 'EN COURS', '2025-03-18 15:20:03', '2025-03-18 15:31:24', NULL),
(2, 'INCIDENT SPECIFIQUE', 'Lorem ipsum dolor sit amet consectetur adipisicing elit. Placeat optio at vitae unde dolorem excepturi quidem, rerum dicta delectus, dolore in qui assumenda ducimus, numquam quas. Quae sequi est excepturi.', 'EN COURS', '2025-03-18 15:20:03', '2025-03-18 15:31:24', NULL),
(3, 'INCIDENT SPECIFIQUE', 'Lorem ipsum dolor sit amet consectetur adipisicing elit. Placeat optio at vitae unde dolorem excepturi quidem, rerum dicta delectus, dolore in qui assumenda ducimus, numquam quas. Quae sequi est excepturi.', 'EN COURS', '2025-03-18 15:20:03', '2025-03-18 15:31:24', NULL),
(4, 'INCIDENT SPECIFIQUE', 'Lorem ipsum dolor sit amet consectetur adipisicing elit. Placeat optio at vitae unde dolorem excepturi quidem, rerum dicta delectus, dolore in qui assumenda ducimus, numquam quas. Quae sequi est excepturi.', 'EN COURS', '2025-03-18 15:20:03', '2025-03-18 15:31:24', NULL),
(5, 'INCIDENT SPECIFIQUE', 'Lorem ipsum dolor sit amet consectetur adipisicing elit. Placeat optio at vitae unde dolorem excepturi quidem, rerum dicta delectus, dolore in qui assumenda ducimus, numquam quas. Quae sequi est excepturi.', 'EN COURS', '2025-03-18 15:20:03', '2025-03-18 15:31:24', NULL);

-- --------------------------------------------------------

--
-- Structure de la table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `location` varchar(150) NOT NULL,
  `amount` double NOT NULL,
  `method` set('CASH','MOBILE MONEY','CREDIT CARD','BANK TRANSFER') NOT NULL DEFAULT 'CASH',
  `description` text DEFAULT NULL,
  `billing_id` int(11) NOT NULL,
  `stock_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL,
  `updated_at` timestamp NOT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf32 COLLATE=utf32_general_ci ROW_FORMAT=DYNAMIC;

--
-- Déchargement des données de la table `payments`
--

INSERT INTO `payments` (`id`, `location`, `amount`, `method`, `description`, `billing_id`, `stock_id`, `customer_id`, `created_by`, `created_at`, `updated_at`, `deleted_at`) VALUES
(6, 'Bureau Abidjan', 5000, 'CASH', NULL, 12, 29, 15, 1, '2025-03-28 14:18:10', '2025-03-28 14:18:10', NULL),
(7, 'Bureau Abidjan', 5000, 'CASH', NULL, 12, 29, 15, 1, '2025-03-28 14:18:23', '2025-03-28 14:20:50', '2025-03-28 14:20:50'),
(8, 'Bureau Abidjan', 50000, 'CASH', NULL, 11, 28, 4, 1, '2025-03-28 14:19:48', '2025-03-28 14:19:48', NULL),
(9, 'Bureau Abidjan', 4000, 'MOBILE MONEY', NULL, 12, 29, 15, 1, '2025-03-28 14:31:41', '2025-03-28 14:31:41', NULL);

-- --------------------------------------------------------

--
-- Structure de la table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `category_id` int(11) NOT NULL,
  `min_expired_at` int(11) NOT NULL COMMENT 'En jour',
  `max_expired_at` int(11) DEFAULT NULL COMMENT 'En Jour',
  `created_at` timestamp NOT NULL,
  `updated_at` timestamp NOT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf32 COLLATE=utf32_general_ci ROW_FORMAT=DYNAMIC;

--
-- Déchargement des données de la table `products`
--

INSERT INTO `products` (`id`, `name`, `category_id`, `min_expired_at`, `max_expired_at`, `created_at`, `updated_at`, `deleted_at`) VALUES
(5, 'Banane', 2, 7, NULL, '2025-03-10 10:36:47', '2025-03-10 10:36:47', NULL),
(6, 'Mangue', 1, 10, NULL, '2025-03-10 10:39:22', '2025-03-10 17:26:33', NULL),
(7, 'Orange', 1, 18, NULL, '2025-03-10 10:39:41', '2025-03-10 10:40:39', NULL),
(8, 'Tomate', 1, 5, 10, '2025-03-19 10:48:21', '2025-03-19 10:48:21', NULL),
(9, 'Piment', 1, 7, 14, '2025-03-19 10:49:03', '2025-03-19 10:49:03', NULL),
(10, 'Aubergine', 1, 7, 10, '2025-03-19 10:49:19', '2025-03-19 10:49:19', NULL),
(11, 'Gombo', 1, 5, 7, '2025-03-19 10:49:33', '2025-03-19 10:49:33', NULL),
(12, 'Poivron', 1, 7, 14, '2025-03-19 10:49:56', '2025-03-19 10:49:56', NULL),
(13, 'Epinards (Amarante)', 1, 3, 5, '2025-03-19 10:50:40', '2025-03-19 10:50:40', NULL),
(14, 'Feuilles de Manioc', 1, 2, 3, '2025-03-19 10:51:57', '2025-03-19 10:51:57', NULL),
(15, 'Feuilles de Patate Douce', 1, 2, 4, '2025-03-19 10:52:23', '2025-03-19 10:52:23', NULL),
(16, 'Oignon', 1, 30, 90, '2025-03-19 10:53:46', '2025-03-19 10:53:46', NULL),
(17, 'Huile de Palme', 3, 180, 365, '2025-03-19 10:54:28', '2025-03-19 10:54:28', NULL),
(18, 'Huile de Palmiste', 3, 365, NULL, '2025-03-19 10:55:05', '2025-03-19 10:55:05', NULL),
(19, 'Arachide (Non Transformée)', 3, 90, 180, '2025-03-19 10:56:57', '2025-03-19 10:56:57', NULL),
(20, 'Noix de Cajou', 3, 180, 365, '2025-03-19 10:57:24', '2025-03-19 10:57:24', NULL),
(21, 'Graine de Coton', 3, 180, 365, '2025-03-19 10:57:49', '2025-03-19 10:57:49', NULL),
(22, 'Igname', 4, 30, 90, '2025-03-19 10:59:43', '2025-03-19 10:59:43', NULL),
(23, 'Manioc Frais', 4, 7, 14, '2025-03-19 11:00:06', '2025-03-19 11:00:06', NULL),
(24, 'Attieké Transformé', 4, 3, 5, '2025-03-19 11:00:46', '2025-03-19 11:00:46', NULL),
(25, 'Gari', 4, 180, 365, '2025-03-19 11:01:09', '2025-03-19 11:01:09', NULL),
(26, 'Placali', 4, 3, 5, '2025-03-19 11:01:26', '2025-03-19 11:01:26', NULL),
(27, 'Taro', 4, 30, 60, '2025-03-19 11:01:45', '2025-03-19 11:01:45', NULL),
(28, 'Patate Douce', 4, 30, 60, '2025-03-19 11:02:15', '2025-03-19 11:02:15', NULL),
(29, 'Pomme de terre', 4, 30, 90, '2025-03-19 11:02:51', '2025-03-19 11:02:51', NULL),
(30, 'Riz (Non Cuit)', 5, 365, 730, '2025-03-19 11:03:50', '2025-03-19 11:03:50', NULL),
(31, 'Maïs (Grain Sec)', 5, 365, NULL, '2025-03-19 11:04:30', '2025-03-19 11:04:30', NULL),
(32, 'Mil', 5, 365, 730, '2025-03-19 11:04:58', '2025-03-19 11:04:58', NULL),
(33, 'Sorgho', 5, 365, 730, '2025-03-19 11:05:24', '2025-03-19 11:05:24', NULL),
(34, 'Arachide (décortiquée)', 6, 90, 180, '2025-03-19 11:06:07', '2025-03-19 11:06:07', NULL),
(35, 'Niébé (haricot à œil noir)', 6, 180, 365, '2025-03-19 11:06:28', '2025-03-19 11:06:28', NULL),
(36, 'Cacao (fèves sèches)', 7, 365, 730, '2025-03-19 11:06:56', '2025-03-19 11:06:56', NULL),
(37, 'Café (grains)', 7, 365, 730, '2025-03-19 11:07:20', '2025-03-19 11:07:20', NULL),
(38, 'Hévéa (latex)', 7, 7, 14, '2025-03-19 11:07:37', '2025-03-19 11:07:37', NULL),
(39, 'Coton (fibres)', 7, 365, NULL, '2025-03-19 11:08:07', '2025-03-19 11:08:07', NULL),
(40, 'Canne à sucre (fraîche)', 7, 7, 14, '2025-03-19 11:08:27', '2025-03-19 11:08:27', NULL),
(41, 'Ananas', 2, 5, 7, '2025-03-24 15:35:25', '2025-03-24 15:35:25', NULL),
(42, 'Papaye', 2, 7, 14, '2025-03-24 15:35:48', '2025-03-24 15:35:48', NULL),
(43, 'Goyave', 2, 7, NULL, '2025-03-24 15:36:31', '2025-03-24 15:36:31', NULL),
(44, 'Fruit du dragon (Pitaya)', 2, 7, NULL, '2025-03-24 15:36:59', '2025-03-24 15:36:59', NULL),
(45, 'Jacquier', 2, 7, NULL, '2025-03-24 15:37:14', '2025-03-24 15:37:14', NULL),
(46, 'Durian', 2, 7, NULL, '2025-03-24 15:37:32', '2025-03-24 15:37:32', NULL),
(47, 'Noix de coco', 2, 7, NULL, '2025-03-24 15:37:54', '2025-03-24 15:37:54', NULL),
(48, 'Tamarin', 2, 7, NULL, '2025-03-24 15:40:41', '2025-03-24 15:40:41', NULL),
(49, 'Cherimoya (Pomme cannelle)', 2, 7, NULL, '2025-03-24 15:40:55', '2025-03-24 15:40:55', NULL),
(50, 'Longan', 2, 7, NULL, '2025-03-24 15:41:13', '2025-03-24 15:41:13', NULL),
(51, 'Fruit à pain', 2, 7, NULL, '2025-03-24 15:41:31', '2025-03-24 15:41:31', NULL);

-- --------------------------------------------------------

--
-- Structure de la table `releases`
--

CREATE TABLE `releases` (
  `id` int(11) NOT NULL,
  `before_qty` double NOT NULL,
  `qty` double NOT NULL,
  `after_qty` double NOT NULL,
  `delivery` set('Cool AgriTransport','Tierce','Client') NOT NULL DEFAULT 'Client',
  `detail_id` int(11) NOT NULL,
  `stock_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL,
  `updated_at` timestamp NOT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf32 COLLATE=utf32_general_ci;

--
-- Déchargement des données de la table `releases`
--

INSERT INTO `releases` (`id`, `before_qty`, `qty`, `after_qty`, `delivery`, `detail_id`, `stock_id`, `created_at`, `updated_at`, `deleted_at`) VALUES
(2, 30, 30, 0, 'Cool AgriTransport', 35, 19, '2025-03-14 15:18:42', '2025-03-14 15:18:42', NULL);

-- --------------------------------------------------------

--
-- Structure de la table `rottens`
--

CREATE TABLE `rottens` (
  `id` int(11) NOT NULL,
  `before_qty` double NOT NULL,
  `qty` double NOT NULL,
  `after_qty` double NOT NULL,
  `detail_id` int(11) NOT NULL,
  `stock_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL,
  `updated_at` timestamp NOT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf32 COLLATE=utf32_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `stocks`
--

CREATE TABLE `stocks` (
  `id` int(11) NOT NULL,
  `ref` varchar(50) NOT NULL,
  `type_storage` set('STOCKAGE SEC','STOCKAGE REFRIGERE') NOT NULL DEFAULT 'STOCKAGE SEC',
  `qty` double NOT NULL COMMENT 'en kg',
  `storage_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `created_by` int(11) NOT NULL DEFAULT 13 COMMENT 'User created stock',
  `expired_at` int(11) NOT NULL COMMENT 'Nombre de Jours',
  `created_at` timestamp NOT NULL,
  `updated_at` timestamp NOT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf32 COLLATE=utf32_general_ci ROW_FORMAT=DYNAMIC;

--
-- Déchargement des données de la table `stocks`
--

INSERT INTO `stocks` (`id`, `ref`, `type_storage`, `qty`, `storage_id`, `customer_id`, `created_by`, `expired_at`, `created_at`, `updated_at`, `deleted_at`) VALUES
(19, 'S-20250001', 'STOCKAGE SEC', 120, 1, 4, 0, 5, '2025-03-14 15:15:23', '2025-03-14 15:18:42', NULL),
(22, '2025-0007-5911', 'STOCKAGE REFRIGERE', 20, 7, 4, 4, 7, '2025-03-25 13:50:09', '2025-03-25 14:02:31', NULL),
(25, '2025-0001-2221', 'STOCKAGE SEC', 20000, 1, 14, 1, 4, '2025-03-28 11:02:12', '2025-03-28 11:02:12', NULL),
(26, '2025-0001-8193', 'STOCKAGE SEC', 10000, 1, 4, 1, 7, '2025-03-28 11:03:47', '2025-03-28 11:03:47', NULL),
(27, '2025-0007-5029', 'STOCKAGE SEC', 19980, 7, 4, 1, 7, '2025-03-28 11:32:06', '2025-03-28 11:32:06', NULL),
(28, '2025-0009-2094', 'STOCKAGE SEC', 20000, 9, 4, 1, 7, '2025-03-28 11:35:48', '2025-03-28 11:35:48', NULL),
(29, '2025-0008-5094', 'STOCKAGE REFRIGERE', 1500, 8, 15, 1, 7, '2025-03-28 11:37:56', '2025-03-28 11:37:56', NULL);

-- --------------------------------------------------------

--
-- Structure de la table `storages`
--

CREATE TABLE `storages` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `capacity` double NOT NULL,
  `location` varchar(150) NOT NULL,
  `created_at` timestamp NOT NULL,
  `updated_at` timestamp NOT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf32 COLLATE=utf32_general_ci ROW_FORMAT=DYNAMIC;

--
-- Déchargement des données de la table `storages`
--

INSERT INTO `storages` (`id`, `name`, `capacity`, `location`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'Espace de Stockage à Sec', 30000, 'Abidjan', '2025-03-12 14:19:05', '2025-03-13 12:35:47', NULL),
(7, 'Conteneur Frigorifique 20 Pieds', 20000, 'Abidjan', '2025-03-12 14:30:46', '2025-03-13 12:35:07', NULL),
(8, 'Camion Frigorifique 20 Pieds Marque Hyundai H100', 1500, 'Abidjan', '2025-03-12 14:32:17', '2025-03-13 12:35:19', NULL),
(9, 'Stockage dans un conteneur frigorifique', 20000, 'Abidjan', '2025-03-12 16:19:16', '2025-03-13 12:35:59', NULL);

-- --------------------------------------------------------

--
-- Structure de la table `tariffs`
--

CREATE TABLE `tariffs` (
  `id` int(11) NOT NULL,
  `price` double NOT NULL,
  `min_qty` double NOT NULL,
  `max_qty` double NOT NULL,
  `duration` int(11) NOT NULL COMMENT 'Durée en Heure',
  `storage_id` int(11) NOT NULL,
  `container_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL,
  `updated_at` timestamp NOT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf32 COLLATE=utf32_general_ci ROW_FORMAT=DYNAMIC;

--
-- Déchargement des données de la table `tariffs`
--

INSERT INTO `tariffs` (`id`, `price`, `min_qty`, `max_qty`, `duration`, `storage_id`, `container_id`, `created_at`, `updated_at`, `deleted_at`) VALUES
(6, 150, 1, 1000, 24, 1, 6, '2025-03-12 16:09:04', '2025-03-12 16:09:04', NULL),
(7, 150, 1, 1000, 24, 1, 7, '2025-03-12 16:11:49', '2025-03-12 16:11:49', NULL),
(8, 150, 1, 1000, 24, 1, 8, '2025-03-12 16:12:33', '2025-03-12 16:12:33', NULL),
(9, 150, 1, 1000, 24, 1, 9, '2025-03-12 16:12:52', '2025-03-12 16:12:52', NULL),
(10, 1000, 1, 1000, 24, 1, 10, '2025-03-12 16:16:45', '2025-03-12 16:16:45', NULL),
(11, 1000, 1, 1000, 24, 1, 11, '2025-03-12 16:17:11', '2025-03-12 16:17:11', NULL),
(12, 250, 1, 1000, 24, 9, 6, '2025-03-12 16:33:05', '2025-03-12 16:33:05', NULL),
(13, 250, 1, 1000, 24, 9, 7, '2025-03-12 16:33:40', '2025-03-12 16:33:40', NULL),
(14, 250, 1, 1000, 24, 9, 8, '2025-03-12 16:52:44', '2025-03-12 16:52:44', NULL),
(15, 250, 1, 1000, 24, 9, 9, '2025-03-12 16:53:00', '2025-03-12 16:53:00', NULL),
(16, 2000, 1, 1000, 24, 9, 10, '2025-03-12 16:53:27', '2025-03-12 16:53:27', NULL),
(17, 2000, 1, 1000, 24, 9, 11, '2025-03-12 16:53:43', '2025-03-12 16:53:43', NULL),
(18, 80000, 1000, 20000, 24, 7, 12, '2025-03-12 16:56:46', '2025-03-12 16:56:46', NULL),
(19, 50000, 1000, 20000, 24, 7, 13, '2025-03-12 16:57:21', '2025-03-12 16:57:21', NULL),
(20, 100000, 1500, 1500, 24, 8, 12, '2025-03-12 16:59:01', '2025-03-12 16:59:01', NULL),
(21, 150, 1, 1000, 24, 7, 6, '2025-03-12 16:09:04', '2025-03-12 16:09:04', NULL),
(22, 150, 1, 1000, 24, 7, 7, '2025-03-12 16:11:49', '2025-03-12 16:11:49', NULL),
(23, 150, 1, 1000, 24, 7, 8, '2025-03-12 16:12:33', '2025-03-12 16:12:33', NULL),
(24, 150, 1, 1000, 24, 7, 9, '2025-03-12 16:12:52', '2025-03-12 16:12:52', NULL),
(25, 1000, 1, 1000, 24, 7, 10, '2025-03-12 16:16:45', '2025-03-12 16:16:45', NULL),
(26, 1000, 1, 1000, 24, 7, 11, '2025-03-12 16:17:11', '2025-03-12 16:17:11', NULL),
(27, 150, 1, 1000, 24, 8, 6, '2025-03-12 16:09:04', '2025-03-12 16:09:04', NULL),
(28, 150, 1, 1000, 24, 8, 7, '2025-03-12 16:11:49', '2025-03-12 16:11:49', NULL),
(29, 150, 1, 1000, 24, 8, 8, '2025-03-12 16:12:33', '2025-03-12 16:12:33', NULL),
(30, 150, 1, 1000, 24, 8, 9, '2025-03-12 16:12:52', '2025-03-12 16:12:52', NULL),
(31, 1000, 1, 1000, 24, 8, 10, '2025-03-12 16:16:45', '2025-03-12 16:16:45', NULL),
(32, 1000, 1, 1000, 24, 8, 11, '2025-03-12 16:17:11', '2025-03-12 16:17:11', NULL),
(33, 150, 1, 1000, 24, 9, 6, '2025-03-12 16:09:04', '2025-03-12 16:09:04', NULL),
(34, 150, 1, 1000, 24, 9, 7, '2025-03-12 16:11:49', '2025-03-12 16:11:49', NULL),
(35, 150, 1, 1000, 24, 9, 8, '2025-03-12 16:12:33', '2025-03-12 16:12:33', NULL),
(36, 150, 1, 1000, 24, 9, 9, '2025-03-12 16:12:52', '2025-03-12 16:12:52', NULL),
(37, 1000, 1, 1000, 24, 9, 10, '2025-03-12 16:16:45', '2025-03-12 16:16:45', NULL),
(38, 1000, 1, 1000, 24, 9, 11, '2025-03-12 16:17:11', '2025-03-12 16:17:11', NULL);

-- --------------------------------------------------------

--
-- Structure de la table `temperatures`
--

CREATE TABLE `temperatures` (
  `id` int(11) NOT NULL,
  `type_storage` set('STOCKAGE A SEC','STOCKAGE REFRIGERE') NOT NULL DEFAULT 'STOCKAGE A SEC',
  `session` set('PASSAGE 1','PASSAGE 2','PASSAGE 3') NOT NULL DEFAULT 'PASSAGE 1',
  `degree` float NOT NULL DEFAULT 37,
  `session_time` time NOT NULL,
  `storage_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL,
  `updated_at` timestamp NOT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf32 COLLATE=utf32_general_ci ROW_FORMAT=DYNAMIC;

--
-- Déchargement des données de la table `temperatures`
--

INSERT INTO `temperatures` (`id`, `type_storage`, `session`, `degree`, `session_time`, `storage_id`, `created_at`, `updated_at`, `deleted_at`) VALUES
(5, 'STOCKAGE A SEC', 'PASSAGE 1', 37, '00:00:00', 1, '2025-03-10 09:52:27', '2025-03-10 10:10:49', '2025-03-10 10:10:49'),
(6, 'STOCKAGE A SEC', 'PASSAGE 1', 17, '14:00:00', 7, '2025-03-10 10:10:41', '2025-03-18 16:28:51', NULL),
(7, 'STOCKAGE A SEC', 'PASSAGE 1', 27, '12:00:00', 1, '2025-03-18 10:41:55', '2025-03-18 10:41:55', NULL),
(8, 'STOCKAGE A SEC', 'PASSAGE 1', 37, '14:00:00', 1, '2025-03-24 14:09:01', '2025-03-24 14:09:17', NULL);

-- --------------------------------------------------------

--
-- Structure de la table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `phone` varchar(30) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `username` varchar(150) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `locale` char(4) NOT NULL DEFAULT 'fr',
  `group_id` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL,
  `updated_at` timestamp NOT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf32 COLLATE=utf32_general_ci ROW_FORMAT=DYNAMIC;

--
-- Déchargement des données de la table `users`
--

INSERT INTO `users` (`id`, `name`, `phone`, `email`, `username`, `password`, `email_verified_at`, `remember_token`, `locale`, `group_id`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'System Administrator', '0500000000', 'sysadmin@coolagristock.com', 'sysadmin', '$2y$12$l4Wi5hp9JPe.EdDrAy7YQOPiLMgEartZ74YbsoV0HIk/EHE08nxR6', '2024-04-20 07:39:43', 'kD7JMxx6HkJamtkGvn0uAoCSE6T8B0xMPwGj0bB730AjHwPnUm2peBDCqHEG', 'en', 1, '2024-04-13 08:18:00', '2025-03-17 10:11:04', NULL),
(4, 'Saha Agricole', '0778269777', 'saha@gmail.com', 'saha', '$2y$12$kNLol6.CZfh/6pblGJsSzOEtyW9mL14/MXZNjb64xQNEGNKGsHlx6', NULL, NULL, 'fr', 5, '2025-03-10 08:54:13', '2025-03-28 18:06:34', NULL),
(13, 'Operateur de saisie', '0797806347', 'supervisor@coolagristock.com', 'supervisor', '$2y$12$WHowvZuA8bDW/HVuP0PS.uAb0SHxrSxf19HjM1Wi2/iEs8LUin73a', NULL, NULL, 'fr', 2, '2025-03-13 10:52:14', '2025-03-13 10:52:14', NULL),
(14, 'Solidarité Agricole', '0108976890', 'Solidarite@gricole.com', 'Agricole', '$2y$12$oNzj5D5YNXs7VwD.cO/DL.LSfB22vH7RWEjxMS6binmvmfw7Vx7j2', NULL, NULL, 'fr', 5, '2025-03-13 11:43:06', '2025-03-13 16:59:32', NULL),
(15, 'Israel Atindekoun', '0594580339', 'atindekounisrael13@gmail.com', 'Louange11', '$2y$12$jush.xoVqC7GE27TZOv69OBqbsA6hZ07zqAikjixXryrLivFqci92', NULL, NULL, 'fr', 8, '2025-03-26 15:25:07', '2025-03-26 15:25:07', NULL),
(16, 'Armande', '0778250912', NULL, NULL, '$2y$12$8Hcgq74C1KDkgCeVq7DSn.a/Am/YSsTftdHIfQ5TpZH6Sroz/WrwS', NULL, NULL, 'fr', 10, '2025-03-26 15:48:44', '2025-03-26 15:48:44', NULL);

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `activities`
--
ALTER TABLE `activities`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_activities` (`user_id`);

--
-- Index pour la table `billings`
--
ALTER TABLE `billings`
  ADD PRIMARY KEY (`id`) USING BTREE,
  ADD KEY `stock_bill` (`stock_id`),
  ADD KEY `client_bill` (`customer_id`) USING BTREE;

--
-- Index pour la table `capacities`
--
ALTER TABLE `capacities`
  ADD PRIMARY KEY (`id`) USING BTREE;

--
-- Index pour la table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`) USING BTREE;

--
-- Index pour la table `cities`
--
ALTER TABLE `cities`
  ADD PRIMARY KEY (`id`) USING BTREE;

--
-- Index pour la table `claims`
--
ALTER TABLE `claims`
  ADD PRIMARY KEY (`id`) USING BTREE,
  ADD KEY `customer_claim` (`customer_id`),
  ADD KEY `storage_claim` (`storage_id`);

--
-- Index pour la table `details`
--
ALTER TABLE `details`
  ADD PRIMARY KEY (`id`) USING BTREE,
  ADD KEY `stock_details` (`stock_id`),
  ADD KEY `product_details` (`product_id`),
  ADD KEY `container_details` (`container_id`);

--
-- Index pour la table `groups`
--
ALTER TABLE `groups`
  ADD PRIMARY KEY (`id`) USING BTREE;

--
-- Index pour la table `incidents`
--
ALTER TABLE `incidents`
  ADD PRIMARY KEY (`id`) USING BTREE;

--
-- Index pour la table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`) USING BTREE,
  ADD KEY `stock_bill` (`stock_id`) USING BTREE,
  ADD KEY `client_bill` (`customer_id`) USING BTREE,
  ADD KEY `billing_payment` (`billing_id`) USING BTREE,
  ADD KEY `cashier_payment` (`created_by`);

--
-- Index pour la table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`) USING BTREE,
  ADD KEY `category_product` (`category_id`);

--
-- Index pour la table `releases`
--
ALTER TABLE `releases`
  ADD PRIMARY KEY (`id`),
  ADD KEY `detail_release` (`detail_id`),
  ADD KEY `stock_release` (`stock_id`);

--
-- Index pour la table `rottens`
--
ALTER TABLE `rottens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `stock_rotten` (`stock_id`),
  ADD KEY `rottens_details` (`detail_id`) USING BTREE;

--
-- Index pour la table `stocks`
--
ALTER TABLE `stocks`
  ADD PRIMARY KEY (`id`) USING BTREE,
  ADD KEY `storage_stock` (`storage_id`),
  ADD KEY `customer_stock` (`customer_id`);

--
-- Index pour la table `storages`
--
ALTER TABLE `storages`
  ADD PRIMARY KEY (`id`) USING BTREE;

--
-- Index pour la table `tariffs`
--
ALTER TABLE `tariffs`
  ADD PRIMARY KEY (`id`) USING BTREE,
  ADD KEY `capacity_tariff` (`container_id`) USING BTREE,
  ADD KEY `type_tariff` (`storage_id`) USING BTREE;

--
-- Index pour la table `temperatures`
--
ALTER TABLE `temperatures`
  ADD PRIMARY KEY (`id`) USING BTREE,
  ADD KEY `storage_temperature` (`storage_id`);

--
-- Index pour la table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`) USING BTREE,
  ADD KEY `user_group` (`group_id`) USING BTREE;

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `activities`
--
ALTER TABLE `activities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=66;

--
-- AUTO_INCREMENT pour la table `billings`
--
ALTER TABLE `billings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT pour la table `capacities`
--
ALTER TABLE `capacities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT pour la table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT pour la table `cities`
--
ALTER TABLE `cities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT pour la table `claims`
--
ALTER TABLE `claims`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT pour la table `details`
--
ALTER TABLE `details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=60;

--
-- AUTO_INCREMENT pour la table `groups`
--
ALTER TABLE `groups`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT pour la table `incidents`
--
ALTER TABLE `incidents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT pour la table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT pour la table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=52;

--
-- AUTO_INCREMENT pour la table `releases`
--
ALTER TABLE `releases`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `rottens`
--
ALTER TABLE `rottens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT pour la table `stocks`
--
ALTER TABLE `stocks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT pour la table `storages`
--
ALTER TABLE `storages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT pour la table `tariffs`
--
ALTER TABLE `tariffs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT pour la table `temperatures`
--
ALTER TABLE `temperatures`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT pour la table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `activities`
--
ALTER TABLE `activities`
  ADD CONSTRAINT `user_activities` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `billings`
--
ALTER TABLE `billings`
  ADD CONSTRAINT `customer_bill` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `stock_bill` FOREIGN KEY (`stock_id`) REFERENCES `stocks` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `claims`
--
ALTER TABLE `claims`
  ADD CONSTRAINT `customer_claim` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `storage_claim` FOREIGN KEY (`storage_id`) REFERENCES `storages` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `details`
--
ALTER TABLE `details`
  ADD CONSTRAINT `container_details` FOREIGN KEY (`container_id`) REFERENCES `capacities` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `product_details` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `stock_details` FOREIGN KEY (`stock_id`) REFERENCES `stocks` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `billing_payment` FOREIGN KEY (`billing_id`) REFERENCES `billings` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `cashier_payment` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `customer_payment` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `stock_payment` FOREIGN KEY (`stock_id`) REFERENCES `stocks` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `category_product` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `releases`
--
ALTER TABLE `releases`
  ADD CONSTRAINT `detail_release` FOREIGN KEY (`detail_id`) REFERENCES `details` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `stock_release` FOREIGN KEY (`stock_id`) REFERENCES `stocks` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `rottens`
--
ALTER TABLE `rottens`
  ADD CONSTRAINT `rottens_details` FOREIGN KEY (`detail_id`) REFERENCES `details` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `stock_rotten` FOREIGN KEY (`stock_id`) REFERENCES `stocks` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `stocks`
--
ALTER TABLE `stocks`
  ADD CONSTRAINT `customer_stock` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `storage_stock` FOREIGN KEY (`storage_id`) REFERENCES `storages` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `tariffs`
--
ALTER TABLE `tariffs`
  ADD CONSTRAINT `capacity_tariff` FOREIGN KEY (`container_id`) REFERENCES `capacities` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `type_tariff` FOREIGN KEY (`storage_id`) REFERENCES `storages` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `temperatures`
--
ALTER TABLE `temperatures`
  ADD CONSTRAINT `storage_temperature` FOREIGN KEY (`storage_id`) REFERENCES `storages` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`group_id`) REFERENCES `groups` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Mise à jour du mot de passe administrateur (ajoutée pour création d'un nouvel accès)
--
UPDATE `users`
SET `password` = '$2y$12$l4Wi5hp9JPe.EdDrAy7YQOPiLMgEartZ74YbsoV0HIk/EHE08nxR6',
    `username` = 'sysadmin',
    `name` = 'System Administrator',
    `phone` = '0500000000',
    `locale` = 'en',
    `deleted_at` = NULL
WHERE `email` = 'sysadmin@coolagristock.com';

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
