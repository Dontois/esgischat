<?php
// Fonctions utilitaires utilisées dans tout le site

// Démarre la session si elle n'est pas déjà démarrée
function demarrer_session() {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
}

// Echappe le texte avant de l'afficher (sécurité contre le code HTML/JS malveillant)
function e($texte) {
    return htmlspecialchars($texte ?? '', ENT_QUOTES, 'UTF-8');
}

// Vérifie qu'un email est valide
function email_valide($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// Bloque la page si l'utilisateur n'est pas connecté (côté client normal)
// Remarque : cette fonction est appelée depuis vues/clients/*.php (2 niveaux sous la racine),
// donc "../../index.php" pointe toujours vers la racine du projet, peu importe son emplacement.
function verifier_connexion() {
    demarrer_session();
    if (empty($_SESSION['utilisateur'])) {
        header('Location: ../../index.php');
        exit;
    }
    return $_SESSION['utilisateur'];
}

// Bloque la page si l'utilisateur n'est pas modérateur/admin
function verifier_connexion_admin($role_minimum = 'moderateur') {
    demarrer_session();
    $niveaux = ['moderateur' => 1, 'admin' => 2];
    $utilisateur = $_SESSION['admin'] ?? null;
    $niveau_actuel = $niveaux[$utilisateur['role'] ?? ''] ?? 0;
    $niveau_requis = $niveaux[$role_minimum] ?? 1;

    if (!$utilisateur || $niveau_actuel < $niveau_requis) {
        header('Location: login-admin.php');
        exit;
    }
    return $utilisateur;
}

// Enregistre un message à afficher sur la prochaine page (succès ou erreur)
function message_flash($texte, $type = 'succes') {
    demarrer_session();
    $_SESSION['flash'] = ['texte' => $texte, 'type' => $type];
}

// Affiche le message flash s'il y en a un, puis le supprime
function afficher_flash() {
    demarrer_session();
    if (!empty($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        echo '<div class="toast ' . e($f['type']) . '" style="position:static;margin:0 0 16px 0">' . e($f['texte']) . '</div>';
        unset($_SESSION['flash']);
    }
}

// Gère l'upload d'une image (photo de profil, publication ou message)
// Retourne le nom du fichier enregistré, ou null si pas d'image fournie ou erreur.
// $erreur (par référence) est rempli avec un message explicite si l'upload
// a été TENTÉ mais a échoué, afin que l'appelant puisse prévenir l'utilisateur
// au lieu d'échouer silencieusement (c'était le bug : une image trop lourde
// disparaissait sans aucun message, et côté chat le message n'était même pas
// envoyé bien que l'API réponde "succès").
function traiter_image($fichier, $dossier, &$erreur = null) {
    $erreur = null;

    if (empty($fichier) || empty($fichier['name'])) {
        return null; // Aucun fichier fourni : ce n'est pas une erreur.
    }
    if (!is_array($fichier)) {
        $erreur = "Fichier invalide.";
        return null;
    }

    $code_erreur = $fichier['error'] ?? UPLOAD_ERR_NO_FILE;
    if ($code_erreur === UPLOAD_ERR_INI_SIZE || $code_erreur === UPLOAD_ERR_FORM_SIZE) {
        $erreur = "Image trop volumineuse pour la configuration actuelle du serveur "
                . "(limite serveur : " . ini_get('upload_max_filesize') . "). "
                . "Réduisez la taille de l'image ou contactez l'administrateur.";
        return null;
    }
    if ($code_erreur !== UPLOAD_ERR_OK) {
        $erreur = "Erreur lors de l'envoi du fichier (code $code_erreur).";
        return null;
    }

    // Formats acceptés : les formats web classiques + les photos de smartphones (HEIC/HEIF)
    $extensions_ok = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'heic', 'heif'];
    $taille_max    = 12 * 1024 * 1024; // 12 Mo (photos de smartphone modernes)

    if (($fichier['size'] ?? 0) > $taille_max) {
        $erreur = "Image trop volumineuse (12 Mo maximum).";
        return null;
    }

    $extension = strtolower(pathinfo($fichier['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, $extensions_ok, true)) {
        $erreur = "Format d'image non pris en charge (." . e($extension) . "). "
                . "Formats acceptés : JPG, PNG, GIF, WEBP, BMP, HEIC.";
        return null;
    }

    $dossier_cible = __DIR__ . '/../uploads/' . $dossier;
    if (!is_dir($dossier_cible) && !mkdir($dossier_cible, 0777, true) && !is_dir($dossier_cible)) {
        $erreur = "Impossible de créer le dossier de destination sur le serveur.";
        return null;
    }

    $nom_fichier = uniqid('img_') . '.' . $extension;
    $chemin      = $dossier_cible . '/' . $nom_fichier;

    if (move_uploaded_file($fichier['tmp_name'], $chemin)) {
        return $nom_fichier;
    }

    $erreur = "Échec de l'enregistrement de l'image sur le serveur.";
    return null;
}

// Envoi un email HTML simple via la fonction mail() du serveur
function envoyer_email_html($destinataire, $sujet, $html) {
    $headers = [];
    $headers[] = 'MIME-Version: 1.0';
    $headers[] = 'Content-type: text/html; charset=UTF-8';
    $headers[] = 'From: no-reply@esgischat.local';

    return mail($destinataire, $sujet, $html, implode("\r\n", $headers));
}

// Réponse JSON standard pour les endpoints API
function reponse_json_api($success, $data = []) {
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(array_merge(['success' => $success], $data));
    exit;
}

// Données utilisateur sans champs sensibles
function utilisateur_public($ligne) {
    if (!$ligne) {
        return null;
    }
    unset($ligne['mot_de_passe'], $ligne['reset_token'], $ligne['reset_expire'], $ligne['api_token']);
    return $ligne;
}

// Génère un jeton d'authentification aléatoire et unique pour l'API
function generer_api_token() {
    return bin2hex(random_bytes(32));
}

// Authentification API via sessionStorage (header X-Auth-Token envoyé par le JS).
// IMPORTANT : on ne fait JAMAIS confiance à un identifiant envoyé par le client
// (comme un ancien header "X-User-Id" qui pourrait être falsifié depuis les
// DevTools). On vérifie un jeton secret généré côté serveur à la connexion et
// stocké en base, associé à un seul utilisateur.
function utilisateur_depuis_entete() {
    global $bdd;
    $token = $_SERVER['HTTP_X_AUTH_TOKEN'] ?? '';
    if ($token === '' || !preg_match('/^[a-f0-9]{64}$/', $token)) {
        return null;
    }

    static $cache = [];
    if (array_key_exists($token, $cache)) {
        return $cache[$token];
    }

    $req = $bdd->prepare(
        "SELECT id, nom, prenom, email, photo_profil, bio, role, date_creation
         FROM utilisateurs WHERE api_token = :token"
    );
    $req->execute(['token' => $token]);
    $user = $req->fetch() ?: null;
    $cache[$token] = $user;
    return $user;
}

function utilisateur_api_requis($client_uniquement = true) {
    $user = utilisateur_depuis_entete();
    if (!$user) {
        reponse_json_api(false, ['message' => 'Non autorisé.']);
    }
    if ($client_uniquement && ($user['role'] ?? '') !== 'user') {
        reponse_json_api(false, ['message' => 'Ce compte doit utiliser le portail administrateur.']);
    }
    return $user;
}

// Chemins uploads : racine en SPA, ../../ depuis vues/clients/
function chemin_upload($dossier, $fichier) {
    if (empty($fichier)) {
        return '';
    }
    $prefix = (defined('SPA_MODE') && SPA_MODE) ? '' : '../../';
    return $prefix . 'uploads/' . $dossier . '/' . $fichier;
}

function initiales_avatar($prenom, $nom) {
    return mb_substr($prenom ?? '', 0, 1) . mb_substr($nom ?? '', 0, 1);
}

// Transforme une date SQL en texte relatif ("Il y a 5 min", etc.)
function temps_relatif($date_sql) {
    $secondes = time() - strtotime($date_sql);

    if ($secondes < 60)    return "À l'instant";
    if ($secondes < 3600)  return "Il y a " . floor($secondes / 60) . " min";
    if ($secondes < 86400) return "Il y a " . floor($secondes / 3600) . " h";
    if ($secondes < 604800) return "Il y a " . floor($secondes / 86400) . " j";
    return date('d/m/Y', strtotime($date_sql));
}
