<?php
require_once __DIR__ . '/../../inclure/config.php';
require_once __DIR__ . '/../../inclure/fonctions.php';
demarrer_session();

function est_requete_ajax() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

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
        if (est_requete_ajax()) {
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode(['success' => true, 'message' => 'Connexion réussie.', 'redirect' => 'dashboard.php']);
            exit;
        }
        header('Location: dashboard.php');
        exit;
    }

    if (est_requete_ajax()) {
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(['success' => false, 'message' => $erreur]);
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

    <div id="admin-login-message">
      <?php if ($erreur): ?>
        <div class="toast erreur" style="position:static;margin-bottom:16px"><?= e($erreur) ?></div>
      <?php endif; ?>
    </div>

    <form method="post" id="admin-login-form">
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
<script>
document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('admin-login-form');
  const messageBox = document.getElementById('admin-login-message');
  if (!form || !messageBox) return;

  form.addEventListener('submit', async (event) => {
    event.preventDefault();
    const formData = new FormData(form);
    const response = await fetch(window.location.href, {
      method: 'POST',
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
      body: formData,
    });
    const data = await response.json().catch(() => null);
    if (data?.success) {
      window.location.href = data.redirect || 'dashboard.php';
      return;
    }
    messageBox.innerHTML = `<div class="toast erreur" style="position:static;margin-bottom:16px">${data?.message || 'Erreur de connexion.'}</div>`;
  });
});
</script>
</body>
</html>
