<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\JsonResponse;
use App\Core\Session;
use App\Repositories\AutoevaluacionRepository;
use App\Repositories\EstudianteRepository;
use App\Repositories\PeriodoRepository;
use App\Services\AuthService;
use App\Services\AutoevaluacionService;
use App\Services\ExportService;
use App\Services\ImportService;
use App\Services\PdfService;

final class ApiController
{
    public function __construct(
        private readonly AuthService $authService,
        private readonly AutoevaluacionService $autoevaluacionService,
        private readonly PeriodoRepository $periodoRepo,
        private readonly EstudianteRepository $estudianteRepo,
        private readonly AutoevaluacionRepository $autoevaluacionRepo,
        private readonly ImportService $importService,
        private readonly ExportService $exportService,
        private readonly PdfService $pdfService,
        private readonly array $appConfig
    ) {
    }

    public function run(string $action): never
    {
        switch ($action) {
            case 'docente.login':
                $this->docenteLogin();
            case 'docente.logout':
                $this->docenteLogout();
            case 'docente.periodos.list':
                $this->requireDocente();
                JsonResponse::send(['ok' => true, 'data' => $this->periodoRepo->all()]);
            case 'docente.periodos.create':
                $this->requireDocente();
                $this->createPeriodo();
            case 'docente.periodos.update':
                $this->requireDocente();
                $this->updatePeriodo();
            case 'docente.periodos.password':
                $this->requireDocente();
                $this->updatePeriodoPassword();
            case 'docente.estudiantes.import':
                $this->requireDocente();
                $this->importarEstudiantes();
            case 'docente.estudiantes.list':
                $this->requireDocente();
                $this->listarEstudiantes();
            case 'docente.reportes.list':
                $this->requireDocente();
                $this->listarReportes();
            case 'docente.reportes.export':
                $this->requireDocente();
                $this->exportarReportes();
            case 'estudiante.login':
                $this->estudianteLogin();
            case 'estudiante.logout':
                $this->estudianteLogout();
            case 'estudiante.estructura':
                $this->requireEstudiante();
                JsonResponse::send(['ok' => true, 'data' => $this->autoevaluacionService->estructura()]);
            case 'estudiante.guardar':
                $this->requireEstudiante();
                $this->guardarAutoevaluacion();
            case 'estudiante.resumen':
                $this->requireEstudiante();
                $this->resumen();
            case 'estudiante.pdf':
                $this->requireEstudiante();
                $this->descargarPdfEstudiante();
            default:
                JsonResponse::send(['ok' => false, 'message' => 'Acción no válida.'], 404);
        }
    }

    private function docenteLogin(): never
    {
        $body = $this->jsonBody();
        $docente = $this->authService->loginDocente(trim((string) ($body['usuario'] ?? '')), (string) ($body['password'] ?? ''));
        if (!$docente) {
            JsonResponse::send(['ok' => false, 'message' => 'Credenciales inválidas.'], 401);
        }

        Session::set('docente', $docente);
        JsonResponse::send(['ok' => true, 'data' => $docente]);
    }

    private function docenteLogout(): never
    {
        Session::destroy();
        JsonResponse::send(['ok' => true]);
    }

    private function estudianteLogin(): never
    {
        $body = $this->jsonBody();
        $result = $this->authService->loginEstudiante(
            trim((string) ($body['codigo'] ?? '')),
            trim((string) ($body['grado'] ?? '')),
            trim((string) ($body['curso'] ?? '')),
            (string) ($body['password_periodo'] ?? '')
        );
        if (!$result) {
            JsonResponse::send(['ok' => false, 'message' => 'No se pudo autenticar estudiante o período cerrado.'], 401);
        }

        $registro = $this->autoevaluacionRepo->findByEstudiantePeriodo($result['estudiante']['id'], $result['periodo']['id']);
        Session::set('estudiante', [
            'id' => $result['estudiante']['id'],
            'codigo' => $result['estudiante']['codigo'],
            'nombre' => $result['estudiante']['nombre'],
            'grado' => $result['estudiante']['grado'],
            'curso' => $result['estudiante']['curso'],
            'periodo_id' => $result['periodo']['id'],
            'periodo_nombre' => $result['periodo']['nombre'],
        ]);

        JsonResponse::send(['ok' => true, 'data' => ['ya_enviado' => (bool) $registro]]);
    }

    private function estudianteLogout(): never
    {
        Session::destroy();
        JsonResponse::send(['ok' => true]);
    }

    private function guardarAutoevaluacion(): never
    {
        $body = $this->jsonBody();
        $estudiante = Session::get('estudiante');

        try {
            $id = $this->autoevaluacionService->guardar(
                (int) $estudiante['id'],
                (int) $estudiante['periodo_id'],
                $body['respuestas'] ?? []
            );
        } catch (\Throwable $e) {
            JsonResponse::send(['ok' => false, 'message' => $e->getMessage()], 422);
        }

        JsonResponse::send(['ok' => true, 'data' => ['autoevaluacion_id' => $id]]);
    }

    private function resumen(): never
    {
        $estudiante = Session::get('estudiante');
        $row = $this->autoevaluacionRepo->findByEstudiantePeriodo((int) $estudiante['id'], (int) $estudiante['periodo_id']);

        if (!$row) {
            JsonResponse::send(['ok' => false, 'message' => 'No hay autoevaluación registrada.'], 404);
        }

        JsonResponse::send(['ok' => true, 'data' => $row]);
    }

    private function listarEstudiantes(): never
    {
        $filters = [
            'curso' => $_GET['curso'] ?? '',
            'codigo' => $_GET['codigo'] ?? '',
            'nombre' => $_GET['nombre'] ?? '',
        ];
        JsonResponse::send(['ok' => true, 'data' => $this->estudianteRepo->all($filters)]);
    }

    private function listarReportes(): never
    {
        $periodoId = (int) ($_GET['periodo_id'] ?? 0);
        $curso = $_GET['curso'] ?? null;
        JsonResponse::send(['ok' => true, 'data' => $this->autoevaluacionRepo->reportes($periodoId, $curso)]);
    }

    private function exportarReportes(): never
    {
        $periodoId = (int) ($_GET['periodo_id'] ?? 0);
        $curso = $_GET['curso'] ?? null;
        $rows = $this->autoevaluacionRepo->reportes($periodoId, $curso);

        $nombre = 'reporte_periodo_' . $periodoId . ($curso ? '_curso_' . $curso : '_general') . '.xlsx';
        $destino = dirname(__DIR__, 2) . '/storage/exports/' . $nombre;
        $this->exportService->generarXlsx($rows, $destino);

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $nombre . '"');
        readfile($destino);
        exit;
    }

    private function descargarPdfEstudiante(): never
    {
        $id = (int) ($_GET['id'] ?? 0);
        $registro = $this->autoevaluacionRepo->findById($id);
        if (!$registro) {
            JsonResponse::send(['ok' => false, 'message' => 'No existe el registro.'], 404);
        }

        $nombre = 'autoevaluacion_' . $registro['codigo'] . '_p' . $registro['periodo_id'] . '.pdf';
        $destino = dirname(__DIR__, 2) . '/storage/pdf/' . $nombre;

        $logo = dirname(__DIR__, 2) . '/public/assets/img/logo.png';
        $this->pdfService->generar($registro, $this->autoevaluacionService->estructura(), $destino, $logo);

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $nombre . '"');
        readfile($destino);
        exit;
    }

    private function createPeriodo(): never
    {
        $body = $this->jsonBody();
        $id = $this->periodoRepo->create(
            trim((string) ($body['nombre'] ?? '')),
            (string) ($body['password'] ?? ''),
            (bool) ($body['activo'] ?? true),
            (bool) ($body['formulario_abierto'] ?? true)
        );

        JsonResponse::send(['ok' => true, 'data' => ['id' => $id]]);
    }

    private function updatePeriodo(): never
    {
        $body = $this->jsonBody();
        $this->periodoRepo->updateEstado((int) $body['periodo_id'], (bool) $body['activo'], (bool) $body['formulario_abierto']);
        JsonResponse::send(['ok' => true]);
    }

    private function updatePeriodoPassword(): never
    {
        $body = $this->jsonBody();
        $this->periodoRepo->updatePassword((int) $body['periodo_id'], (string) $body['password']);
        JsonResponse::send(['ok' => true]);
    }

    private function importarEstudiantes(): never
    {
        if (!isset($_FILES['archivo']) || !is_uploaded_file($_FILES['archivo']['tmp_name'])) {
            JsonResponse::send(['ok' => false, 'message' => 'Archivo inválido.'], 422);
        }

        $count = $this->importService->importarCsv($_FILES['archivo']['tmp_name']);
        JsonResponse::send(['ok' => true, 'data' => ['importados' => $count]]);
    }

    private function jsonBody(): array
    {
        $raw = file_get_contents('php://input') ?: '{}';
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    private function requireDocente(): void
    {
        if (!Session::get('docente')) {
            JsonResponse::send(['ok' => false, 'message' => 'No autorizado.'], 401);
        }
    }

    private function requireEstudiante(): void
    {
        if (!Session::get('estudiante')) {
            JsonResponse::send(['ok' => false, 'message' => 'No autorizado.'], 401);
        }
    }
}
