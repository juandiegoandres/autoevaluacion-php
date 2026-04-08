<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;

final class Database
{
    private static ?PDO $connection = null;

    public static function connection(array $config): PDO
    {
        if (self::$connection instanceof PDO) {
            return self::$connection;
        }

        $driver = $config['driver'] ?? 'sqlite';

        try {
            if ($driver === 'mysql') {
                $dsn = sprintf(
                    'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                    $config['host'],
                    $config['port'],
                    $config['database']
                );
                self::$connection = new PDO($dsn, $config['username'], $config['password']);
            } else {
                $path = $config['sqlite_path'];
                $dir = dirname($path);
                if (!is_dir($dir)) {
                    mkdir($dir, 0775, true);
                }
                self::$connection = new PDO('sqlite:' . $path);
                self::$connection->exec('PRAGMA foreign_keys = ON;');
            }

            self::$connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            self::$connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            return self::$connection;
        } catch (PDOException $exception) {
            throw new PDOException('No se pudo establecer conexión DB: ' . $exception->getMessage());
        }
    }
}
