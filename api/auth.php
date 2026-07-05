<?php
require_once __DIR__ . '/../inclure/config.php';
require_once __DIR__ . '/../inclure/fonctions.php';

header('Content-Type: application/json; charset=UTF-8');

$action = $_GET['action'] ?? $_POST['action'] ?? '';

if ($action === 'login') {
    $email = trim($_POST['email'] ?? '');
    $mdp   = $_POST['mot_de_passe'] ?? '';

    if (!$email || !$mdp) {
        reponse_json_api(false, ['message' => 'Merci de remplir tous les champs.']);
    }

    $req = $bdd->prepare("SELECT * FROM utilisateurs WHERE email = :email");
    $req->execute(['email' => $email]);
    $utilisateur = $req->fetch();

    if (!$utilisateur || !password_verify($mdp, $utilisateur['mot_de_passe']) || $utilisateur['role'] !== 'user') {
        reponse_json_api(false, ['message' => 'Email ou mot de passe incorrect.']);
    }

    // Génère un nouveau jeton d'API à chaque connexion (invalide les anciennes sessions)
    $token = generer_api_token();
    $bdd->prepare("UPDATE utilisateurs SET api_token = :token WHERE id = :id")
        ->execute(['token' => $token, 'id' => $utilisateur['id']]);

    reponse_json_api(true, [
        'message' => 'Connexion réussie.',
        'user'    => utilisateur_public($utilisateur),
        'token'   => $token,
    ]);
}

if ($action === 'register') {
    $nom    = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $email  = trim($_POST['email'] ?? '');
    $mdp    = $_POST['mot_de_passe'] ?? '';
    $conf   = $_POST['confirm_mdp'] ?? '';

    if (!$nom || !$prenom || !$email || !$mdp || !$conf) {
        reponse_json_api(false, ['message' => 'Tous les champs sont obligatoires.']);
    }
    if (!email_valide($email)) {
        reponse_json_api(false, ['message' => 'Adresse email invalide.']);
    }
    if (strlen($mdp) < 6) {
        reponse_json_api(false, ['message' => 'Le mot de passe doit contenir au moins 6 caractères.']);
    }
    if ($mdp !== $conf) {
        reponse_json_api(false, ['message' => 'Les mots de passe ne correspondent pas.']);
    }

    $req = $bdd->prepare("SELECT id FROM utilisateurs WHERE email = :email");
    $req->execute(['email' => $email]);
    if ($req->fetch()) {
        reponse_json_api(false, ['message' => 'Cet email est déjà utilisé.']);
    }

    $erreur_image = null;
    $photo = traiter_image($_FILES['photo_profil'] ?? [], 'avatars', $erreur_image);
    if ($erreur_image) {
        reponse_json_api(false, ['message' => $erreur_image]);
    }
    $hash  = password_hash($mdp, PASSWORD_DEFAULT);

    $req = $bdd->prepare(
        "INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe, photo_profil)
         VALUES (:nom, :prenom, :email, :mdp, :photo)"
    );
    $req->execute([
        'nom'    => strip_tags($nom),
        'prenom' => strip_tags($prenom),
        'email'  => $email,
        'mdp'    => $hash,
        'photo'  => $photo,
    ]);

    $id = (int)$bdd->lastInsertId();

    $token = generer_api_token();
    $bdd->prepare("UPDATE utilisateurs SET api_token = :token WHERE id = :id")
        ->execute(['token' => $token, 'id' => $id]);

    $req = $bdd->prepare("SELECT id, nom, prenom, email, photo_profil, bio, role, date_creation FROM utilisateurs WHERE id = :id");
    $req->execute(['id' => $id]);
    $utilisateur = $req->fetch();

    reponse_json_api(true, [
        'message' => 'Compte créé avec succès !',
        'user'    => utilisateur_public($utilisateur),
        'token'   => $token,
    ]);
}

if ($action === 'forgot') {
    $email = trim($_POST['email'] ?? '');

    $lien_test = null;
    if ($email !== '' && email_valide($email)) {
        $req = $bdd->prepare("SELECT id, prenom FROM utilisateurs WHERE email = :email AND role = 'user'");
        $req->execute(['email' => $email]);
        $utilisateur = $req->fetch();

        if ($utilisateur) {
            $token = bin2hex(random_bytes(16));
            $expire = date('Y-m-d H:i:s', time() + 3600);
            $bdd->prepare("UPDATE utilisateurs SET reset_token = :token, reset_expire = :expire WHERE id = :id")
                ->execute(['token' => $token, 'expire' => $expire, 'id' => $utilisateur['id']]);

            $lien = 'index.html#/reset?token=' . $token;
            $html = '<div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto;padding:24px;background:#0d0f1a;color:#e8eaf6;border-radius:16px">';
            $html .= '<h2 style="margin:0 0 12px">Réinitialisation de mot de passe</h2>';
            $html .= '<p>Bonjour ' . e($utilisateur['prenom']) . ',</p>';
            $html .= '<p>Vous avez demandé à réinitialiser votre mot de passe ESGISchat.</p>';
            $html .= '<p><a href="' . e($lien) . '" style="display:inline-block;padding:12px 18px;background:#7c6ef5;color:#fff;text-decoration:none;border-radius:10px">Réinitialiser mon mot de passe</a></p>';
            $html .= '<p style="font-size:12px;color:#9aa0b8">Ce lien expire dans 1 heure.</p>';
            $html .= '</div>';
            envoyer_email_html($email, 'Réinitialisation de mot de passe ESGISchat', $html);
            $lien_test = $lien;
        }
    }

    reponse_json_api(true, [
        'message'   => "Si cet email existe dans notre base, un lien de réinitialisation a été envoyé.",
        'lien_test' => $lien_test,
    ]);
}

if ($action === 'reset') {
    $token = $_POST['token'] ?? $_GET['token'] ?? '';
    $mdp   = $_POST['mot_de_passe'] ?? '';
    $conf  = $_POST['confirm_mdp'] ?? '';

    $req = $bdd->prepare("SELECT id FROM utilisateurs WHERE reset_token = :token AND reset_expire > NOW()");
    $req->execute(['token' => $token]);
    $utilisateur = $req->fetch();

    if (!$utilisateur) {
        reponse_json_api(false, ['message' => 'Lien invalide ou expiré.']);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'GET' || (!$mdp && !$conf)) {
        reponse_json_api(true, ['valid' => true]);
    }

    if (strlen($mdp) < 6 || $mdp !== $conf) {
        reponse_json_api(false, ['message' => 'Mots de passe invalides ou différents (6 caractères minimum).']);
    }

    $hash = password_hash($mdp, PASSWORD_DEFAULT);
    // On invalide aussi le jeton d'API existant : si le mot de passe a été
    // réinitialisé (compte potentiellement compromis), les anciennes sessions
    // ouvertes ailleurs sont déconnectées par sécurité.
    $bdd->prepare("UPDATE utilisateurs SET mot_de_passe = :mdp, reset_token = NULL, reset_expire = NULL, api_token = NULL WHERE id = :id")
        ->execute(['mdp' => $hash, 'id' => $utilisateur['id']]);

    reponse_json_api(true, ['message' => 'Mot de passe modifié avec succès !']);
}

if ($action === 'verify') {
    $user = utilisateur_depuis_entete();
    if (!$user || ($user['role'] ?? '') !== 'user') {
        reponse_json_api(false, ['message' => 'Session expirée.']);
    }
    reponse_json_api(true, ['user' => utilisateur_public($user)]);
}

if ($action === 'logout') {
    $user = utilisateur_depuis_entete();
    if ($user) {
        $bdd->prepare("UPDATE utilisateurs SET api_token = NULL WHERE id = :id")->execute(['id' => $user['id']]);
    }
    reponse_json_api(true, ['message' => 'Déconnexion réussie.']);
}

reponse_json_api(false, ['message' => 'Action inconnue.']);
