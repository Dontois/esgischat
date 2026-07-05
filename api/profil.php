<?php
require_once __DIR__ . '/../inclure/config.php';
require_once __DIR__ . '/../inclure/fonctions.php';

$utilisateur = utilisateur_api_requis();
$uid = (int)$utilisateur['id'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'infos' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    reponse_json_api(true, ['user' => utilisateur_public($utilisateur)]);
}

if ($action === 'modifier_infos' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom    = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $email  = trim($_POST['email'] ?? '');
    $bio    = trim($_POST['bio'] ?? '');

    if (!$nom || !$prenom || !$email) {
        reponse_json_api(false, ['message' => 'Nom, prénom et email sont obligatoires.']);
    }
    if (!email_valide($email)) {
        reponse_json_api(false, ['message' => 'Email invalide.']);
    }

    $req = $bdd->prepare("SELECT id FROM utilisateurs WHERE email = :email AND id != :id");
    $req->execute(['email' => $email, 'id' => $uid]);
    if ($req->fetch()) {
        reponse_json_api(false, ['message' => 'Cet email est déjà utilisé.']);
    }

    $photo = $utilisateur['photo_profil'];
    $erreur_image = null;
    $nouvelle_photo = traiter_image($_FILES['photo_profil'] ?? [], 'avatars', $erreur_image);
    if ($erreur_image) {
        reponse_json_api(false, ['message' => $erreur_image]);
    }
    if ($nouvelle_photo) {
        $photo = $nouvelle_photo;
    }

    $bdd->prepare("UPDATE utilisateurs SET nom=:nom, prenom=:prenom, email=:email, bio=:bio, photo_profil=:photo WHERE id=:id")
        ->execute([
            'nom' => strip_tags($nom), 'prenom' => strip_tags($prenom),
            'email' => $email, 'bio' => strip_tags($bio),
            'photo' => $photo, 'id' => $uid,
        ]);

    $req = $bdd->prepare("SELECT id, nom, prenom, email, photo_profil, bio, role, date_creation FROM utilisateurs WHERE id = :id");
    $req->execute(['id' => $uid]);
    $maj = $req->fetch();

    reponse_json_api(true, [
        'message' => 'Profil mis à jour !',
        'user'    => utilisateur_public($maj),
    ]);
}

if ($action === 'modifier_mdp' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $ancien  = $_POST['ancien_mdp'] ?? '';
    $nouveau = $_POST['nouveau_mdp'] ?? '';
    $conf    = $_POST['confirm_mdp'] ?? '';

    $req = $bdd->prepare("SELECT mot_de_passe FROM utilisateurs WHERE id = :id");
    $req->execute(['id' => $uid]);
    $ligne = $req->fetch();

    if (!password_verify($ancien, $ligne['mot_de_passe'])) {
        reponse_json_api(false, ['message' => 'Ancien mot de passe incorrect.']);
    }
    if (strlen($nouveau) < 6 || $nouveau !== $conf) {
        reponse_json_api(false, ['message' => 'Mots de passe invalides ou différents (6 caractères minimum).']);
    }

    $hash = password_hash($nouveau, PASSWORD_DEFAULT);
    // On génère un nouveau jeton d'API : la session actuelle reste valide,
    // mais toute autre session ouverte avec l'ancien jeton est déconnectée.
    $nouveau_token = generer_api_token();
    $bdd->prepare("UPDATE utilisateurs SET mot_de_passe = :mdp, api_token = :token WHERE id = :id")
        ->execute(['mdp' => $hash, 'token' => $nouveau_token, 'id' => $uid]);

    reponse_json_api(true, [
        'message' => 'Mot de passe modifié avec succès !',
        'token'   => $nouveau_token,
    ]);
}

reponse_json_api(false, ['message' => 'Action inconnue.']);
