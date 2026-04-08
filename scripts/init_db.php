<?php

declare(strict_types=1);

if (is_file(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}
require_once __DIR__ . '/../app/autoload.php';

use App\Core\Database;
use App\Core\Env;

Env::load(__DIR__ . '/../.env');
$config = require __DIR__ . '/../app/Config/config.php';
$db = Database::connection($config['db']);

$sql = file_get_contents(__DIR__ . '/../database/migrations/001_init.sql');
$db->exec($sql);

$usuario = getenv('DOCENTE_DEFAULT_USER') ?: 'admin';
$password = getenv('DOCENTE_DEFAULT_PASSWORD') ?: 'admin123';
$hash = password_hash($password, PASSWORD_BCRYPT);

$stmt = $db->prepare('INSERT OR IGNORE INTO docentes (nombre, usuario, password_hash) VALUES (:nombre, :usuario, :password_hash)');
$stmt->execute([
    'nombre' => 'Docente Administrador',
    'usuario' => $usuario,
    'password_hash' => $hash,
]);

echo "Base inicializada correctamente. Usuario docente: {$usuario}\n";
