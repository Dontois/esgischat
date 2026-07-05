<?php
// Rendu HTML d'une publication (flux SPA)

function render_publication_html($publication, $uid, $utilisateur, $commentaires) {
    ob_start();
    ?>
    <div class="pub-carte carte" data-pub-id="<?= (int)$publication['id'] ?>">
      <div class="carte-corps">
        <div style="display:flex;gap:10px;align-items:center">
          <?php if (!empty($publication['photo_profil'])): ?>
            <img src="<?= e(chemin_upload('avatars', $publication['photo_profil'])) ?>" class="avatar avatar-sm" alt="">
          <?php else: ?>
            <div class="avatar avatar-sm"><?= e(initiales_avatar($publication['prenom'], $publication['nom'])) ?></div>
          <?php endif; ?>
          <div style="flex:1">
            <div style="font-weight:600"><?= e($publication['prenom'] . ' ' . $publication['nom']) ?></div>
            <div style="font-size:12px;color:var(--texte-3)"><?= e(temps_relatif($publication['date_creation'])) ?></div>
          </div>
          <?php if ($publication['auteur_id'] == $uid || in_array($utilisateur['role'], ['moderateur', 'admin'])): ?>
            <button type="button" class="btn btn-ghost btn-petit" data-pub-delete="<?= (int)$publication['id'] ?>" title="Supprimer"><i class="ph ph-trash" aria-hidden="true"></i></button>
          <?php endif; ?>
        </div>

        <p style="margin:12px 0"><?= nl2br(e($publication['contenu'])) ?></p>

        <?php if (!empty($publication['image'])): ?>
          <img src="<?= e(chemin_upload('posts', $publication['image'])) ?>" class="pub-preview-image" style="width:100%;border-radius:var(--rayon);margin-bottom:12px" alt="">
        <?php endif; ?>

        <div style="display:flex;gap:10px;margin-bottom:8px">
          <form method="post">
            <input type="hidden" name="publication_id" value="<?= (int)$publication['id'] ?>">
            <input type="hidden" name="type" value="like">
            <button type="button" class="btn btn-secondaire btn-petit" data-ajax-reaction data-reaction-like="<?= (int)$publication['id'] ?>" style="<?= ($publication['ma_reaction'] ?? '') === 'like' ? 'color:var(--like);border-color:var(--like)' : '' ?>">
              <i class="ph ph-thumbs-up" aria-hidden="true"></i> <span data-like-count="<?= (int)$publication['id'] ?>"><?= (int)$publication['nb_likes'] ?></span>
            </button>
          </form>
          <form method="post">
            <input type="hidden" name="publication_id" value="<?= (int)$publication['id'] ?>">
            <input type="hidden" name="type" value="dislike">
            <button type="button" class="btn btn-secondaire btn-petit" data-ajax-reaction data-reaction-dislike="<?= (int)$publication['id'] ?>" style="<?= ($publication['ma_reaction'] ?? '') === 'dislike' ? 'color:var(--dislike);border-color:var(--dislike)' : '' ?>">
              <i class="ph ph-thumbs-down" aria-hidden="true"></i> <span data-dislike-count="<?= (int)$publication['id'] ?>"><?= (int)$publication['nb_dislikes'] ?></span>
            </button>
          </form>
          <span id="result-<?= (int)$publication['id'] ?>" class="comment-meta" style="margin-left:auto"></span>
        </div>

        <details>
          <summary style="cursor:pointer;color:var(--texte-2);font-size:13px" id="comment-count-<?= (int)$publication['id'] ?>">
            <i class="ph ph-chat-circle" aria-hidden="true"></i> <?= count($commentaires) ?> commentaire(s)
          </summary>
          <div style="margin-top:10px;display:flex;flex-direction:column;gap:8px">
            <div id="comments-<?= (int)$publication['id'] ?>" style="display:flex;flex-direction:column;gap:8px">
              <?php foreach ($commentaires as $c): ?>
                <div class="comment-bulle">
                  <strong><?= e($c['prenom'] . ' ' . $c['nom']) ?> :</strong> <?= e($c['contenu']) ?>
                  <div class="comment-meta"><?= e(temps_relatif($c['date_creation'])) ?></div>
                </div>
              <?php endforeach; ?>
            </div>

            <form method="post" data-comment-form style="display:flex;gap:8px;margin-top:6px">
              <input type="hidden" name="publication_id" value="<?= (int)$publication['id'] ?>">
              <input type="text" name="contenu" class="champ" placeholder="Écrire un commentaire..." required>
              <button type="submit" class="btn btn-primaire btn-petit">Envoyer</button>
            </form>
          </div>
        </details>
      </div>
    </div>
    <?php
    return ob_get_clean();
}

function charger_publications_flux($bdd, $uid, $utilisateur) {
    $req = $bdd->prepare("
        SELECT p.*, u.nom, u.prenom, u.photo_profil,
               (SELECT COUNT(*) FROM reactions r WHERE r.publication_id = p.id AND r.type = 'like')    AS nb_likes,
               (SELECT COUNT(*) FROM reactions r WHERE r.publication_id = p.id AND r.type = 'dislike') AS nb_dislikes,
               (SELECT r2.type FROM reactions r2 WHERE r2.publication_id = p.id AND r2.utilisateur_id = :uid) AS ma_reaction
        FROM publications p
        JOIN utilisateurs u ON u.id = p.auteur_id
        ORDER BY p.date_creation DESC
        LIMIT 30
    ");
    $req->execute(['uid' => $uid]);
    $publications = $req->fetchAll();

    $commentaires_par_pub = [];
    if ($publications) {
        $ids = array_column($publications, 'id');
        $marqueurs = implode(',', array_fill(0, count($ids), '?'));
        $req = $bdd->prepare("
            SELECT c.*, u.nom, u.prenom
            FROM commentaires c
            JOIN utilisateurs u ON u.id = c.auteur_id
            WHERE c.publication_id IN ($marqueurs)
            ORDER BY c.date_creation ASC
        ");
        $req->execute($ids);
        foreach ($req->fetchAll() as $c) {
            $commentaires_par_pub[$c['publication_id']][] = $c;
        }
    }

    return [$publications, $commentaires_par_pub];
}
