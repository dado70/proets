<?php
declare(strict_types=1);

namespace ProETS\Core;

use PDO;

class Migrator
{
    private static string $migrationsDir;

    private static function dir(): string
    {
        if (!isset(self::$migrationsDir)) {
            self::$migrationsDir = dirname(__DIR__, 2) . '/migrations';
        }
        return self::$migrationsDir;
    }

    /**
     * Tutti i file migrazione ordinati per nome.
     * @return array<string, array{version:string, description:string, up:callable}>
     */
    private static function loadAll(): array
    {
        $dir = self::dir();
        if (!is_dir($dir)) return [];

        $files = glob($dir . '/*.php') ?: [];
        sort($files);

        $migrations = [];
        foreach ($files as $file) {
            $name = basename($file, '.php');
            $def  = require $file;
            if (is_array($def) && isset($def['up'])) {
                $migrations[$name] = $def;
            }
        }
        return $migrations;
    }

    /**
     * Nomi delle migrazioni già applicate nel DB.
     * @return string[]
     */
    private static function appliedNames(): array
    {
        try {
            $rows = Database::fetchAll("SELECT migration FROM db_migrations ORDER BY id");
            return array_column($rows, 'migration');
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * Migrazioni non ancora applicate.
     * @return array<string, array{version:string, description:string, up:callable}>
     */
    public static function getPending(): array
    {
        $all     = self::loadAll();
        $applied = self::appliedNames();
        return array_filter($all, fn($name) => !in_array($name, $applied, true), ARRAY_FILTER_USE_KEY);
    }

    /**
     * Esegue tutte le migrazioni pendenti in ordine.
     * Ogni migrazione è wrappata in una transaction.
     * @return array{name:string, ok:bool, message:string}[]
     */
    public static function runAll(): array
    {
        $pending = self::getPending();
        $results = [];
        $pdo     = Database::pdo();

        foreach ($pending as $name => $def) {
            try {
                $pdo->beginTransaction();
                ($def['up'])($pdo);
                Database::query(
                    "INSERT INTO db_migrations (migration, version, description) VALUES (?, ?, ?)",
                    [$name, $def['version'] ?? '', $def['description'] ?? '']
                );
                $pdo->commit();
                $results[] = ['name' => $name, 'ok' => true, 'message' => $def['description'] ?? $name];
            } catch (\Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $results[] = ['name' => $name, 'ok' => false, 'message' => $e->getMessage()];
                // Interrompi al primo errore — non eseguire le successive
                break;
            }
        }

        return $results;
    }

    /**
     * Lista delle migrazioni già applicate con timestamp.
     * @return array<int, array{migration:string, version:string, description:string, applied_at:string}>
     */
    public static function getApplied(): array
    {
        try {
            return Database::fetchAll(
                "SELECT migration, version, description, applied_at FROM db_migrations ORDER BY id"
            );
        } catch (\Throwable) {
            return [];
        }
    }
}
