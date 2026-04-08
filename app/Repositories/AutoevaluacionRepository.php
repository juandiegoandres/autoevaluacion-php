<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class AutoevaluacionRepository
{
    public function __construct(private readonly PDO $db)
    {
    }

    public function existsByEstudiantePeriodo(int $estudianteId, int $periodoId): bool
    {
        $stmt = $this->db->prepare('SELECT id FROM autoevaluaciones WHERE estudiante_id = :estudiante_id AND periodo_id = :periodo_id LIMIT 1');
        $stmt->execute(['estudiante_id' => $estudianteId, 'periodo_id' => $periodoId]);
        return (bool) $stmt->fetch();
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO autoevaluaciones (
                estudiante_id, periodo_id, respuestas_json,
                promedio_dimension_1, promedio_dimension_2, promedio_dimension_3,
                promedio_dimension_4, promedio_dimension_5, nota_final
            ) VALUES (
                :estudiante_id, :periodo_id, :respuestas_json,
                :promedio_dimension_1, :promedio_dimension_2, :promedio_dimension_3,
                :promedio_dimension_4, :promedio_dimension_5, :nota_final
            )'
        );
        $stmt->execute($data);
        return (int) $this->db->lastInsertId();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT a.*, e.codigo, e.nombre, e.grado, e.curso, p.nombre AS periodo_nombre
             FROM autoevaluaciones a
             INNER JOIN estudiantes e ON e.id = a.estudiante_id
             INNER JOIN periodos p ON p.id = a.periodo_id
             WHERE a.id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function findByEstudiantePeriodo(int $estudianteId, int $periodoId): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM autoevaluaciones WHERE estudiante_id = :estudiante_id AND periodo_id = :periodo_id LIMIT 1');
        $stmt->execute(['estudiante_id' => $estudianteId, 'periodo_id' => $periodoId]);
        return $stmt->fetch() ?: null;
    }

    public function reportes(int $periodoId, ?string $curso = null): array
    {
        $sql = 'SELECT e.codigo, e.nombre, e.grado, e.curso, p.nombre AS periodo,
                    a.promedio_dimension_1, a.promedio_dimension_2, a.promedio_dimension_3,
                    a.promedio_dimension_4, a.promedio_dimension_5, a.nota_final, a.created_at AS fecha_envio
                FROM autoevaluaciones a
                INNER JOIN estudiantes e ON e.id = a.estudiante_id
                INNER JOIN periodos p ON p.id = a.periodo_id
                WHERE a.periodo_id = :periodo_id';

        $params = ['periodo_id' => $periodoId];
        if ($curso !== null && $curso !== '') {
            $sql .= ' AND e.curso = :curso';
            $params['curso'] = $curso;
        }

        $sql .= ' ORDER BY e.grado, e.curso, e.nombre';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
