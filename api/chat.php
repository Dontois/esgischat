<?php
require_once __DIR__ . '/../inclure/config.php';
require_once __DIR__ . '/../inclure/fonctions.php';

if (!defined('SPA_MODE')) {
    define('SPA_MODE', true);
}

$utilisateur = utilisateur_api_requis();
$uid = (int)$utilisateur['id'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'envoyer' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $dest = intval($_POST['destinataire_id'] ?? 0);
    $contenu = trim($_POST['contenu'] ?? '');
    $erreur_image = null;
    $image = traiter_image($_FILES['image'] ?? [], 'messages', $erreur_image);

    if ($erreur_image) {
        reponse_json_api(false, ['message' => $erreur_image]);
    }
    if (!$dest) {
        reponse_json_api(false, ['message' => 'Destinataire invalide.']);
    }
    if ($contenu === '' && !$image) {
        reponse_json_api(false, ['message' => 'Le message ne peut pas être vide.']);
    }

    $bdd->prepare("INSERT INTO messages (expediteur_id, destinataire_id, contenu, image) VALUES (:exp, :dest, :contenu, :image)")
        ->execute([
            'exp'     => $uid,
            'dest'    => $dest,
            'contenu' => $contenu !== '' ? strip_tags($contenu) : null,
            'image'   => $image,
        ]);

    reponse_json_api(true, ['message' => 'Message envoyé.']);
}

if ($action === 'charger') {
    $avec = intval($_GET['avec'] ?? 0);
    if (!$avec) {
        reponse_json_api(true, ['messages' => []]);
    }

    $bdd->prepare("UPDATE messages SET lu = 1 WHERE expediteur_id = :avec AND destinataire_id = :uid")
        ->execute(['avec' => $avec, 'uid' => $uid]);

    $req = $bdd->prepare("SELECT * FROM messages WHERE (expediteur_id = :uid AND destinataire_id = :avec) OR (expediteur_id = :avec AND destinataire_id = :uid) ORDER BY date_creation ASC");
    $req->execute(['uid' => $uid, 'avec' => $avec]);
    $messages = $req->fetchAll();

    foreach ($messages as &$m) {
        $m['date_label'] = temps_relatif($m['date_creation']);
        if (!empty($m['image'])) {
            $m['image_url'] = chemin_upload('messages', $m['image']);
        }
    }
    unset($m);

    reponse_json_api(true, ['messages' => $messages]);
}

if ($action === 'conversations') {
    $req = $bdd->prepare("
        SELECT
            u.id, u.nom, u.prenom, u.photo_profil,
            m.contenu AS dernier_message, m.date_creation AS date_dernier,
            (SELECT COUNT(*) FROM messages nm WHERE nm.expediteur_id = u.id AND nm.destinataire_id = :uid AND nm.lu = 0) AS non_lus
        FROM utilisateurs u
        JOIN messages m ON m.id = (
            SELECT id FROM messages
            WHERE (expediteur_id = u.id AND destinataire_id = :uid)
               OR (expediteur_id = :uid AND destinataire_id = u.id)
            ORDER BY date_creation DESC LIMIT 1
        )
        WHERE u.id != :uid
        ORDER BY m.date_creation DESC
    ");
    $req->execute(['uid' => $uid]);
    $conversations = $req->fetchAll();

    $req_amis = $bdd->prepare("
        SELECT u.id, u.nom, u.prenom, u.photo_profil
        FROM utilisateurs u
        JOIN amis a ON (
            (a.demandeur_id = :uid AND a.receveur_id = u.id) OR
            (a.receveur_id = :uid AND a.demandeur_id = u.id)
        )
        WHERE a.statut = 'accepte' AND u.id != :uid
        ORDER BY u.prenom ASC
    ");
    $req_amis->execute(['uid' => $uid]);
    $amis = $req_amis->fetchAll();

    $conversation_ids = array_column($conversations, 'id');
    if ($conversation_ids) {
        $conversation_ids = array_flip($conversation_ids);
        $amis = array_values(array_filter($amis, function ($ami) use ($conversation_ids) {
            return !isset($conversation_ids[$ami['id']]);
        }));
    }

    reponse_json_api(true, [
        'conversations' => $conversations,
        'amis'          => $amis,
    ]);
}

if ($action === 'conversation') {
    $avec = intval($_GET['avec'] ?? 0);
    if (!$avec) {
        reponse_json_api(false, ['message' => 'Conversation introuvable']);
    }

    $req = $bdd->prepare("SELECT id, nom, prenom, photo_profil FROM utilisateurs WHERE id = :id");
    $req->execute(['id' => $avec]);
    $interlocuteur = $req->fetch();

    if (!$interlocuteur) {
        reponse_json_api(false, ['message' => 'Utilisateur introuvable']);
    }

    $bdd->prepare("UPDATE messages SET lu = 1 WHERE expediteur_id = :avec AND destinataire_id = :uid")
        ->execute(['avec' => $avec, 'uid' => $uid]);

    $req = $bdd->prepare("SELECT * FROM messages WHERE (expediteur_id = :uid AND destinataire_id = :avec) OR (expediteur_id = :avec AND destinataire_id = :uid) ORDER BY date_creation ASC");
    $req->execute(['uid' => $uid, 'avec' => $avec]);
    $messages = $req->fetchAll();

    $html = '<div class="chat-entete">';
    $html .= '<a href="#/chat" data-nav="chat" class="chat-back-btn" aria-label="Retour aux conversations"><i class="ph ph-arrow-left" aria-hidden="true"></i></a>';
    if (!empty($interlocuteur['photo_profil'])) {
        $html .= '<img src="' . e(chemin_upload('avatars', $interlocuteur['photo_profil'])) . '" class="avatar avatar-sm" alt="">';
    } else {
        $html .= '<div class="avatar avatar-sm">' . e(initiales_avatar($interlocuteur['prenom'], $interlocuteur['nom'])) . '</div>';
    }
    $html .= '<div class="chat-entete-nom">' . e($interlocuteur['prenom'] . ' ' . $interlocuteur['nom']) . '</div></div>';
    $html .= '<div class="chat-messages" data-chat-messages>';

    foreach ($messages as $m) {
        $mine = ($m['expediteur_id'] == $uid);
        $html .= '<div class="message-bulle ' . ($mine ? 'moi' : 'autre') . '">';
        if (!empty($m['contenu'])) {
            $html .= '<div class="message-contenu">' . nl2br(e($m['contenu'])) . '</div>';
        }
        if (!empty($m['image'])) {
            $html .= '<img src="' . e(chemin_upload('messages', $m['image'])) . '" class="message-image ' . ($mine ? 'moi' : 'autre') . '" alt="">';
        }
        $html .= '<div class="message-heure">' . e(temps_relatif($m['date_creation'])) . '</div></div>';
    }
    if (!$messages) {
        $html .= '<p style="text-align:center;color:var(--texte-3)">Aucun message. Dites bonjour !</p>';
    }

    $html .= '</div><form class="chat-saisie" data-chat-form enctype="multipart/form-data">';
    $html .= '<input type="hidden" name="destinataire_id" value="' . (int)$interlocuteur['id'] . '">';
    $html .= '<textarea name="contenu" placeholder="Écrire un message..." rows="1" data-chat-input></textarea>';
    $html .= '<label class="btn btn-ghost btn-petit" style="cursor:pointer"><i class="ph ph-image" aria-hidden="true"></i><input type="file" name="image" accept="image/*" data-chat-image style="display:none"></label>';
    $html .= '<button type="submit" class="btn btn-primaire btn-petit">Envoyer</button></form>';

    reponse_json_api(true, [
        'html'             => $html,
        'interlocuteur_id' => (int)$interlocuteur['id'],
    ]);
}

reponse_json_api(false, ['message' => 'Action inconnue']);
