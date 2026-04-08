<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class EstudianteRepository
{
    public function __construct(private readonly PDO $db)
    {
    }

    public function findForLogin(string $codigo, string $grado, string $curso): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM estudiantes WHERE codigo = :codigo AND grado = :grado AND curso = :curso LIMIT 1');
        $stmt->execute([
            'codigo' => $codigo,
            'grado' => $grado,
            'curso' => $curso,
        ]);
        return $stmt->fetch() ?: null;
    }

    public function upsert(array $student): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO estudiantes (codigo, nombre, grado, curso) VALUES (:codigo, :nombre, :grado, :curso)
             ON CONFLICT(codigo) DO UPDATE SET
                nombre=excluded.nombre,
                grado=excluded.grado,
                curso=excluded.curso,
                updated_at=CURRENT_TIMESTAMP'
        );
        $stmt->execute($student);
    }

    public function all(array $filters = []): array
    {
        $sql = 'SELECT * FROM estudiantes WHERE 1=1';
        $params = [];

        if (!empty($filters['curso'])) {
            $sql .= ' AND curso = :curso';
            $params['curso'] = $filters['curso'];
        }
        if (!empty($filters['codigo'])) {
            $sql .= ' AND codigo LIKE :codigo';
            $params['codigo'] = '%' . $filters['codigo'] . '%';
        }
        if (!empty($filters['nombre'])) {
            $sql .= ' AND nombre LIKE :nombre';
            $params['nombre'] = '%' . $filters['nombre'] . '%';
        }

        $sql .= ' ORDER BY grado, curso, nombre';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
