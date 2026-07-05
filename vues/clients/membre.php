<?php
if (!defined('SPA_MODE')) {
    header('Location: ../../index.html#/amis');
    exit;
}

$uid = (int)$utilisateur['id'];
$membre_id = (int)($membre_id ?? $_GET['id'] ?? 0);

$req = $bdd->prepare("SELECT id, nom, prenom, email, photo_profil, bio, date_creation FROM utilisateurs WHERE id = :id AND role = 'user'");
$req->execute(['id' => $membre_id]);
$membre = $req->fetch();

if (!$membre) {
    echo '<div class="empty-state">Utilisateur introuvable.</div>';
    return;
}

$req = $bdd->prepare("
    SELECT a.id AS ami_id, a.statut, a.demandeur_id
    FROM amis a
    WHERE (a.demandeur_id = :uid AND a.receveur_id = :id)
       OR (a.receveur_id = :uid AND a.demandeur_id = :id)
    LIMIT 1
");
$req->execute(['uid' => $uid, 'id' => $membre_id]);
$amitie = $req->fetch();
?>

<div class="profil-entete">
  <div class="profil-couverture"></div>
  <div class="profil-infos-bas">
    <div class="profil-avatar-wrap">
      <?php if (!empty($membre['photo_profil'])): ?>
        <img src="<?= e(chemin_upload('avatars', $membre['photo_profil'])) ?>" class="avatar avatar-lg" alt="">
      <?php else: ?>
        <div class="avatar avatar-lg"><?= e(initiales_avatar($membre['prenom'], $membre['nom'])) ?></div>
      <?php endif; ?>
    </div>
    <div class="profil-texte">
      <div class="profil-nom"><?= e($membre['prenom'] . ' ' . $membre['nom']) ?></div>
      <div class="profil-bio"><?= e($membre['bio'] ?? 'Aucune bio renseignée.') ?></div>
      <div class="profil-meta">Membre depuis le <?= e(date('d/m/Y', strtotime($membre['date_creation']))) ?></div>
    </div>
  </div>
</div>

<div style="display:flex;gap:10px;margin-top:20px;flex-wrap:wrap">
  <?php if (($amitie['statut'] ?? '') === 'accepte'): ?>
    <a class="btn btn-primaire btn-petit" href="#/chat/<?= (int)$membre['id'] ?>" data-nav="chat/<?= (int)$membre['id'] ?>"><i class="ph ph-chat-circle-dots" aria-hidden="true"></i> Message</a>
    <button type="button" class="btn btn-ghost btn-petit" data-ami-retirer="<?= (int)$membre['id'] ?>">Retirer l'ami</button>
  <?php elseif (($amitie['statut'] ?? '') === 'en_attente' && (int)$amitie['demandeur_id'] === $uid): ?>
    <button type="button" class="btn btn-secondaire btn-petit" disabled>Demande envoyée</button>
  <?php elseif (($amitie['statut'] ?? '') === 'en_attente'): ?>
    <button type="button" class="btn btn-primaire btn-petit" data-ami-repondre="<?= (int)$amitie['ami_id'] ?>" data-statut="accepte">Accepter</button>
    <button type="button" class="btn btn-ghost btn-petit" data-ami-repondre="<?= (int)$amitie['ami_id'] ?>" data-statut="refuse">Refuser</button>
  <?php else: ?>
    <button type="button" class="btn btn-primaire btn-petit" data-ami-envoyer="<?= (int)$membre['id'] ?>">Ajouter en ami</button>
  <?php endif; ?>
  <a class="btn btn-ghost btn-petit" href="#/amis" data-nav="amis">← Retour aux amis</a>
</div>
