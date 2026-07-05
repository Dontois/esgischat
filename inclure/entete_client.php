<?php
// Ce fichier affiche le haut de la page : topbar + sidebar gauche.
// Variables attendues avant l'include : $titre_page, $page_actuelle, $utilisateur
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ESGISchat — <?= e($titre_page) ?></title>
  <link rel="stylesheet" href="../../assets/css/style.css">
  <script src="https://unpkg.com/@phosphor-icons/web"></script>
  <script src="../../assets/js/app.js" defer></script>
  <script src="../../assets/js/chat-live.js" defer></script>
  <script src="../../assets/js/mobile-nav.js" defer></script>
</head>
<body>
<?php $utilisateur = $utilisateur ?? []; ?>
<script>
  window.appUser = <?= json_encode($utilisateur) ?>;
</script>
<div id="zone-toast"></div>

<div class="app-layout">

  <!-- Topbar -->
  <header class="topbar">
    <button class="topbar-burger" type="button" aria-label="Ouvrir le menu"><i class="ph ph-list" aria-hidden="true"></i></button>
    <div class="topbar-logo">ESGISchat</div>
    <nav class="topbar-actions">
      <a class="topbar-profil" href="profil.php">
        <?php if (!empty($utilisateur['photo_profil'])): ?>
          <img src="../../uploads/avatars/<?= e($utilisateur['photo_profil'] ?? '') ?>" class="avatar avatar-sm" alt="">
        <?php else: ?>
          <div class="avatar avatar-sm"><?= e(($utilisateur['prenom'] ?? '') !== '' ? mb_substr($utilisateur['prenom'], 0, 1) . mb_substr($utilisateur['nom'] ?? '', 0, 1) : '') ?></div>
        <?php endif; ?>
        <span class="topbar-profil-nom"><?= e($utilisateur['prenom'] ?? '') ?></span>
      </a>
      <a class="topbar-btn-icone" href="../../deconnexion.php" title="Se déconnecter"><i class="ph ph-sign-out" aria-hidden="true"></i></a>
    </nav>
  </header>

  <!-- Sidebar gauche -->
  <aside class="sidebar" id="sidebar-mobile">
    <span class="sidebar-titre">Menu</span>
    <a class="nav-item <?= $page_actuelle === 'accueil' ? 'actif' : '' ?>" href="accueil.php">
      <span class="nav-icone"><i class="ph ph-house" aria-hidden="true"></i></span> Accueil
    </a>
    <a class="nav-item <?= $page_actuelle === 'amis' ? 'actif' : '' ?>" href="amis.php">
      <span class="nav-icone"><i class="ph ph-users" aria-hidden="true"></i></span> Amis
    </a>
    <a class="nav-item <?= $page_actuelle === 'chat' ? 'actif' : '' ?>" href="chat.php">
      <span class="nav-icone"><i class="ph ph-chat-circle-dots" aria-hidden="true"></i></span> Messages
    </a>
    <a class="nav-item <?= $page_actuelle === 'profil' ? 'actif' : '' ?>" href="profil.php">
      <span class="nav-icone"><i class="ph ph-user-circle" aria-hidden="true"></i></span> Mon profil
    </a>
    <div style="flex:1"></div>
    <a class="nav-item" href="../../deconnexion.php" style="color:var(--danger)">
      <span class="nav-icone"><i class="ph ph-sign-out" aria-hidden="true"></i></span> Déconnexion
    </a>
  </aside>

  <div class="sidebar-backdrop" id="sidebar-backdrop"></div>

  <!-- Contenu central -->
  <main class="contenu-central">
    <?php afficher_flash(); ?>
