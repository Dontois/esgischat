<?php
require_once __DIR__ . '/inclure/fonctions.php';
demarrer_session();
session_destroy();
header('Location: index.php');
exit;
