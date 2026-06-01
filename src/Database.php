<?php

declare(strict_types=1);

namespace App;

use PDO;
use PDOException;

final class Database
{
    public static function createConnection(Config $config): PDO
    {
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
            $config->getDbHost(),
            $config->getDbPort(),
            $config->getDbName()
        );

        try {
            return new PDO(
                $dsn,
                $config->getDbUser(),
                $config->getDbPassword(),
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
        } catch (PDOException $e) {
            throw new \RuntimeException(
                'Database connection failed: ' . $e->getMessage(),
                (int) $e->getCode(),
                $e
            );
        }
    }
}
