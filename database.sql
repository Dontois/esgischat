
CREATE DATABASE IF NOT EXISTS reseau_social CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE reseau_social;


CREATE TABLE IF NOT EXISTS utilisateurs (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    nom             VARCHAR(100)  NOT NULL,
    prenom          VARCHAR(100)  NOT NULL,
    email           VARCHAR(191)  NOT NULL UNIQUE,
    mot_de_passe    VARCHAR(255)  NOT NULL,
    photo_profil    VARCHAR(255)  DEFAULT NULL,
    bio             TEXT          DEFAULT NULL,
    role            ENUM('user','moderateur','admin') NOT NULL DEFAULT 'user',
    reset_token     VARCHAR(64)   DEFAULT NULL,
    reset_expire    DATETIME      DEFAULT NULL,
    api_token       VARCHAR(64)   DEFAULT NULL,
    date_creation   DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_api_token (api_token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE IF NOT EXISTS publications (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    auteur_id       INT           NOT NULL,
    contenu         TEXT          DEFAULT NULL,
    image           VARCHAR(255)  DEFAULT NULL,
    date_creation   DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (auteur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE IF NOT EXISTS reactions (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    publication_id  INT           NOT NULL,
    utilisateur_id  INT           NOT NULL,
    type            ENUM('like','dislike') NOT NULL,
    date_creation   DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_reaction (publication_id, utilisateur_id),
    FOREIGN KEY (publication_id) REFERENCES publications(id) ON DELETE CASCADE,
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE IF NOT EXISTS commentaires (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    publication_id  INT           NOT NULL,
    auteur_id       INT           NOT NULL,
    contenu         TEXT          NOT NULL,
    date_creation   DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (publication_id) REFERENCES publications(id) ON DELETE CASCADE,
    FOREIGN KEY (auteur_id)      REFERENCES utilisateurs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE IF NOT EXISTS amis (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    demandeur_id    INT           NOT NULL,
    receveur_id     INT           NOT NULL,
    statut          ENUM('en_attente','accepte','refuse') NOT NULL DEFAULT 'en_attente',
    date_creation   DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_ami (demandeur_id, receveur_id),
    FOREIGN KEY (demandeur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE,
    FOREIGN KEY (receveur_id)  REFERENCES utilisateurs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE IF NOT EXISTS messages (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    expediteur_id   INT           NOT NULL,
    destinataire_id INT           NOT NULL,
    contenu         TEXT          DEFAULT NULL,
    image           VARCHAR(255)  DEFAULT NULL,
    lu              TINYINT(1)    NOT NULL DEFAULT 0,
    date_creation   DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (expediteur_id)    REFERENCES utilisateurs(id) ON DELETE CASCADE,
    FOREIGN KEY (destinataire_id)  REFERENCES utilisateurs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe, role, bio) VALUES
('Admin',     'Super',   'admin@reseau.com',   '$2y$10$k5FFWhvEnljtakFN59iICuAJPb.IEz/MJDzFnUqZSSl9zh.puLz2y', 'admin',      'Administrateur du réseau social.'),
('Dupont',    'Marie',   'marie@reseau.com',   '$2y$10$k5FFWhvEnljtakFN59iICuAJPb.IEz/MJDzFnUqZSSl9zh.puLz2y', 'moderateur', 'Modératrice passionnée de lecture.'),
('Martin',    'Lucas',   'lucas@reseau.com',   '$2y$10$k5FFWhvEnljtakFN59iICuAJPb.IEz/MJDzFnUqZSSl9zh.puLz2y', 'user',       'Étudiant en informatique à ESGIS.'),
('Konan',     'Awa',     'awa@reseau.com',     '$2y$10$k5FFWhvEnljtakFN59iICuAJPb.IEz/MJDzFnUqZSSl9zh.puLz2y', 'user',       'Passionnée de développement web.');



INSERT INTO publications (auteur_id, contenu) VALUES
(3, 'Bonjour tout le monde ! Je viens de rejoindre ce réseau social. Ravi de vous rencontrer '),
(4, 'Aujourd\'hui j\'ai appris AJAX en PHP. C\'est vraiment puissant pour éviter les rechargements de page !'),
(3, 'Le développement web c\'est une aventure infinie. Chaque jour on apprend quelque chose de nouveau 🚀');
