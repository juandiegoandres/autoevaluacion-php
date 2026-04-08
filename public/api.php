<?php

declare(strict_types=1);

if (is_file(dirname(__DIR__) . '/vendor/autoload.php')) {
    require_once dirname(__DIR__) . '/vendor/autoload.php';
}
require_once dirname(__DIR__) . '/app/autoload.php';

use App\Controllers\ApiController;
use App\Core\Database;
use App\Core\Env;
use App\Repositories\AutoevaluacionRepository;
use App\Repositories\DocenteRepository;
use App\Repositories\EstudianteRepository;
use App\Repositories\PeriodoRepository;
use App\Services\AuthService;
use App\Services\AutoevaluacionService;
use App\Services\ExportService;
use App\Services\ImportService;
use App\Services\PdfService;

Env::load(dirname(__DIR__) . '/.env');
$config = require dirname(__DIR__) . '/app/Config/config.php';
$estructura = require dirname(__DIR__) . '/app/Config/autoevaluacion.php';

$db = Database::connection($config['db']);

$periodoRepo = new PeriodoRepository($db);
$estudianteRepo = new EstudianteRepository($db);
$autoevaluacionRepo = new AutoevaluacionRepository($db);
$authService = new AuthService(new DocenteRepository($db), $estudianteRepo, $periodoRepo);

$controller = new ApiController(
    $authService,
    new AutoevaluacionService($autoevaluacionRepo, $estructura),
    $periodoRepo,
    $estudianteRepo,
    $autoevaluacionRepo,
    new ImportService($estudianteRepo),
    new ExportService(),
    new PdfService(),
    $config
);

$controller->run($_GET['action'] ?? '');
