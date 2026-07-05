<?php
require_once __DIR__ . '/../../inclure/config.php';
require_once __DIR__ . '/../../inclure/fonctions.php';
demarrer_session();

if (!empty($_SESSION['admin'])) {
    header('Location: dashboard.php');
    exit;
}

$erreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $mdp   = $_POST['mot_de_passe'] ?? '';

    $req = $bdd->prepare("SELECT * FROM utilisateurs WHERE email = :email");
    $req->execute(['email' => $email]);
    $utilisateur = $req->fetch();

    if (!$utilisateur || !password_verify($mdp, $utilisateur['mot_de_passe']) || !in_array($utilisateur['role'], ['moderateur', 'admin'])) {
        $erreur = "Identifiants incorrects.";
    } else {
        unset($utilisateur['mot_de_passe']);
        $_SESSION['admin'] = $utilisateur;
        header('Location: dashboard.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ESGISchat — Administration</title>
  <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>

<div class="admin-login">
  <div class="admin-login-boite">
    <div class="admin-login-logo">ESGIS<span>chat</span></div>
    <div class="admin-login-sub">Portail d'administration</div>

    <?php if ($erreur): ?>
      <div class="toast erreur" style="position:static;margin-bottom:16px"><?= e($erreur) ?></div>
    <?php endif; ?>

    <form method="post">
      <div class="groupe-champ">
        <label class="champ-label">EMAIL</label>
        <input type="email" name="email" class="champ" placeholder="admin@reseau.com" required>
      </div>
      <div class="groupe-champ">
        <label class="champ-label">MOT DE PASSE</label>
        <input type="password" name="mot_de_passe" class="champ" placeholder="••••••••" required>
      </div>
      <button type="submit" class="btn btn-primaire" style="width:100%;padding:13px">Accéder au tableau de bord</button>
    </form>
    <p style="text-align:center;margin-top:20px;font-size:13px;color:var(--texte-3)">
      <a href="../../index.html">← Espace utilisateur</a>
    </p>
  </div>
</div>
</body>
</html>
