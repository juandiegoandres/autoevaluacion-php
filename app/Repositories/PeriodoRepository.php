<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class PeriodoRepository
{
    public function __construct(private readonly PDO $db)
    {
    }

    public function activo(): ?array
    {
        $stmt = $this->db->query('SELECT * FROM periodos WHERE activo = 1 LIMIT 1');
        return $stmt->fetch() ?: null;
    }

    public function all(): array
    {
        return $this->db->query('SELECT * FROM periodos ORDER BY id DESC')->fetchAll();
    }

    public function create(string $nombre, string $password, bool $activo, bool $formularioAbierto): int
    {
        if ($activo) {
            $this->db->exec('UPDATE periodos SET activo = 0');
        }

        $stmt = $this->db->prepare('INSERT INTO periodos (nombre, password_hash, activo, formulario_abierto) VALUES (:nombre, :password_hash, :activo, :formulario_abierto)');
        $stmt->execute([
            'nombre' => $nombre,
            'password_hash' => password_hash($password, PASSWORD_BCRYPT),
            'activo' => $activo ? 1 : 0,
            'formulario_abierto' => $formularioAbierto ? 1 : 0,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function updateEstado(int $periodoId, bool $activo, bool $formularioAbierto): void
    {
        if ($activo) {
            $this->db->exec('UPDATE periodos SET activo = 0');
        }

        $stmt = $this->db->prepare('UPDATE periodos SET activo = :activo, formulario_abierto = :formulario_abierto WHERE id = :id');
        $stmt->execute([
            'id' => $periodoId,
            'activo' => $activo ? 1 : 0,
            'formulario_abierto' => $formularioAbierto ? 1 : 0,
        ]);
    }

    public function updatePassword(int $periodoId, string $password): void
    {
        $stmt = $this->db->prepare('UPDATE periodos SET password_hash = :password_hash WHERE id = :id');
        $stmt->execute([
            'id' => $periodoId,
            'password_hash' => password_hash($password, PASSWORD_BCRYPT),
        ]);
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM periodos WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }
}
