-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : dim. 05 juil. 2026 à 03:36
-- Version du serveur : 10.4.32-MariaDB
-- Version de PHP : 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `reseau_social`
--

-- --------------------------------------------------------

--
-- Structure de la table `amis`
--

CREATE TABLE `amis` (
  `id` int(11) NOT NULL,
  `demandeur_id` int(11) NOT NULL,
  `receveur_id` int(11) NOT NULL,
  `statut` enum('en_attente','accepte','refuse') NOT NULL DEFAULT 'en_attente',
  `date_creation` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `amis`
--

INSERT INTO `amis` (`id`, `demandeur_id`, `receveur_id`, `statut`, `date_creation`) VALUES
(1, 5, 6, 'accepte', '2026-07-04 20:18:10');

-- --------------------------------------------------------

--
-- Structure de la table `commentaires`
--

CREATE TABLE `commentaires` (
  `id` int(11) NOT NULL,
  `publication_id` int(11) NOT NULL,
  `auteur_id` int(11) NOT NULL,
  `contenu` text NOT NULL,
  `date_creation` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `expediteur_id` int(11) NOT NULL,
  `destinataire_id` int(11) NOT NULL,
  `contenu` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `lu` tinyint(1) NOT NULL DEFAULT 0,
  `date_creation` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `messages`
--

INSERT INTO `messages` (`id`, `expediteur_id`, `destinataire_id`, `contenu`, `image`, `lu`, `date_creation`) VALUES
(1, 6, 5, 'cc', NULL, 1, '2026-07-04 23:12:38'),
(2, 6, 5, NULL, 'img_6a49855e6f714.png', 1, '2026-07-04 23:12:46'),
(3, 5, 6, NULL, 'img_6a498d49a53c5.png', 1, '2026-07-04 23:46:33'),
(4, 6, 5, NULL, 'img_6a4998670b822.png', 0, '2026-07-05 00:33:59'),
(5, 6, 5, 'Rengoku', NULL, 0, '2026-07-05 00:35:14'),
(6, 6, 5, NULL, 'img_6a4998cec53a4.png', 0, '2026-07-05 00:35:42');

-- --------------------------------------------------------

--
-- Structure de la table `publications`
--

CREATE TABLE `publications` (
  `id` int(11) NOT NULL,
  `auteur_id` int(11) NOT NULL,
  `contenu` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `date_creation` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `publications`
--

INSERT INTO `publications` (`id`, `auteur_id`, `contenu`, `image`, `date_creation`) VALUES
(1, 3, 'Bonjour tout le monde ! Je viens de rejoindre ce réseau social. Ravi de vous rencontrer ', NULL, '2026-07-04 19:01:22'),
(2, 4, 'Aujourd\'hui j\'ai appris AJAX en PHP. C\'est vraiment puissant pour éviter les rechargements de page !', NULL, '2026-07-04 19:01:22'),
(3, 3, 'Le développement web c\'est une aventure infinie. Chaque jour on apprend quelque chose de nouveau ', NULL, '2026-07-04 19:01:22'),
(4, 6, 'v', NULL, '2026-07-04 20:19:16'),
(5, 5, 'HI', 'img_6a498874cb122.png', '2026-07-04 23:25:56'),
(6, 5, 'hi', 'img_6a498a3202e1f.png', '2026-07-04 23:33:22'),
(7, 3, 'cc', NULL, '2026-07-05 01:10:12'),
(8, 3, 'rengoku', NULL, '2026-07-05 01:10:44'),
(9, 3, 'arcane', 'img_6a49a52aad7ed.jpeg', '2026-07-05 01:28:26');

-- --------------------------------------------------------

--
-- Structure de la table `reactions`
--

CREATE TABLE `reactions` (
  `id` int(11) NOT NULL,
  `publication_id` int(11) NOT NULL,
  `utilisateur_id` int(11) NOT NULL,
  `type` enum('like','dislike') NOT NULL,
  `date_creation` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `reactions`
--

INSERT INTO `reactions` (`id`, `publication_id`, `utilisateur_id`, `type`, `date_creation`) VALUES
(1, 6, 6, 'like', '2026-07-04 23:56:40'),
(2, 5, 6, 'like', '2026-07-04 23:56:44'),
(3, 4, 6, 'like', '2026-07-04 23:56:45');

-- --------------------------------------------------------

--
-- Structure de la table `utilisateurs`
--

CREATE TABLE `utilisateurs` (
  `id` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `prenom` varchar(100) NOT NULL,
  `email` varchar(191) NOT NULL,
  `mot_de_passe` varchar(255) NOT NULL,
  `photo_profil` varchar(255) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `role` enum('user','moderateur','admin') NOT NULL DEFAULT 'user',
  `date_creation` datetime NOT NULL DEFAULT current_timestamp(),
  `token` varchar(64) DEFAULT NULL,
  `token_expire` datetime DEFAULT NULL,
  `reset_token` varchar(64) DEFAULT NULL,
  `reset_expire` datetime DEFAULT NULL,
  `api_token` varchar(64) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `utilisateurs`
--

INSERT INTO `utilisateurs` (`id`, `nom`, `prenom`, `email`, `mot_de_passe`, `photo_profil`, `bio`, `role`, `date_creation`, `token`, `token_expire`, `reset_token`, `reset_expire`, `api_token`) VALUES
(1, 'Admin', 'Super', 'admin@reseau.com', '$2y$10$k5FFWhvEnljtakFN59iICuAJPb.IEz/MJDzFnUqZSSl9zh.puLz2y', NULL, 'Administrateur du réseau social.', 'admin', '2026-07-04 19:01:22', NULL, NULL, NULL, NULL, NULL),
(2, 'Dupont', 'Marie', 'marie@reseau.com', '$2y$10$k5FFWhvEnljtakFN59iICuAJPb.IEz/MJDzFnUqZSSl9zh.puLz2y', NULL, 'Modératrice passionnée de lecture.', 'moderateur', '2026-07-04 19:01:22', NULL, NULL, NULL, NULL, NULL),
(3, 'Martin', 'Lucas', 'lucas@reseau.com', '$2y$10$k5FFWhvEnljtakFN59iICuAJPb.IEz/MJDzFnUqZSSl9zh.puLz2y', NULL, 'Étudiant en informatique à ESGIS.', 'user', '2026-07-04 19:01:22', NULL, NULL, NULL, NULL, 'd1bfe0fb386692ca44efe37dbf5d2035fda1a7d388c17ce1a98d587136e51aea'),
(4, 'Konan', 'Awa', 'awa@reseau.com', '$2y$10$k5FFWhvEnljtakFN59iICuAJPb.IEz/MJDzFnUqZSSl9zh.puLz2y', NULL, 'Passionnée de développement web.', 'user', '2026-07-04 19:01:22', NULL, NULL, NULL, NULL, NULL),
(5, 'bob1', 'bob2', 'bob@email.com', '$2y$10$VFW4CZEKI52YNW.a9bcZf.LnsGM56533xPAI4VO/H.qj7v6j8xAua', NULL, NULL, 'user', '2026-07-04 19:16:05', NULL, NULL, 'a1943339280bbc9b6a69d0d2d2259352', '2026-07-05 02:03:39', NULL),
(6, 'fire', 'firefox', 'fire@email.com', '$2y$10$gVTMHZ5HwWOnPLcN5IE6u.4fb3l9lL7taQMi6HDWodRlnhOP/fsQK', 'img_6a499694c13c5.png', '', 'user', '2026-07-04 20:17:59', NULL, NULL, NULL, NULL, NULL);

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `amis`
--
ALTER TABLE `amis`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_ami` (`demandeur_id`,`receveur_id`),
  ADD KEY `receveur_id` (`receveur_id`);

--
-- Index pour la table `commentaires`
--
ALTER TABLE `commentaires`
  ADD PRIMARY KEY (`id`),
  ADD KEY `publication_id` (`publication_id`),
  ADD KEY `auteur_id` (`auteur_id`);

--
-- Index pour la table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `expediteur_id` (`expediteur_id`),
  ADD KEY `destinataire_id` (`destinataire_id`);

--
-- Index pour la table `publications`
--
ALTER TABLE `publications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `auteur_id` (`auteur_id`);

--
-- Index pour la table `reactions`
--
ALTER TABLE `reactions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_reaction` (`publication_id`,`utilisateur_id`),
  ADD KEY `utilisateur_id` (`utilisateur_id`);

--
-- Index pour la table `utilisateurs`
--
ALTER TABLE `utilisateurs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `api_token` (`api_token`),
  ADD KEY `idx_token` (`token`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `amis`
--
ALTER TABLE `amis`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `commentaires`
--
ALTER TABLE `commentaires`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT pour la table `publications`
--
ALTER TABLE `publications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT pour la table `reactions`
--
ALTER TABLE `reactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `utilisateurs`
--
ALTER TABLE `utilisateurs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `amis`
--
ALTER TABLE `amis`
  ADD CONSTRAINT `amis_ibfk_1` FOREIGN KEY (`demandeur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `amis_ibfk_2` FOREIGN KEY (`receveur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `commentaires`
--
ALTER TABLE `commentaires`
  ADD CONSTRAINT `commentaires_ibfk_1` FOREIGN KEY (`publication_id`) REFERENCES `publications` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `commentaires_ibfk_2` FOREIGN KEY (`auteur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`expediteur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`destinataire_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `publications`
--
ALTER TABLE `publications`
  ADD CONSTRAINT `publications_ibfk_1` FOREIGN KEY (`auteur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `reactions`
--
ALTER TABLE `reactions`
  ADD CONSTRAINT `reactions_ibfk_1` FOREIGN KEY (`publication_id`) REFERENCES `publications` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reactions_ibfk_2` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
