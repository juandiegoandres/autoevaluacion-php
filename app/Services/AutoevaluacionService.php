<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\AutoevaluacionRepository;

final class AutoevaluacionService
{
    public function __construct(private readonly AutoevaluacionRepository $repo, private readonly array $estructura)
    {
    }

    public function estructura(): array
    {
        return $this->estructura;
    }

    public function calcular(array $respuestas): array
    {
        $promedios = [];
        $sumatoria = 0.0;

        foreach ($this->estructura as $idx => $dimension) {
            $dimensionId = $dimension['id'];
            $items = $dimension['items'];
            $total = 0;

            foreach ($items as $i => $_item) {
                $itemKey = 'item_' . ($i + 1);
                $valor = (int) ($respuestas[$dimensionId][$itemKey] ?? 0);
                if ($valor < 1 || $valor > 5) {
                    throw new \InvalidArgumentException('Todas las respuestas deben estar entre 1 y 5.');
                }
                $total += $valor;
            }

            $promedio = round($total / count($items), 2);
            $promedios[$idx + 1] = $promedio;
            $sumatoria += $promedio;
        }

        return [
            'promedios' => $promedios,
            'nota_final' => round($sumatoria / count($this->estructura), 2),
        ];
    }

    public function guardar(int $estudianteId, int $periodoId, array $respuestas): int
    {
        if ($this->repo->existsByEstudiantePeriodo($estudianteId, $periodoId)) {
            throw new \RuntimeException('El estudiante ya diligenció la autoevaluación en este período.');
        }

        $calc = $this->calcular($respuestas);

        return $this->repo->create([
            'estudiante_id' => $estudianteId,
            'periodo_id' => $periodoId,
            'respuestas_json' => json_encode($respuestas, JSON_UNESCAPED_UNICODE),
            'promedio_dimension_1' => $calc['promedios'][1],
            'promedio_dimension_2' => $calc['promedios'][2],
            'promedio_dimension_3' => $calc['promedios'][3],
            'promedio_dimension_4' => $calc['promedios'][4],
            'promedio_dimension_5' => $calc['promedios'][5],
            'nota_final' => $calc['nota_final'],
        ]);
    }
}
