<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\DocenteRepository;
use App\Repositories\EstudianteRepository;
use App\Repositories\PeriodoRepository;

final class AuthService
{
    public function __construct(
        private readonly DocenteRepository $docenteRepo,
        private readonly EstudianteRepository $estudianteRepo,
        private readonly PeriodoRepository $periodoRepo
    ) {
    }

    public function loginDocente(string $usuario, string $password): ?array
    {
        $docente = $this->docenteRepo->findByUsuario($usuario);
        if (!$docente || !password_verify($password, $docente['password_hash'])) {
            return null;
        }
        unset($docente['password_hash']);
        return $docente;
    }

    public function loginEstudiante(string $codigo, string $grado, string $curso, string $passwordPeriodo): ?array
    {
        $periodo = $this->periodoRepo->activo();
        if (!$periodo || !(int) $periodo['formulario_abierto']) {
            return null;
        }

        if (!password_verify($passwordPeriodo, $periodo['password_hash'])) {
            return null;
        }

        $estudiante = $this->estudianteRepo->findForLogin($codigo, $grado, $curso);
        if (!$estudiante) {
            return null;
        }

        return [
            'estudiante' => $estudiante,
            'periodo' => $periodo,
        ];
    }
}
