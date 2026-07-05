<?php
require_once __DIR__ . '/../../inclure/config.php';
require_once __DIR__ . '/../../inclure/fonctions.php';
demarrer_session();

function est_requete_ajax() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

$message = '';
$lien_test = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if ($email !== '' && email_valide($email)) {
        $req = $bdd->prepare("SELECT id, prenom FROM utilisateurs WHERE email = :email");
        $req->execute(['email' => $email]);
        $utilisateur = $req->fetch();

        if ($utilisateur) {
            $token = bin2hex(random_bytes(16));
            $_SESSION['reset_token']   = $token;
            $_SESSION['reset_user_id'] = $utilisateur['id'];
            $_SESSION['reset_expire']  = time() + 3600;

            $lien_test = 'reset-password.php?token=' . $token;
            $html = '<div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto;padding:24px;background:#0d0f1a;color:#e8eaf6;border-radius:16px">';
            $html .= '<h2 style="margin:0 0 12px">Réinitialisation de mot de passe</h2>';
            $html .= '<p>Bonjour ' . e($utilisateur['prenom']) . ',</p>';
            $html .= '<p>Vous avez demandé à réinitialiser votre mot de passe. Cliquez sur le bouton ci-dessous pour continuer.</p>';
            $html .= '<p><a href="' . e($lien_test) . '" style="display:inline-block;padding:12px 18px;background:#7c6ef5;color:#fff;text-decoration:none;border-radius:10px">Réinitialiser mon mot de passe</a></p>';
            $html .= '<p>Si vous n’êtes pas à l’origine de cette demande, ignorez simplement cet email.</p>';
            $html .= '</div>';
            envoyer_email_html($email, 'Réinitialisation de mot de passe ESGISchat', $html);
        }
    }

    // On affiche toujours le même message, même si l'email n'existe pas (sécurité)
    $message = "Si cet email existe dans notre base, un lien de réinitialisation a été envoyé.";

    if (est_requete_ajax()) {
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(['success' => true, 'message' => $message, 'lien_test' => $lien_test]);
        exit;
    }
}

$titre_page = "Mot de passe oublié";
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ESGISchat — <?= e($titre_page) ?></title>
  <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>

<div style="min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px">
  <div style="width:100%;max-width:400px">
    <div style="text-align:center;margin-bottom:32px">
      <div style="font-family:'Space Grotesk',sans-serif;font-size:28px;font-weight:700;background:linear-gradient(135deg,#fff 30%,var(--accent-clair));-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;margin-bottom:6px">ESGISchat</div>
      <p style="color:var(--texte-2);font-size:14px">Réinitialisation de mot de passe</p>
    </div>

    <div class="carte">
      <div class="carte-corps">
        <h2 style="font-size:20px;margin-bottom:6px">Mot de passe oublié ?</h2>
        <p style="color:var(--texte-2);font-size:14px;margin-bottom:24px">Entrez votre adresse email pour générer un lien de réinitialisation.</p>

        <div id="forgot-message">
          <?php if ($message): ?>
            <div class="toast succes" style="position:static;margin-bottom:16px"><?= e($message) ?></div>
          <?php endif; ?>
        </div>

        <div id="forgot-test-link">
          <?php if ($lien_test): ?>
            <div class="toast" style="position:static;margin-bottom:16px">
              Lien de test (simule l'email) :
              <a href="<?= e($lien_test) ?>"><?= e($lien_test) ?></a>
            </div>
          <?php endif; ?>
        </div>

        <form method="post">
          <div class="groupe-champ">
            <label class="champ-label">VOTRE EMAIL</label>
            <input type="email" name="email" class="champ" placeholder="votre@email.com">
          </div>
          <button type="submit" class="btn btn-primaire" style="width:100%;padding:13px">Envoyer le lien</button>
          <p style="text-align:center;margin-top:16px;font-size:13px;color:var(--texte-3)">
            <a href="../../index.php">← Retour à la connexion</a>
          </p>
        </form>
      </div>
    </div>
  </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', () => {
  const form = document.querySelector('form');
  const messageBox = document.getElementById('forgot-message');
  const linkBox = document.getElementById('forgot-test-link');
  if (!form) return;

  form.addEventListener('submit', async (event) => {
    event.preventDefault();
    const formData = new FormData(form);
    const response = await fetch(window.location.href, {
      method: 'POST',
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
      body: formData,
    });
    const data = await response.json().catch(() => null);

    if (messageBox) {
      messageBox.innerHTML = `<div class="toast succes" style="position:static;margin-bottom:16px">${data?.message || 'Demande envoyée.'}</div>`;
    }

    if (linkBox) {
      linkBox.innerHTML = data?.lien_test
        ? `<div class="toast" style="position:static;margin-bottom:16px">Lien de test : <a href="${data.lien_test}">${data.lien_test}</a></div>`
        : '';
    }
  });
});
</script>
</body>
</html>
