
## Description du projet
ESGISchat est un réseau social interne destiné à un usage étudiant. Il propose :
- une inscription et une connexion sécurisées,
- un fil de publications avec likes/dislikes et commentaires,
- un système d'amis (envoi, acceptation, refus),
- une messagerie privée entre utilisateurs,
- un espace profil pour modifier ses informations et son mot de passe,
- un back-office pour la modération et l'administration.

## Mode de fonctionnement
Le projet fonctionne en mode semi-SPA pour la partie utilisateur :
- les vues sont chargées via `api/spa.php`.
- les actions utilisent des formulaires POST et rechargent la page.
- l'authentification utilisateur passe par un token stocké en base (`api_token`).
- les pages admin et client sont séparées : le back-office est accessible via `vues/back-office/login-admin.php`.
- les uploads d'images sont validés côté serveur et stockés dans `uploads/`.

### Organisation du projet
- `index.php` : page de connexion et d'inscription utilisateur.
- `deconnexion.php` : déconnexion.
- `inclure/` : configuration de la base et fonctions utilitaires.
- `api/` : endpoints pour l'authentification, le feed, le chat, les amis et le profil.
- `vues/clients/` : pages utilisateur.
- `vues/back-office/` : portail admin/modérateur.
- `assets/css/style.css` : styles principaux.
- `uploads/` : images uploadées.

## Identifiants de test
| Rôle | Email | Mot de passe |
|------|-------|--------------|
| Admin | admin@reseau.com | password123 |
| Modérateur | marie@reseau.com | password123 |
| Utilisateur | lucas@reseau.com | password123 |

## Accès au back-office
- URL admin : `http://localhost/esgi_new/vues/back-office/login-admin.php`
- Seuls les comptes `moderateur` et `admin` peuvent se connecter.
- Un compte `user` ne doit pas accéder au portail admin.


> Remplacez cette liste par les noms réels de votre groupe avant livraison.

## Installation
1. Copier le dossier `esgi_new` dans `htdocs` de XAMPP.
2. Importer `database.sql` dans phpMyAdmin.
3. Vérifier les paramètres de connexion MySQL dans `inclure/config.php`.
4. Ouvrir `http://localhost/esgi_new/index.php`.

## Uploads d'images
- Formats acceptés : JPG, PNG, GIF, WEBP, BMP, HEIC.
- Taille maximale : 12 Mo.
- Si le serveur refuse encore l'upload, vérifiez et augmentez dans `php.ini` :
```ini
upload_max_filesize = 12M
post_max_size = 15M
```

## Choix techniques
- Pas de `fetch`/`async`/AJAX : les actions sont gérées par des formulaires HTML classiques.
- Pas de bibliothèque externe : le projet utilise PHP, HTML, CSS et JS natif.
- Le back-office est protégé par un système de rôle (`user`, `moderateur`, `admin`).
- Les erreurs d'authentification ne doivent pas révéler si un compte existe ou non.


## Liste des membres du groupe
- OKANLAWON Dontoi's



