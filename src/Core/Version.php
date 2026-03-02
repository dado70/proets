<?php
declare(strict_types=1);

namespace ProETS\Core;

class Version
{
    private static ?string $cached = null;

    /**
     * Legge la versione dal file VERSION in root (singola fonte di verità).
     */
    public static function current(): string
    {
        if (self::$cached === null) {
            $file = dirname(__DIR__, 2) . '/VERSION';
            self::$cached = file_exists($file)
                ? trim((string)file_get_contents($file))
                : '0.0.0';
        }
        return self::$cached;
    }

    /**
     * Versione DB = migration più recente applicata (campo version).
     * Restituisce null se la tabella db_migrations non esiste ancora.
     */
    public static function dbVersion(): ?string
    {
        try {
            $row = Database::fetchOne(
                "SELECT version FROM db_migrations ORDER BY id DESC LIMIT 1"
            );
            return $row ? $row['version'] : null;
        } catch (\Throwable) {
            return null;
        }
    }
}
