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

| Administrateur | admin@reseau.com | password123 |
| Modérateur | marie@reseau.com | password123 |
| Utilisateur | lucas@reseau.com | password123 |
| Utilisateur |simon@email.com | 123456
| Utilisateur |brayan@email.com| 123456


## Accès au back-office
- URL admin : http://localhost/esgi_new/vues/back-office/login-admin.php
- Seuls les comptes `moderateur` et `admin` peuvent s’authentifier.
- Un compte `user` ne doit pas accéder au portail admin.

## Installation
1. Copier le dossier `esgi_new` dans `htdocs` de XAMPP.
2. Importer le fichier SQL fourni dans phpMyAdmin.
3. Vérifier les paramètres de connexion MySQL dans [inclure/config.php](inclure/config.php).
4. Ouvrir http://localhost/esgi_new/index.html.

Lien du dépôt
https://github.com/Dontois/esgischat

Lien site héberger 
https://esgischat.infinityfreeapp.com/


## Liste des membres du groupe
- ALLOHOU Mirabelle
- HOUNNOUKPE Ariane
- ZANNOU Wilson
- OKANLAWON Dontoi's

