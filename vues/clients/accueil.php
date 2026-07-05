<?php
if (!defined('SPA_MODE')) {
  header('Location: ../../index.html#/accueil');
  exit;
}

// Garantit la présence des variables attendues (évite les notices et erreurs d'analyse statique)
$utilisateur = $utilisateur ?? ($_SESSION['utilisateur'] ?? null);
$page_actuelle = $page_actuelle ?? 'accueil';

require_once __DIR__ . '/../../inclure/publications.php';

$uid = (int)($utilisateur['id'] ?? 0);
[$publications, $commentaires_par_pub] = charger_publications_flux($bdd, $uid, $utilisateur);
?>

<div class="boite-publication">
  <form enctype="multipart/form-data" data-pub-form>
    <div class="boite-pub-top">
      <?php if (!empty($utilisateur['photo_profil'])): ?>
        <img src="<?= e(chemin_upload('avatars', $utilisateur['photo_profil'])) ?>" class="avatar avatar-md" alt="" data-user-avatar>
      <?php else: ?>
        <div class="avatar avatar-md" data-user-avatar><?= e(initiales_avatar($utilisateur['prenom'], $utilisateur['nom'])) ?></div>
      <?php endif; ?>
      <textarea name="contenu" placeholder="Quoi de neuf ? Partagez quelque chose..." rows="2"></textarea>
    </div>
    <div class="boite-pub-bas">
      <label class="btn-media" for="pub-image-input"><i class="ph ph-image" aria-hidden="true"></i> Photo</label>
      <input type="file" id="pub-image-input" name="image" accept="image/*" style="display:none">
      <button type="submit" class="btn btn-primaire btn-petit" style="margin-left:auto"><i class="ph ph-paper-plane-right" aria-hidden="true"></i> Publier</button>
    </div>
    <div class="pub-image-preview" data-pub-preview>
      <img src="" alt="Aperçu de l'image" data-pub-preview-img>
      <div class="pub-image-preview-info">
        <div class="pub-image-preview-name" data-pub-preview-name></div>
        <div class="pub-image-preview-actions">
          <button type="button" class="btn btn-danger btn-petit" data-remove-pub-image><i class="ph ph-x" aria-hidden="true"></i> Retirer</button>
        </div>
      </div>
    </div>
  </form>
</div>

<div id="liste-flux">
  <?php if (!$publications): ?>
    <div class="empty-state">Aucune publication pour le moment. Soyez le premier à publier !</div>
  <?php endif; ?>

  <?php foreach ($publications as $p): ?>
    <?= render_publication_html($p, $uid, $utilisateur, $commentaires_par_pub[$p['id']] ?? []) ?>
  <?php endforeach; ?>
</div>
