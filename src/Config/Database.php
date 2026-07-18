<?php

namespace Canary\Config;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $pdo = null;

    public static function connect(): PDO
    {
        if (self::$pdo !== null) {
            return self::$pdo;
        }
        $driver = $_ENV["DB_DRIVER"] ?? "sqlite";
        $database =
            $_ENV["DB_DATABASE"] ?? __DIR__ . "/../../database/database.sqlite";

        try {
            if ($driver === "sqlite") {
                $dbDir = dirname($database);

                if (!is_dir($dbDir)) {
                    mkdir($dbDir, 0755, true);
                }

                $dsn = "sqlite:{$database}";
                $pdo = new PDO($dsn);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $pdo->setAttribute(
                    PDO::ATTR_DEFAULT_FETCH_MODE,
                    PDO::FETCH_ASSOC,
                );

                $pdo->exec("PRAGMA foreign_keys = ON");

                self::$pdo = $pdo;
                return self::$pdo;
            }

            throw new PDOException("Unsupported driver: {$driver}");
        } catch (PDOException $e) {
            error_log("[Database] Connection failed: " . $e->getMessage());

            die(
                json_encode([
                    "status" => "error",
                    "message" => $e->getMessage(),
                ])
            );
        }
    }

    public static function getPdo(): ?PDO
    {
        return self::connect();
    }

    # Deny instantiation and cloning
    private function __construct() {}
    private function __clone() {}
}
