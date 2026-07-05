<?php
if (!defined('SPA_MODE')) {
    header('Location: ../../index.html#/amis');
    exit;
}

$uid = (int)$utilisateur['id'];
$filtre = $_GET['filtre'] ?? 'tous';

$req = $bdd->prepare("
    SELECT a.id AS ami_id, u.id, u.nom, u.prenom, u.photo_profil
    FROM amis a
    JOIN utilisateurs u ON u.id = a.demandeur_id
    WHERE a.receveur_id = :uid AND a.statut = 'en_attente'
    ORDER BY a.date_creation DESC
");
$req->execute(['uid' => $uid]);
$demandes_recues = $req->fetchAll();

$req = $bdd->prepare("
    SELECT u.id, u.nom, u.prenom, u.photo_profil, u.bio,
           a.statut AS statut_ami, a.demandeur_id, a.id AS ami_id
    FROM utilisateurs u
    LEFT JOIN amis a ON (
        (a.demandeur_id = :uid AND a.receveur_id = u.id) OR
        (a.receveur_id = :uid AND a.demandeur_id = u.id)
    )
    WHERE u.id != :uid AND u.role = 'user'
    ORDER BY u.prenom ASC
");
$req->execute(['uid' => $uid]);
$membres = $req->fetchAll();

if ($filtre === 'amis') {
    $membres = array_values(array_filter($membres, fn($m) => ($m['statut_ami'] ?? '') === 'accepte'));
}
?>

<?php if ($demandes_recues): ?>
  <div class="carte" style="margin-bottom:20px">
    <div class="carte-corps">
      <h3 style="margin-bottom:12px">Demandes d'amitié reçues</h3>
      <?php foreach ($demandes_recues as $d): ?>
        <div style="display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid var(--bord)">
          <?php if (!empty($d['photo_profil'])): ?>
            <img src="<?= e(chemin_upload('avatars', $d['photo_profil'])) ?>" class="avatar avatar-sm" alt="">
          <?php else: ?>
            <div class="avatar avatar-sm"><?= e(initiales_avatar($d['prenom'], $d['nom'])) ?></div>
          <?php endif; ?>
          <div style="flex:1">
            <a href="#/membre/<?= (int)$d['id'] ?>" data-nav="membre/<?= (int)$d['id'] ?>"><?= e($d['prenom'] . ' ' . $d['nom']) ?></a>
          </div>
          <button type="button" class="btn btn-primaire btn-petit" data-ami-repondre="<?= (int)$d['ami_id'] ?>" data-statut="accepte">Accepter</button>
          <button type="button" class="btn btn-ghost btn-petit" data-ami-repondre="<?= (int)$d['ami_id'] ?>" data-statut="refuse">Refuser</button>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
<?php endif; ?>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px">
  <h2 style="font-size:18px">Membres du réseau</h2>
  <div style="display:flex;gap:8px">
    <a class="btn btn-secondaire btn-petit <?= $filtre === 'tous' ? 'actif' : '' ?>" href="#/amis/tous" data-nav="amis/tous">Tous</a>
    <a class="btn btn-secondaire btn-petit <?= $filtre === 'amis' ? 'actif' : '' ?>" href="#/amis/amis" data-nav="amis/amis">Mes amis</a>
  </div>
</div>

<div class="amis-grille">
  <?php foreach ($membres as $m): ?>
    <div class="ami-carte carte">
      <div class="carte-corps" style="text-align:center">
        <?php if (!empty($m['photo_profil'])): ?>
          <img src="<?= e(chemin_upload('avatars', $m['photo_profil'])) ?>" class="avatar avatar-md" alt="">
        <?php else: ?>
          <div class="avatar avatar-md"><?= e(initiales_avatar($m['prenom'], $m['nom'])) ?></div>
        <?php endif; ?>
        <div style="font-weight:600;margin-top:8px">
          <a href="#/membre/<?= (int)$m['id'] ?>" data-nav="membre/<?= (int)$m['id'] ?>"><?= e($m['prenom'] . ' ' . $m['nom']) ?></a>
        </div>
        <div style="font-size:12px;color:var(--texte-3);margin-bottom:10px"><?= e($m['bio'] ?? '') ?></div>

        <?php if (($m['statut_ami'] ?? '') === 'accepte'): ?>
          <button type="button" class="btn btn-ghost btn-petit" data-ami-retirer="<?= (int)$m['id'] ?>">Retirer l'ami</button>
        <?php elseif (($m['statut_ami'] ?? '') === 'en_attente' && (int)$m['demandeur_id'] === $uid): ?>
          <button type="button" class="btn btn-secondaire btn-petit" disabled>Demande envoyée</button>
        <?php elseif (($m['statut_ami'] ?? '') === 'en_attente'): ?>
          <span style="font-size:12px;color:var(--texte-3)">Demande reçue</span>
        <?php else: ?>
          <button type="button" class="btn btn-primaire btn-petit" data-ami-envoyer="<?= (int)$m['id'] ?>">Ajouter en ami</button>
        <?php endif; ?>
      </div>
    </div>
  <?php endforeach; ?>
</div>
