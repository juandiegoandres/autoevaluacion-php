<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class DocenteRepository
{
    public function __construct(private readonly PDO $db)
    {
    }

    public function findByUsuario(string $usuario): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM docentes WHERE usuario = :usuario LIMIT 1');
        $stmt->execute(['usuario' => $usuario]);
        return $stmt->fetch() ?: null;
    }
}
