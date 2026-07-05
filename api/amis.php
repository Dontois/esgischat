<?php
require_once __DIR__ . '/../inclure/config.php';
require_once __DIR__ . '/../inclure/fonctions.php';

$utilisateur = utilisateur_api_requis();
$uid = (int)$utilisateur['id'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'envoyer' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $cible = intval($_POST['cible_id'] ?? 0);
    if ($cible && $cible !== $uid) {
        $req = $bdd->prepare("
            SELECT id FROM amis
            WHERE (demandeur_id = :uid AND receveur_id = :cible)
               OR (demandeur_id = :cible AND receveur_id = :uid)
        ");
        $req->execute(['uid' => $uid, 'cible' => $cible]);
        if (!$req->fetch()) {
            $bdd->prepare("INSERT INTO amis (demandeur_id, receveur_id) VALUES (:uid, :cible)")
                ->execute(['uid' => $uid, 'cible' => $cible]);
        }
    }
    reponse_json_api(true, ['message' => "Demande d'ami envoyée !"]);
}

if ($action === 'repondre' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $ami_id = intval($_POST['ami_id'] ?? 0);
    $statut = $_POST['statut'] ?? '';
    if ($ami_id && in_array($statut, ['accepte', 'refuse'], true)) {
        $req = $bdd->prepare("SELECT id FROM amis WHERE id = :id AND receveur_id = :uid AND statut = 'en_attente'");
        $req->execute(['id' => $ami_id, 'uid' => $uid]);
        if ($req->fetch()) {
            $bdd->prepare("UPDATE amis SET statut = :statut WHERE id = :id")
                ->execute(['statut' => $statut, 'id' => $ami_id]);
            reponse_json_api(true, ['message' => $statut === 'accepte' ? 'Demande acceptée !' : 'Demande refusée.']);
        }
    }
    reponse_json_api(false, ['message' => 'Demande introuvable.']);
}

if ($action === 'retirer' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $cible = intval($_POST['cible_id'] ?? 0);
    if ($cible) {
        $bdd->prepare("
            DELETE FROM amis
            WHERE (demandeur_id = :uid AND receveur_id = :cible)
               OR (demandeur_id = :cible AND receveur_id = :uid)
        ")->execute(['uid' => $uid, 'cible' => $cible]);
    }
    reponse_json_api(true, ['message' => 'Ami retiré.']);
}

if ($action === 'profil' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $id = intval($_GET['id'] ?? 0);
    if (!$id) {
        reponse_json_api(false, ['message' => 'Utilisateur introuvable.']);
    }

    $req = $bdd->prepare("SELECT id, nom, prenom, email, photo_profil, bio, date_creation FROM utilisateurs WHERE id = :id AND role = 'user'");
    $req->execute(['id' => $id]);
    $membre = $req->fetch();
    if (!$membre) {
        reponse_json_api(false, ['message' => 'Utilisateur introuvable.']);
    }

    $req = $bdd->prepare("
        SELECT a.statut, a.demandeur_id
        FROM amis a
        WHERE (a.demandeur_id = :uid AND a.receveur_id = :id)
           OR (a.receveur_id = :uid AND a.demandeur_id = :id)
        LIMIT 1
    ");
    $req->execute(['uid' => $uid, 'id' => $id]);
    $amitie = $req->fetch() ?: null;

    reponse_json_api(true, ['membre' => $membre, 'amitie' => $amitie]);
}

if ($action === 'recherche' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $q = trim($_GET['q'] ?? '');
    if ($q === '') {
        reponse_json_api(true, ['users' => []]);
    }

    $req = $bdd->prepare("SELECT id, nom, prenom, photo_profil
        FROM utilisateurs
        WHERE id != :uid
          AND role = 'user'
          AND (
              nom LIKE :term OR prenom LIKE :term OR CONCAT(prenom, ' ', nom) LIKE :term
          )
        ORDER BY prenom ASC, nom ASC
        LIMIT 10");
    $req->execute(['uid' => $uid, 'term' => '%' . $q . '%']);
    $users = $req->fetchAll();

    reponse_json_api(true, ['users' => $users]);
}

reponse_json_api(false, ['message' => 'Action inconnue.']);
