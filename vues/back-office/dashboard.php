<?php
require_once __DIR__ . '/../../inclure/config.php';
require_once __DIR__ . '/../../inclure/fonctions.php';
demarrer_session();
$admin = verifier_connexion_admin('moderateur');

function est_requete_ajax() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

// Traitement des actions de modération

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $json = null;

    if ($action === 'supprimer_publication') {
        $id = intval($_POST['id'] ?? 0);
        $bdd->prepare("DELETE FROM publications WHERE id = :id")->execute(['id' => $id]);
        $json = ['success' => true, 'action' => 'supprimer_publication', 'message' => 'Publication supprimée.'];
    }

    if ($action === 'supprimer_utilisateur') {
        $id = intval($_POST['id'] ?? 0);
        $bdd->prepare("DELETE FROM utilisateurs WHERE id = :id AND role = 'user'")->execute(['id' => $id]);
        $json = ['success' => true, 'action' => 'supprimer_utilisateur', 'message' => 'Utilisateur supprimé.'];
    }

    if ($action === 'ajouter_staff' && $admin['role'] === 'admin') {
        $nom    = trim($_POST['nom']    ?? '');
        $prenom = trim($_POST['prenom'] ?? '');
        $email  = trim($_POST['email']  ?? '');
        $role   = $_POST['role']        ?? '';
        $mdp    = $_POST['mot_de_passe'] ?? '';

        if (!$email || !in_array($role, ['moderateur', 'admin']) || strlen($mdp) < 8) {
            $json = ['success' => false, 'message' => 'Champs invalides (email, rôle et mot de passe de 8 caractères minimum requis).'];
        } else {
            $req = $bdd->prepare("SELECT id FROM utilisateurs WHERE email = :email");
            $req->execute(['email' => $email]);
            if ($req->fetch()) {
                $json = ['success' => false, 'message' => 'Cet email est déjà utilisé.'];
            } else {
                $hash = password_hash($mdp, PASSWORD_DEFAULT);
                $bdd->prepare("INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe, role) VALUES (:nom, :prenom, :email, :mdp, :role)")
                    ->execute(['nom' => strip_tags($nom), 'prenom' => strip_tags($prenom), 'email' => $email, 'mdp' => $hash, 'role' => $role]);
                $id = (int)$bdd->lastInsertId();
                $staff = $bdd->prepare("SELECT id, nom, prenom, email, role FROM utilisateurs WHERE id = :id")->execute(['id' => $id]);
                $staff = $bdd->prepare("SELECT id, nom, prenom, email, role FROM utilisateurs WHERE id = :id");
                $staff->execute(['id' => $id]);
                $staff_user = $staff->fetch();
                $row_html = '<tr><td>' . e($staff_user['prenom'] . ' ' . $staff_user['nom']) . '</td><td>' . e($staff_user['email']) . '</td><td>' . e($staff_user['role']) . '</td><td><span style="color:var(--texte-3);font-size:12px">Modifiable</span></td></tr>';
                $json = ['success' => true, 'action' => 'ajouter_staff', 'message' => "Compte $role créé avec succès.", 'row_html' => $row_html];
            }
        }
    }

    if ($action === 'changer_role' && $admin['role'] === 'admin') {
        $id           = intval($_POST['id'] ?? 0);
        $nouveau_role = $_POST['role'] ?? '';
        if ($id && in_array($nouveau_role, ['user', 'moderateur', 'admin'])) {
            $bdd->prepare("UPDATE utilisateurs SET role = :role WHERE id = :id")->execute(['role' => $nouveau_role, 'id' => $id]);
            $json = ['success' => true, 'action' => 'changer_role', 'message' => 'Rôle mis à jour.', 'new_role' => $nouveau_role, 'new_label' => $nouveau_role === 'admin' ? 'Rétrograder en modérateur' : 'Promouvoir admin'];
        } else {
            $json = ['success' => false, 'message' => 'Rôle invalide.'];
        }
    }

    if ($json !== null) {
        if (est_requete_ajax()) {
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode($json);
            exit;
        }

        if ($json['success']) {
            message_flash($json['message']);
        } else {
            message_flash($json['message'], 'erreur');
        }
    }

    header('Location: dashboard.php?section=' . ($_POST['retour_section'] ?? 'dashboard'));
    exit;
}

$section = $_GET['section'] ?? 'dashboard';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ESGISchat — Tableau de bord</title>
  <link rel="stylesheet" href="../../assets/css/style.css">
  <script src="https://unpkg.com/@phosphor-icons/web"></script>
</head>
<body>

<div class="admin-layout">

  <aside class="admin-sidebar">
    <div class="admin-sidebar-logo">ESGIS<span>chat</span></div>

    <span class="sidebar-titre">Navigation</span>
    <a class="nav-item <?= $section === 'dashboard' ? 'actif' : '' ?>" href="dashboard.php?section=dashboard">
      <span class="nav-icone"><i class="ph ph-chart-line-up" aria-hidden="true"></i></span> Tableau de bord
    </a>
    <a class="nav-item <?= $section === 'utilisateurs' ? 'actif' : '' ?>" href="dashboard.php?section=utilisateurs">
      <span class="nav-icone"><i class="ph ph-users" aria-hidden="true"></i></span> Utilisateurs
    </a>
    <a class="nav-item <?= $section === 'publications' ? 'actif' : '' ?>" href="dashboard.php?section=publications">
      <span class="nav-icone"><i class="ph ph-newspaper" aria-hidden="true"></i></span> Publications
    </a>
    <?php if ($admin['role'] === 'admin'): ?>
      <a class="nav-item <?= $section === 'staff' ? 'actif' : '' ?>" href="dashboard.php?section=staff">
        <span class="nav-icone"><i class="ph ph-shield-check" aria-hidden="true"></i></span> Équipe
      </a>
    <?php endif; ?>

    <div style="flex:1"></div>
    <div style="padding:12px;border-top:1px solid var(--bord);margin-top:8px">
      <div style="font-size:13px;color:var(--texte-2);margin-bottom:10px">
        <?= e($admin['prenom'] . ' ' . $admin['nom']) ?> (<?= e($admin['role']) ?>)
      </div>
      <a href="../../deconnexion.php" class="btn btn-ghost btn-petit" style="width:100%;display:block;text-align:center;color:var(--danger)"><i class="ph ph-sign-out" aria-hidden="true"></i> Déconnexion</a>
    </div>
  </aside>

  <div class="admin-contenu">
    <div id="admin-flash">
      <?php afficher_flash(); ?>
    </div>

    <?php if ($section === 'dashboard'): ?>
      <?php
        $stats = [];
        $stats['total_utilisateurs'] = $bdd->query("SELECT COUNT(*) FROM utilisateurs WHERE role='user'")->fetchColumn();
        $stats['total_publications'] = $bdd->query("SELECT COUNT(*) FROM publications")->fetchColumn();
        $stats['total_commentaires'] = $bdd->query("SELECT COUNT(*) FROM commentaires")->fetchColumn();
        $stats['total_messages']     = $bdd->query("SELECT COUNT(*) FROM messages")->fetchColumn();
        $stats['total_amis']         = $bdd->query("SELECT COUNT(*) FROM amis WHERE statut='accepte'")->fetchColumn();
        $stats['total_likes']        = $bdd->query("SELECT COUNT(*) FROM reactions WHERE type='like'")->fetchColumn();
        $stats['nouveaux_7j']        = $bdd->query("SELECT COUNT(*) FROM utilisateurs WHERE date_creation >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetchColumn();

        $top_auteurs = $bdd->query("
            SELECT u.nom, u.prenom, COUNT(p.id) AS nb_publications
            FROM utilisateurs u LEFT JOIN publications p ON p.auteur_id = u.id
            WHERE u.role='user' GROUP BY u.id ORDER BY nb_publications DESC LIMIT 5
        ")->fetchAll();
      ?>
      <div class="admin-page-titre">Tableau de bord</div>
      <div class="admin-page-sous">Vue d'ensemble de l'activité ESGISchat</div>

      <div class="stats-grille">
        <div class="stat-carte"><div style="font-size:24px;font-weight:700"><?= (int)$stats['total_utilisateurs'] ?></div><div style="color:var(--texte-3)">Utilisateurs</div></div>
        <div class="stat-carte"><div style="font-size:24px;font-weight:700"><?= (int)$stats['total_publications'] ?></div><div style="color:var(--texte-3)">Publications</div></div>
        <div class="stat-carte"><div style="font-size:24px;font-weight:700"><?= (int)$stats['total_commentaires'] ?></div><div style="color:var(--texte-3)">Commentaires</div></div>
        <div class="stat-carte"><div style="font-size:24px;font-weight:700"><?= (int)$stats['total_messages'] ?></div><div style="color:var(--texte-3)">Messages</div></div>
        <div class="stat-carte"><div style="font-size:24px;font-weight:700"><?= (int)$stats['total_amis'] ?></div><div style="color:var(--texte-3)">Amitiés</div></div>
        <div class="stat-carte"><div style="font-size:24px;font-weight:700"><?= (int)$stats['total_likes'] ?></div><div style="color:var(--texte-3)">Likes</div></div>
        <div class="stat-carte"><div style="font-size:24px;font-weight:700"><?= (int)$stats['nouveaux_7j'] ?></div><div style="color:var(--texte-3)">Nouveaux (7j)</div></div>
      </div>

      <div class="admin-table-wrap" style="padding:20px;margin-top:20px">
        <div class="widget-titre">Top 5 auteurs</div>
        <?php foreach ($top_auteurs as $t): ?>
          <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--bord)">
            <span><?= e($t['prenom'] . ' ' . $t['nom']) ?></span>
            <strong><?= (int)$t['nb_publications'] ?> publication(s)</strong>
          </div>
        <?php endforeach; ?>
      </div>

    <?php elseif ($section === 'utilisateurs'): ?>
      <?php
        $utilisateurs = $bdd->query("
            SELECT u.id, u.nom, u.prenom, u.email, u.role, u.photo_profil, u.date_creation, COUNT(p.id) AS nb_publications
            FROM utilisateurs u LEFT JOIN publications p ON p.auteur_id = u.id
            GROUP BY u.id ORDER BY u.date_creation DESC
        ")->fetchAll();
      ?>
      <div class="admin-page-titre">Gestion des utilisateurs</div>
      <div class="admin-page-sous">Consulter et modérer les comptes utilisateurs</div>
      <div class="admin-table-wrap">
        <table class="admin-table">
          <thead><tr><th>Utilisateur</th><th>Email</th><th>Rôle</th><th>Publications</th><th>Inscrit le</th><th>Actions</th></tr></thead>
          <tbody>
            <?php foreach ($utilisateurs as $u): ?>
              <tr>
                <td style="display:flex;align-items:center;gap:8px">
                  <?php if (!empty($u['photo_profil'])): ?>
                    <img src="<?= e('../../uploads/avatars/' . $u['photo_profil']) ?>" class="avatar avatar-sm" alt="">
                  <?php else: ?>
                    <div class="avatar avatar-sm"><?= e(initiales_avatar($u['prenom'], $u['nom'])) ?></div>
                  <?php endif; ?>
                  <?= e($u['prenom'] . ' ' . $u['nom']) ?>
                </td>
                <td><?= e($u['email']) ?></td>
                <td><?= e($u['role']) ?></td>
                <td><?= (int)$u['nb_publications'] ?></td>
                <td><?= e(date('d/m/Y', strtotime($u['date_creation']))) ?></td>
                <td>
                  <?php if ($u['role'] === 'user'): ?>
                    <form method="post" data-admin-form data-admin-action="supprimer_utilisateur" onsubmit="return confirm('Supprimer définitivement ce compte ?');">
                      <input type="hidden" name="action" value="supprimer_utilisateur">
                      <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                      <input type="hidden" name="retour_section" value="utilisateurs">
                      <button type="submit" class="btn btn-ghost btn-petit" style="color:var(--danger)">Supprimer</button>
                    </form>
                  <?php else: ?>
                    <span style="color:var(--texte-3);font-size:12px">Protégé</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

    <?php elseif ($section === 'publications'): ?>
      <?php
        $publications = $bdd->query("
            SELECT p.id, p.contenu, p.image, p.date_creation, u.nom, u.prenom,
                   (SELECT COUNT(*) FROM commentaires c WHERE c.publication_id = p.id) AS nb_commentaires,
                   (SELECT COUNT(*) FROM reactions r WHERE r.publication_id = p.id AND r.type='like') AS nb_likes
            FROM publications p JOIN utilisateurs u ON u.id = p.auteur_id
            ORDER BY p.date_creation DESC
        ")->fetchAll();
      ?>
      <div class="admin-page-titre">Gestion des publications</div>
      <div class="admin-page-sous">Modérer le contenu publié sur ESGISchat</div>
      <div class="admin-table-wrap">
        <table class="admin-table">
          <thead><tr><th>Auteur</th><th>Image</th><th>Contenu</th><th>Likes</th><th>Commentaires</th><th>Date</th><th>Actions</th></tr></thead>
          <tbody>
            <?php foreach ($publications as $p): ?>
              <tr>
                <td><?= e($p['prenom'] . ' ' . $p['nom']) ?></td>
                <td>
                  <?php if (!empty($p['image'])): ?>
                    <a href="<?= e('../../uploads/posts/' . $p['image']) ?>" target="_blank" rel="noopener" title="Voir en grand">
                      <img src="<?= e('../../uploads/posts/' . $p['image']) ?>" alt="" style="width:56px;height:56px;object-fit:cover;border-radius:8px;display:block">
                    </a>
                  <?php else: ?>
                    <span style="color:var(--texte-3);font-size:12px">—</span>
                  <?php endif; ?>
                </td>
                <td style="max-width:300px"><?= e(mb_strimwidth($p['contenu'] ?? '', 0, 80, '...')) ?></td>
                <td><?= (int)$p['nb_likes'] ?></td>
                <td><?= (int)$p['nb_commentaires'] ?></td>
                <td><?= e(date('d/m/Y', strtotime($p['date_creation']))) ?></td>
                <td>
                  <form method="post" data-admin-form data-admin-action="supprimer_publication" onsubmit="return confirm('Supprimer cette publication ?');">
                    <input type="hidden" name="action" value="supprimer_publication">
                    <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                    <input type="hidden" name="retour_section" value="publications">
                    <button type="submit" class="btn btn-ghost btn-petit" style="color:var(--danger)">Supprimer</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

    <?php elseif ($section === 'staff' && $admin['role'] === 'admin'): ?>
      <?php
        $staff = $bdd->query("SELECT id, nom, prenom, email, role FROM utilisateurs WHERE role IN ('moderateur','admin') ORDER BY role DESC")->fetchAll();
      ?>
      <div class="admin-page-titre">Équipe de modération</div>
      <div class="admin-page-sous">Gérer les administrateurs et modérateurs</div>

      <div class="carte" style="margin-bottom:20px">
        <div class="carte-corps">
          <h3 style="margin-bottom:12px">Ajouter un membre</h3>
          <form method="post" data-admin-form data-admin-action="ajouter_staff">
            <input type="hidden" name="action" value="ajouter_staff">
            <input type="hidden" name="retour_section" value="staff">
            <div class="grille-2">
              <div class="groupe-champ">
                <label class="champ-label">PRÉNOM</label>
                <input type="text" name="prenom" class="champ" placeholder="Jean" required>
              </div>
              <div class="groupe-champ">
                <label class="champ-label">NOM</label>
                <input type="text" name="nom" class="champ" placeholder="Dupont" required>
              </div>
            </div>
            <div class="groupe-champ">
              <label class="champ-label">EMAIL</label>
              <input type="email" name="email" class="champ" placeholder="jean@reseau.com" required>
            </div>
            <div class="groupe-champ">
              <label class="champ-label">MOT DE PASSE</label>
              <input type="password" name="mot_de_passe" class="champ" placeholder="Min. 8 caractères" required>
            </div>
            <div class="groupe-champ">
              <label class="champ-label">RÔLE</label>
              <select name="role" class="champ">
                <option value="moderateur">Modérateur</option>
                <option value="admin">Administrateur</option>
              </select>
            </div>
            <button type="submit" class="btn btn-primaire" style="width:100%">Créer le compte</button>
          </form>
        </div>
      </div>

      <div class="admin-table-wrap">
        <table class="admin-table">
          <thead><tr><th>Membre</th><th>Email</th><th>Rôle</th><th>Actions</th></tr></thead>
          <tbody>
            <?php foreach ($staff as $s): ?>
              <tr>
                <td><?= e($s['prenom'] . ' ' . $s['nom']) ?></td>
                <td><?= e($s['email']) ?></td>
                <td><?= e($s['role']) ?></td>
                <td>
                  <?php if ($s['id'] != $admin['id']): ?>
                    <form method="post" data-admin-form data-admin-action="changer_role" style="display:inline">
                      <input type="hidden" name="action" value="changer_role">
                      <input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
                      <input type="hidden" name="retour_section" value="staff">
                      <input type="hidden" name="role" value="<?= $s['role'] === 'admin' ? 'moderateur' : 'admin' ?>">
                      <button type="submit" class="btn btn-ghost btn-petit">
                        <?= $s['role'] === 'admin' ? 'Rétrograder en modérateur' : 'Promouvoir admin' ?>
                      </button>
                    </form>
                  <?php else: ?>
                    <span style="color:var(--texte-3);font-size:12px">Vous-même</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', () => {
  const flashBox = document.getElementById('admin-flash');
  const showFlash = (message, type = 'succes') => {
    if (!flashBox) return;
    flashBox.innerHTML = `<div class="toast ${type}" style="position:static;margin-bottom:16px">${message}</div>`;
  };

  document.querySelectorAll('form[data-admin-form]').forEach((form) => {
    form.addEventListener('submit', async (event) => {
      event.preventDefault();
      const formData = new FormData(form);
      const response = await fetch(window.location.href, {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: formData,
      });
      const data = await response.json().catch(() => null);
      if (!data?.success) {
        showFlash(data?.message || 'Erreur.', 'erreur');
        return;
      }

      showFlash(data.message || 'Action effectuée.');
      if (data.action === 'supprimer_publication' || data.action === 'supprimer_utilisateur') {
        form.closest('tr')?.remove();
      }

      if (data.action === 'ajouter_staff' && data.row_html) {
        const tbody = document.getElementById('staff-table-body');
        if (tbody) {
          tbody.insertAdjacentHTML('beforeend', data.row_html);
          form.reset();
        }
      }

      if (data.action === 'changer_role') {
        const row = form.closest('tr');
        const roleCell = row?.querySelector('td:nth-child(3)');
        const button = form.querySelector('button[type="submit"]');
        if (roleCell) roleCell.textContent = data.new_role || roleCell.textContent;
        if (button) button.textContent = data.new_label || button.textContent;
      }
    });
  });
});
</script>
</body>
</html>
