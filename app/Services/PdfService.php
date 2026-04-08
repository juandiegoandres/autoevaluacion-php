<?php

declare(strict_types=1);

namespace App\Services;

use Dompdf\Dompdf;

final class PdfService
{
    public function generar(array $registro, array $estructura, string $destino, ?string $logoPath = null): void
    {
        $respuestas = json_decode($registro['respuestas_json'], true) ?: [];

        $logoHtml = '';
        if ($logoPath && is_file($logoPath)) {
            $data = base64_encode(file_get_contents($logoPath));
            $logoHtml = '<img src="data:image/png;base64,' . $data . '" style="height:70px" alt="Logo">';
        }

        $body = '<h2 style="margin:0">Instituto Técnico Santo Tomás</h2>';
        $body .= '<p style="margin:4px 0 14px">Reporte de Autoevaluación Estudiantil</p>';
        $body .= '<table width="100%" cellpadding="6" style="border-collapse: collapse; margin-bottom: 12px;">';
        $body .= '<tr><td><strong>Código:</strong> ' . htmlspecialchars($registro['codigo']) . '</td><td><strong>Nombre:</strong> ' . htmlspecialchars($registro['nombre']) . '</td></tr>';
        $body .= '<tr><td><strong>Curso:</strong> ' . htmlspecialchars($registro['grado'] . '-' . $registro['curso']) . '</td><td><strong>Período:</strong> ' . htmlspecialchars($registro['periodo_nombre']) . '</td></tr>';
        $body .= '<tr><td colspan="2"><strong>Fecha:</strong> ' . htmlspecialchars($registro['created_at']) . '</td></tr>';
        $body .= '</table>';

        $body .= '<table width="100%" cellpadding="6" style="border-collapse: collapse; border:1px solid #ccc;">';
        foreach ($estructura as $idx => $dim) {
            $dimNumber = $idx + 1;
            $body .= '<tr style="background:#f2f5fa"><td colspan="2"><strong>' . $dimNumber . '. ' . htmlspecialchars($dim['nombre']) . '</strong></td></tr>';
            foreach ($dim['items'] as $i => $item) {
                $score = $respuestas[$dim['id']]['item_' . ($i + 1)] ?? '-';
                $body .= '<tr><td>' . htmlspecialchars($item) . '</td><td style="width:70px;text-align:center">' . htmlspecialchars((string) $score) . '</td></tr>';
            }
            $body .= '<tr><td><strong>Promedio dimensión ' . $dimNumber . '</strong></td><td style="text-align:center"><strong>' . $registro['promedio_dimension_' . $dimNumber] . '</strong></td></tr>';
        }
        $body .= '</table>';
        $body .= '<h3 style="text-align:right">Nota final: ' . $registro['nota_final'] . '</h3>';

        $html = '<html><body style="font-family: DejaVu Sans, sans-serif; color:#222">'
            . '<table width="100%"><tr><td>' . $logoHtml . '</td><td style="text-align:right">'
            . $body . '</td></tr></table></body></html>';

        $dompdf = new Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        file_put_contents($destino, $dompdf->output());
    }
}
