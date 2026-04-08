<?php

declare(strict_types=1);

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

final class ExportService
{
    public function generarXlsx(array $rows, string $destino): void
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $headers = [
            'Código', 'Nombre', 'Grado', 'Curso', 'Período',
            'Promedio Dimensión 1', 'Promedio Dimensión 2', 'Promedio Dimensión 3',
            'Promedio Dimensión 4', 'Promedio Dimensión 5', 'Nota Final', 'Fecha de envío',
        ];
        $sheet->fromArray($headers, null, 'A1');

        $line = 2;
        foreach ($rows as $row) {
            $sheet->fromArray([
                $row['codigo'], $row['nombre'], $row['grado'], $row['curso'], $row['periodo'],
                $row['promedio_dimension_1'], $row['promedio_dimension_2'], $row['promedio_dimension_3'],
                $row['promedio_dimension_4'], $row['promedio_dimension_5'], $row['nota_final'], $row['fecha_envio'],
            ], null, 'A' . $line++);
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save($destino);
    }
}
