<?php
$uid = null;
if (!defined('SPA_MODE')) {
  header('Location: ../../index.html#/chat');
  exit;
}

// Variables attendues mais parfois non définies lors de l'analyse statique
$utilisateur = $utilisateur ?? ($_SESSION['utilisateur'] ?? null);
$page_actuelle = $page_actuelle ?? 'chat';

$uid = (int)($utilisateur['id'] ?? 0);
$avec = (int)($_GET['avec'] ?? 0);

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

$req = $bdd->prepare("
    SELECT u.id, u.nom, u.prenom, u.photo_profil
    FROM utilisateurs u
    JOIN amis a ON (
        (a.demandeur_id = :uid AND a.receveur_id = u.id) OR
        (a.receveur_id = :uid AND a.demandeur_id = u.id)
    )
    WHERE a.statut = 'accepte' AND u.id != :uid
    ORDER BY u.prenom ASC
");
$req->execute(['uid' => $uid]);
$amis = $req->fetchAll();

$conversation_ids = array_column($conversations, 'id');
if ($conversation_ids) {
    $conversation_ids = array_flip($conversation_ids);
    $amis = array_values(array_filter($amis, function ($ami) use ($conversation_ids) {
        return !isset($conversation_ids[$ami['id']]);
    }));
}

$interlocuteur = null;
$messages = [];
if ($avec) {
    $req = $bdd->prepare("SELECT id, nom, prenom, photo_profil FROM utilisateurs WHERE id = :id");
    $req->execute(['id' => $avec]);
    $interlocuteur = $req->fetch();

    if ($interlocuteur) {
        $bdd->prepare("UPDATE messages SET lu = 1 WHERE expediteur_id = :avec AND destinataire_id = :uid")
            ->execute(['avec' => $avec, 'uid' => $uid]);

        $req = $bdd->prepare("
            SELECT * FROM messages
            WHERE (expediteur_id = :uid AND destinataire_id = :avec)
               OR (expediteur_id = :avec AND destinataire_id = :uid)
            ORDER BY date_creation ASC
        ");
        $req->execute(['uid' => $uid, 'avec' => $avec]);
        $messages = $req->fetchAll();
    }
}
?>

<div class="chat-layout <?= $interlocuteur ? 'a-conversation' : '' ?>" style="height:calc(100vh - 140px)">
  <div class="chat-sidebar">
    <div class="chat-sidebar-top">
      <div class="chat-sidebar-head">
        <div>
          <h3>Messages</h3>
          <div class="chat-sidebar-sub">Vos amis et conversations actives.</div>
        </div>
      </div>
      <input type="text" class="champ" placeholder="Rechercher un ami..." data-chat-search style="margin-top:10px">
    </div>
    <div class="chat-liste" data-chat-list>
      <?php foreach ($conversations as $c): ?>
        <a class="chat-conv-item <?= $avec == $c['id'] ? 'actif' : '' ?>" href="#/chat/<?= (int)$c['id'] ?>" data-user-id="<?= (int)$c['id'] ?>" data-chat-name="<?= e(strtolower($c['prenom'] . ' ' . $c['nom'])) ?>" style="text-decoration:none;color:inherit;display:flex">
          <?php if (!empty($c['photo_profil'])): ?>
            <img src="<?= e(chemin_upload('avatars', $c['photo_profil'])) ?>" class="avatar avatar-sm" alt="">
          <?php else: ?>
            <div class="avatar avatar-sm"><?= e(initiales_avatar($c['prenom'], $c['nom'])) ?></div>
          <?php endif; ?>
          <div class="chat-conv-info">
            <div class="chat-conv-nom"><?= e($c['prenom'] . ' ' . $c['nom']) ?></div>
            <div class="chat-conv-dernier"><?= e($c['dernier_message']) ?></div>
          </div>
          <?php if ($c['non_lus'] > 0): ?>
            <span class="chat-badge-non-lu"><?= (int)$c['non_lus'] ?></span>
          <?php endif; ?>
        </a>
      <?php endforeach; ?>

      <?php if ($amis): ?>
        <div style="padding:10px 16px;font-size:12px;color:var(--texte-3)" data-chat-amis-label>Démarrer avec un ami :</div>
        <?php foreach ($amis as $a): ?>
          <a class="chat-conv-item" href="#/chat/<?= (int)$a['id'] ?>" data-user-id="<?= (int)$a['id'] ?>" data-chat-name="<?= e(strtolower($a['prenom'] . ' ' . $a['nom'])) ?>" style="text-decoration:none;color:inherit;display:flex">
            <?php if (!empty($a['photo_profil'])): ?>
              <img src="<?= e(chemin_upload('avatars', $a['photo_profil'])) ?>" class="avatar avatar-sm" alt="">
            <?php else: ?>
              <div class="avatar avatar-sm"><?= e(initiales_avatar($a['prenom'], $a['nom'])) ?></div>
            <?php endif; ?>
            <div class="chat-conv-info">
              <div class="chat-conv-nom"><?= e($a['prenom'] . ' ' . $a['nom']) ?></div>
            </div>
          </a>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>

  <div class="chat-zone">
    <div class="chat-zone-content">
      <?php if (!$interlocuteur): ?>
        <div class="chat-vide">
          <div style="font-size:48px"><i class="ph ph-chat-circle-dots" aria-hidden="true"></i></div>
          <p>Sélectionnez une conversation ou un ami pour discuter</p>
        </div>
      <?php else: ?>
        <div class="chat-entete">
          <a href="#/chat" data-nav="chat" class="chat-back-btn" aria-label="Retour aux conversations"><i class="ph ph-arrow-left" aria-hidden="true"></i></a>
          <?php if (!empty($interlocuteur['photo_profil'])): ?>
            <img src="<?= e(chemin_upload('avatars', $interlocuteur['photo_profil'])) ?>" class="avatar avatar-sm" alt="">
          <?php else: ?>
            <div class="avatar avatar-sm"><?= e(initiales_avatar($interlocuteur['prenom'], $interlocuteur['nom'])) ?></div>
          <?php endif; ?>
          <div class="chat-entete-nom"><?= e($interlocuteur['prenom'] . ' ' . $interlocuteur['nom']) ?></div>
        </div>

        <div class="chat-messages" data-chat-messages>
          <?php foreach ($messages as $m): ?>
            <div class="message-bulle <?= $m['expediteur_id'] == $uid ? 'moi' : 'autre' ?>">
              <?php if (!empty($m['contenu'])): ?>
                <div class="message-contenu"><?= nl2br(e($m['contenu'])) ?></div>
              <?php endif; ?>
              <?php if (!empty($m['image'])): ?>
                <img src="<?= e(chemin_upload('messages', $m['image'])) ?>" class="message-image" alt="">
              <?php endif; ?>
              <div class="message-heure"><?= e(temps_relatif($m['date_creation'])) ?></div>
            </div>
          <?php endforeach; ?>
          <?php if (!$messages): ?>
            <p style="text-align:center;color:var(--texte-3)">Aucun message. Dites bonjour !</p>
          <?php endif; ?>
        </div>

        <form class="chat-saisie" data-chat-form enctype="multipart/form-data">
          <input type="hidden" name="destinataire_id" value="<?= (int)$interlocuteur['id'] ?>">
          <textarea name="contenu" placeholder="Écrire un message..." rows="1" data-chat-input></textarea>
          <label class="btn btn-ghost btn-petit" style="cursor:pointer"><i class="ph ph-image" aria-hidden="true"></i><input type="file" name="image" accept="image/*" data-chat-image style="display:none"></label>
          <button type="submit" class="btn btn-primaire btn-petit">Envoyer</button>
        </form>
        <div class="chat-image-preview caché" data-chat-preview>
          <img src="" alt="Aperçu du message" data-chat-preview-img>
          <div class="chat-image-preview-info">
            <div class="chat-image-preview-name" data-chat-preview-name></div>
            <button type="button" class="btn btn-danger btn-petit" data-chat-remove-image><i class="ph ph-x" aria-hidden="true"></i> Retirer</button>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>
