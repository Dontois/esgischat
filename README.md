## Description du projet
ESGISchat est un réseau social interne destiné aux étudiants. Le projet propose :
- une inscription et une connexion sécurisées,
- un fil d’actualités avec publications, likes/dislikes et commentaires,
- un système d’amis (envoi, acceptation, refus),
- un module de messagerie privé avec conversations en temps réel,
- un espace profil pour modifier ses informations et son mot de passe,
- un back-office de modération et d’administration.

## Mode de fonctionnement
Le projet fonctionne en mode SPA-like avec un chargement initial via la page d’entrée [index.html](index.html), puis des échanges en JavaScript via Fetch pour la plupart des actions utilisateur.
- la partie client charge les vues via [api/spa.php](api/spa.php),
- l’authentification repose sur un token stocké côté navigateur avec sessionStorage,
- les uploads d’images sont validés côté serveur et stockés dans [uploads](uploads),
- le back-office est accessible via [vues/back-office/login-admin.php](vues/back-office/login-admin.php), avec des rôles `user`, `moderateur` et `admin`.

## Structure du projet
- [index.html](index.html) : page d’entrée du front office.
- [api](api) : endpoints PHP pour l’authentification, le flux, les amis, le chat et le profil.
- [assets](assets) : CSS et JavaScript.
- [vues/clients](vues/clients) : pages du client.
- [vues/back-office](vues/back-office) : portail d’administration.
- [inclure](inclure) : configuration et fonctions utilitaires.
- [uploads](uploads) : fichiers uploadés.

## Identifiants de test
| Rôle | Email | Mot de passe |
|------|-------|--------------|
| Administrateur | admin@reseau.com | password123 |
| Modérateur | marie@reseau.com | password123 |
| Utilisateur | lucas@reseau.com | password123 |

## Accès au back-office
- URL admin : http://localhost/esgi_new/vues/back-office/login-admin.php
- Seuls les comptes `moderateur` et `admin` peuvent s’authentifier.
- Un compte `user` ne doit pas accéder au portail admin.

## Installation
1. Copier le dossier `esgi_new` dans `htdocs` de XAMPP.
2. Importer le fichier SQL fourni dans phpMyAdmin.
3. Vérifier les paramètres de connexion MySQL dans [inclure/config.php](inclure/config.php).
4. Ouvrir http://localhost/esgi_new/index.html.

## Uploads d’images
- Formats acceptés : JPG, PNG, GIF, WEBP, BMP, HEIC.
- Taille maximale : 12 Mo.
- Si l’upload est refusé, vérifier les réglages PHP dans `php.ini` :
```ini
upload_max_filesize = 12M
post_max_size = 15M
```

## Choix techniques
- Frontend : HTML, CSS et JavaScript natif.
- Backend : PHP natif pour l’API et les vues.
- Communication asynchrone : Fetch/AJAX pour les interactions principales sans rechargement complet de la page après le chargement initial.
- Base de données : MySQL.
- Sécurité : mots de passe hashés, validation côté serveur, rôles utilisateur/admin/modérateur.

## Liste des membres du groupe
- ALLOHOU Mirabelle
- HOUNNOUPKE Ariane
- ZANNOU Wilson
- OKANLAWON Dontoi's



