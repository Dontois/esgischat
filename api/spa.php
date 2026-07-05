<?php
require_once __DIR__ . '/../inclure/config.php';
require_once __DIR__ . '/../inclure/fonctions.php';
require_once __DIR__ . '/../inclure/publications.php';

define('SPA_MODE', true);

$utilisateur = utilisateur_api_requis();

$view = $_GET['view'] ?? 'accueil';
$params = [];

if (preg_match('/^membre$/', $view)) {
    $membre_id = (int)($_GET['id'] ?? 0);
}

$routes = [
    'accueil' => __DIR__ . '/../vues/clients/accueil.php',
    'amis'    => __DIR__ . '/../vues/clients/amis.php',
    'chat'    => __DIR__ . '/../vues/clients/chat.php',
    'profil'  => __DIR__ . '/../vues/clients/profil.php',
    'membre'  => __DIR__ . '/../vues/clients/membre.php',
];

if (!isset($routes[$view])) {
    reponse_json_api(false, ['message' => 'Vue introuvable']);
}

ob_start();
require $routes[$view];
$html = ob_get_clean();

$titles = [
    'accueil' => 'Accueil',
    'amis'    => 'Amis',
    'chat'    => 'Messages',
    'profil'  => 'Profil',
    'membre'  => 'Profil membre',
];

reponse_json_api(true, [
    'view'  => $view,
    'title' => $titles[$view] ?? 'ESGISchat',
    'html'  => $html,
]);
