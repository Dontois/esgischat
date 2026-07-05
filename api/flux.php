<?php
require_once __DIR__ . '/../inclure/config.php';
require_once __DIR__ . '/../inclure/fonctions.php';
require_once __DIR__ . '/../inclure/publications.php';

if (!defined('SPA_MODE')) {
    define('SPA_MODE', true);
}

$utilisateur = utilisateur_api_requis();
$uid = (int)$utilisateur['id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    reponse_json_api(false, ['message' => 'Méthode non autorisée.']);
}

$action = $_POST['action'] ?? '';

if ($action === 'reagir') {
    $pub_id = intval($_POST['publication_id'] ?? 0);
    $type   = $_POST['type'] ?? '';

    if (!$pub_id || !in_array($type, ['like', 'dislike'], true)) {
        reponse_json_api(false, ['message' => 'Paramètres invalides.']);
    }

    $req = $bdd->prepare("SELECT id, type FROM reactions WHERE publication_id = :pub AND utilisateur_id = :uid");
    $req->execute(['pub' => $pub_id, 'uid' => $uid]);
    $existante = $req->fetch();

    if ($existante && $existante['type'] === $type) {
        $bdd->prepare("DELETE FROM reactions WHERE id = :id")->execute(['id' => $existante['id']]);
    } elseif ($existante) {
        $bdd->prepare("UPDATE reactions SET type = :type WHERE id = :id")
            ->execute(['type' => $type, 'id' => $existante['id']]);
    } else {
        $bdd->prepare("INSERT INTO reactions (publication_id, utilisateur_id, type) VALUES (:pub, :uid, :type)")
            ->execute(['pub' => $pub_id, 'uid' => $uid, 'type' => $type]);
    }

    $req = $bdd->prepare("SELECT 
        (SELECT COUNT(*) FROM reactions r WHERE r.publication_id = :pub AND r.type = 'like') AS nb_likes,
        (SELECT COUNT(*) FROM reactions r WHERE r.publication_id = :pub AND r.type = 'dislike') AS nb_dislikes,
        (SELECT r2.type FROM reactions r2 WHERE r2.publication_id = :pub AND r2.utilisateur_id = :uid) AS ma_reaction");
    $req->execute(['pub' => $pub_id, 'uid' => $uid]);
    $stats = $req->fetch();

    reponse_json_api(true, [
        'reaction'      => $stats['ma_reaction'] ?? null,
        'like_count'    => (int)$stats['nb_likes'],
        'dislike_count' => (int)$stats['nb_dislikes'],
        'message'       => 'Réaction mise à jour.',
    ]);
}

if ($action === 'commenter') {
    $pub_id  = intval($_POST['publication_id'] ?? 0);
    $contenu = trim($_POST['contenu'] ?? '');

    if (!$pub_id || $contenu === '') {
        reponse_json_api(false, ['message' => 'Commentaire vide.']);
    }

    $bdd->prepare("INSERT INTO commentaires (publication_id, auteur_id, contenu) VALUES (:pub, :uid, :contenu)")
        ->execute(['pub' => $pub_id, 'uid' => $uid, 'contenu' => strip_tags($contenu)]);

    $comment_id = (int)$bdd->lastInsertId();
    $req = $bdd->prepare("SELECT c.*, u.nom, u.prenom FROM commentaires c JOIN utilisateurs u ON u.id = c.auteur_id WHERE c.id = :id");
    $req->execute(['id' => $comment_id]);
    $comment = $req->fetch();

    $req = $bdd->prepare("SELECT COUNT(*) AS total FROM commentaires WHERE publication_id = :pub");
    $req->execute(['pub' => $pub_id]);
    $nb_comments = (int)$req->fetchColumn();

    $html = '<div class="comment-bulle">';
    $html .= '<strong>' . e($comment['prenom'] . ' ' . $comment['nom']) . ' :</strong> ' . e($comment['contenu']);
    $html .= '<div class="comment-meta">' . e(temps_relatif($comment['date_creation'])) . '</div>';
    $html .= '</div>';

    reponse_json_api(true, [
        'comment_html'  => $html,
        'comment_count' => $nb_comments,
        'message'       => 'Commentaire ajouté.',
    ]);
}

if ($action === 'publier') {
    $contenu = trim($_POST['contenu'] ?? '');
    $erreur_image = null;
    $image = traiter_image($_FILES['image'] ?? [], 'posts', $erreur_image);

    if ($erreur_image) {
        reponse_json_api(false, ['message' => $erreur_image]);
    }
    if ($contenu === '' && !$image) {
        reponse_json_api(false, ['message' => 'Le contenu de la publication ne peut pas être vide.']);
    }

    $req = $bdd->prepare("INSERT INTO publications (auteur_id, contenu, image) VALUES (:uid, :contenu, :image)");
    $req->execute(['uid' => $uid, 'contenu' => $contenu !== '' ? strip_tags($contenu) : null, 'image' => $image]);
    $publication_id = (int)$bdd->lastInsertId();

    $req = $bdd->prepare("SELECT p.*, u.nom, u.prenom, u.photo_profil,
        (SELECT COUNT(*) FROM reactions r WHERE r.publication_id = p.id AND r.type = 'like') AS nb_likes,
        (SELECT COUNT(*) FROM reactions r WHERE r.publication_id = p.id AND r.type = 'dislike') AS nb_dislikes,
        (SELECT r2.type FROM reactions r2 WHERE r2.publication_id = p.id AND r2.utilisateur_id = :uid) AS ma_reaction
        FROM publications p
        JOIN utilisateurs u ON u.id = p.auteur_id
        WHERE p.id = :id");
    $req->execute(['uid' => $uid, 'id' => $publication_id]);
    $publication = $req->fetch();

    reponse_json_api(true, [
        'message'          => 'Publication créée !',
        'publication_html' => render_publication_html($publication, $uid, $utilisateur, []),
        'publication_id'   => $publication_id,
    ]);
}

if ($action === 'supprimer') {
    $pub_id = intval($_POST['publication_id'] ?? 0);
    $req = $bdd->prepare("SELECT auteur_id FROM publications WHERE id = :id");
    $req->execute(['id' => $pub_id]);
    $pub = $req->fetch();

    if (!$pub || !($pub['auteur_id'] == $uid || in_array($utilisateur['role'], ['moderateur', 'admin'], true))) {
        reponse_json_api(false, ['message' => 'Action non autorisée.']);
    }

    $bdd->prepare("DELETE FROM publications WHERE id = :id")->execute(['id' => $pub_id]);
    reponse_json_api(true, ['message' => 'Publication supprimée.']);
}

reponse_json_api(false, ['message' => 'Action inconnue.']);
