<?php
require_once __DIR__ . '/../../inclure/config.php';
require_once __DIR__ . '/../../inclure/fonctions.php';
demarrer_session();

$erreur  = '';
$succes  = '';
$token   = $_GET['token'] ?? $_POST['token'] ?? '';

$token_valide = !empty($_SESSION['reset_token'])
    && hash_equals($_SESSION['reset_token'], $token)
    && time() < ($_SESSION['reset_expire'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mdp  = $_POST['mot_de_passe'] ?? '';
    $conf = $_POST['confirm_mdp']  ?? '';

    if (!$token_valide) {
        $erreur = "Lien invalide ou expiré.";
    } elseif (strlen($mdp) < 6 || $mdp !== $conf) {
        $erreur = "Mots de passe invalides ou différents (6 caractères minimum).";
    } else {
        $hash = password_hash($mdp, PASSWORD_DEFAULT);
        $req  = $bdd->prepare("UPDATE utilisateurs SET mot_de_passe = :mdp WHERE id = :id");
        $req->execute(['mdp' => $hash, 'id' => $_SESSION['reset_user_id']]);

        unset($_SESSION['reset_token'], $_SESSION['reset_user_id'], $_SESSION['reset_expire']);
        $succes = "Mot de passe modifié avec succès ! Vous pouvez vous connecter.";
        $token_valide = false;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ESGISchat — Nouveau mot de passe</title>
  <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>

<div style="min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px">
  <div style="width:100%;max-width:400px">
    <div style="text-align:center;margin-bottom:32px">
      <div style="font-family:'Space Grotesk',sans-serif;font-size:28px;font-weight:700;background:linear-gradient(135deg,#fff 30%,var(--accent-clair));-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;margin-bottom:6px">ESGISchat</div>
    </div>

    <div class="carte">
      <div class="carte-corps">
        <h2 style="font-size:20px;margin-bottom:16px">Nouveau mot de passe</h2>

        <?php if ($erreur): ?>
          <div class="toast erreur" style="position:static;margin-bottom:16px"><?= e($erreur) ?></div>
        <?php endif; ?>
        <?php if ($succes): ?>
          <div class="toast succes" style="position:static;margin-bottom:16px"><?= e($succes) ?></div>
        <?php endif; ?>

        <?php if ($token_valide && !$succes): ?>
          <form method="post">
            <input type="hidden" name="token" value="<?= e($token) ?>">
            <div class="groupe-champ">
              <label class="champ-label">NOUVEAU MOT DE PASSE</label>
              <input type="password" name="mot_de_passe" class="champ" placeholder="Min. 6 caractères" required>
            </div>
            <div class="groupe-champ">
              <label class="champ-label">CONFIRMER</label>
              <input type="password" name="confirm_mdp" class="champ" placeholder="••••••••" required>
            </div>
            <button type="submit" class="btn btn-primaire" style="width:100%;padding:13px">Réinitialiser</button>
          </form>
        <?php endif; ?>

        <p style="text-align:center;margin-top:16px;font-size:13px;color:var(--texte-3)">
          <a href="../../index.php">← Retour à la connexion</a>
        </p>
      </div>
    </div>
  </div>
</div>
</body>
</html>
