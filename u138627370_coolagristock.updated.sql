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

-- (data unchanged)

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

-- (data unchanged)

-- --------------------------------------------------------

-- (other tables unchanged until users)

-- --------------------------------------------------------

--
-- Structure de la table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `username` varchar(150) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `language` varchar(10) NOT NULL DEFAULT 'fr',
  `group_id` int(11) NOT NULL DEFAULT 4,
  `created_at` timestamp NOT NULL,
  `updated_at` timestamp NOT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf32 COLLATE=utf32_general_ci ROW_FORMAT=DYNAMIC;

--
-- Déchargement des données de la table `users`
--

INSERT INTO `users` (`id`, `name`, `phone`, `email`, `username`, `password`, `email_verified_at`, `remember_token`, `language`, `group_id`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'System Administrator', '0500000000', 'sysadmin@coolagristock.com', 'sysadmin', '$2y$12$l4Wi5hp9JPe.EdDrAy7YQOPiLMgEartZ74YbsoV0HIk/EHE08nxR6', '2024-04-20 07:39:43', 'kD7JMxx6HkJamtkGvn0uAoCSE6T8B0xMPwGj0bB730AjHwPnUm2peBDCqHEG', 'en', 1, '2024-04-13 08:18:00', '2025-03-17 10:11:04', NULL),
(4, 'Saha Agricole', '0778269777', 'saha@gmail.com', 'saha', '$2y$12$kNLol6.CZfh/6pblGJsSzOEtyW9mL14/MXZNjb64xQNEGNKGsHlx6', NULL, NULL, 'fr', 5, '2025-03-10 08:54:13', '2025-03-28 18:06:34', NULL),
(13, 'Operateur de saisie', '0797806347', 'supervisor@coolagristock.com', 'supervisor', '$2y$12$WHowvZuA8bDW/HVuP0PS.uAb0SHxrSxf19HjM1Wi2/iEs8LUin73a', NULL, NULL, 'fr', 2, '2025-03-13 10:52:14', '2025-03-13 10:52:14', NULL),
(14, 'Solidarité Agricole', '0108976890', 'Solidarite@gricole.com', 'Agricole', '$2y$12$oNzj5D5YNXs7VwD.cO/DL.LSfB22vH7RWEjxMS6binmvmfw7Vx7j2', NULL, NULL, 'fr', 5, '2025-03-13 11:43:06', '2025-03-13 16:59:32', NULL),
(15, 'Israel Atindekoun', '0594580339', 'atindekounisrael13@gmail.com', 'Louange11', '$2y$12$jush.xoVqC7GE27TZOv69OBqbsA6hZ07zqAikjixXryrLivFqci92', NULL, NULL, 'fr', 8, '2025-03-26 15:25:07', '2025-03-26 15:25:07', NULL),
(16, 'Armande', '0778250912', NULL, NULL, '$2y$12$8Hcgq74C1KDkgCeVq7DSn.a/Am/YSsTftdHIfQ5TpZH6Sroz/WrwS', NULL, NULL, 'fr', 10, '2025-03-26 15:48:44', '2025-03-26 15:48:44', NULL);

-- --------------------------------------------------------

-- Indexes and constraints. Keep existing index/constraint blocks but adapt any references to `locale` -> `language` if present.

-- Index pour les tables déchargées

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

-- (other index blocks unchanged)

-- Index pour la table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`) USING BTREE,
  ADD KEY `user_group` (`group_id`) USING BTREE;

-- Add a unique index for username as migrations expect
ALTER TABLE `users`
  ADD UNIQUE KEY `users_username_unique` (`username`);

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

-- (remaining AUTO_INCREMENT blocks unchanged)

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

-- (remaining constraints unchanged)

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
    `language` = 'en',
    `deleted_at` = NULL
WHERE `email` = 'sysadmin@coolagristock.com';

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
