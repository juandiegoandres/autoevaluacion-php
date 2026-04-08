<?php

declare(strict_types=1);

if (is_file(dirname(__DIR__) . '/vendor/autoload.php')) {
    require_once dirname(__DIR__) . '/vendor/autoload.php';
}
require_once dirname(__DIR__) . '/app/autoload.php';

use App\Core\Env;

Env::load(dirname(__DIR__) . '/.env');
$config = require dirname(__DIR__) . '/app/Config/config.php';

?><!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?= htmlspecialchars($config['app']['name']) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/app.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
<div id="app"></div>
<script>
window.APP_CONFIG = { appName: <?= json_encode($config['app']['name']) ?> };
</script>
<script src="assets/js/app.js" defer></script>
</body>
</html>
