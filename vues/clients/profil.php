<?php
if (!defined('SPA_MODE')) {
    header('Location: ../../index.html#/profil');
    exit;
}

$uid = (int)$utilisateur['id'];

$req = $bdd->prepare("SELECT * FROM utilisateurs WHERE id = :id");
$req->execute(['id' => $uid]);
$profil = $req->fetch();
?>

<div class="profil-main">
  <aside class="profil-sidebar">
    <div class="profil-avatar-wrap">
      <?php if (!empty($profil['photo_profil'])): ?>
        <img src="<?= e(chemin_upload('avatars', $profil['photo_profil'])) ?>" class="avatar avatar-xl" alt="" data-user-avatar-lg>
      <?php else: ?>
        <div class="avatar avatar-xl" data-user-avatar-lg><?= e(initiales_avatar($profil['prenom'], $profil['nom'])) ?></div>
      <?php endif; ?>
    </div>
    <div class="profil-texte profil-sidebar-info">
      <div class="profil-nom" data-user-name><?= e($profil['prenom'] . ' ' . $profil['nom']) ?></div>
      <div class="profil-bio" data-user-bio><?= e($profil['bio'] ?? '') ?></div>
      <div class="profil-meta">Membre depuis le <?= e(date('d/m/Y', strtotime($profil['date_creation']))) ?></div>
    </div>
  </aside>

  <div class="profil-content">
    <div class="profil-onglets">
      <button type="button" class="profil-onglet actif" data-profil-tab="infos">Infos personnelles</button>
      <button type="button" class="profil-onglet" data-profil-tab="mdp">Mot de passe</button>
    </div>

    <div class="profil-form-section" id="section-infos">
  <h3>Modifier mes informations</h3>
  <form enctype="multipart/form-data" data-profil-infos-form>
    <div class="grille-2">
      <div class="groupe-champ">
        <label class="champ-label">PRÉNOM</label>
        <input type="text" name="prenom" class="champ" value="<?= e($profil['prenom']) ?>" required>
      </div>
      <div class="groupe-champ">
        <label class="champ-label">NOM</label>
        <input type="text" name="nom" class="champ" value="<?= e($profil['nom']) ?>" required>
      </div>
    </div>
    <div class="groupe-champ">
      <label class="champ-label">EMAIL</label>
      <input type="email" name="email" class="champ" value="<?= e($profil['email']) ?>" required>
    </div>
    <div class="groupe-champ">
      <label class="champ-label">BIO</label>
      <textarea name="bio" class="champ" placeholder="Parlez de vous..."><?= e($profil['bio'] ?? '') ?></textarea>
    </div>
    <div class="groupe-champ">
      <label class="champ-label">NOUVELLE PHOTO DE PROFIL</label>
      <input type="file" name="photo_profil" class="champ" accept="image/*">
    </div>
    <button type="submit" class="btn btn-primaire">Enregistrer</button>
  </form>
</div>

<div class="profil-form-section caché" id="section-mdp">
  <h3>Changer le mot de passe</h3>
  <form data-profil-mdp-form>
    <div class="groupe-champ">
      <label class="champ-label">ANCIEN MOT DE PASSE</label>
      <input type="password" name="ancien_mdp" class="champ" placeholder="••••••••" required>
    </div>
    <div class="groupe-champ">
      <label class="champ-label">NOUVEAU MOT DE PASSE</label>
      <input type="password" name="nouveau_mdp" class="champ" placeholder="Min. 6 caractères" required>
    </div>
    <div class="groupe-champ">
      <label class="champ-label">CONFIRMER</label>
      <input type="password" name="confirm_mdp" class="champ" placeholder="••••••••" required>
    </div>
    <button type="submit" class="btn btn-primaire">Modifier</button>
  </form>
</div>
  </div>
</div>
