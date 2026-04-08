<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\EstudianteRepository;

final class ImportService
{
    public function __construct(private readonly EstudianteRepository $repo)
    {
    }

    public function importarCsv(string $tmpPath): int
    {
        $handle = fopen($tmpPath, 'r');
        if (!$handle) {
            throw new \RuntimeException('No se pudo abrir el archivo CSV.');
        }

        $count = 0;
        $header = fgetcsv($handle);
        if (!$header) {
            fclose($handle);
            return 0;
        }

        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) < 4) {
                continue;
            }
            [$codigo, $nombre, $grado, $curso] = array_map('trim', $row);
            if ($codigo === '' || $nombre === '' || $grado === '' || $curso === '') {
                continue;
            }

            $this->repo->upsert([
                'codigo' => $codigo,
                'nombre' => $nombre,
                'grado' => $grado,
                'curso' => $curso,
            ]);
            $count++;
        }
        fclose($handle);

        return $count;
    }
}
