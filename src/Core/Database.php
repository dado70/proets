<?php
declare(strict_types=1);

namespace ProETS\Core;

use PDO;
use PDOException;
use PDOStatement;

class Database
{
    private static ?PDO $pdo = null;

    public static function connect(array $cfg): void
    {
        if (self::$pdo !== null) return;

        $dsn = "mysql:host={$cfg['host']};port={$cfg['port']};dbname={$cfg['name']};charset={$cfg['charset']}";
        try {
            self::$pdo = new PDO($dsn, $cfg['user'], $cfg['pass'], [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
            ]);
        } catch (PDOException $e) {
            error_log('[ProETS] DB connect error: ' . $e->getMessage());
            throw new \RuntimeException('Errore di connessione al database.');
        }
    }

    public static function pdo(): PDO
    {
        if (self::$pdo === null) {
            throw new \RuntimeException('Database non connesso.');
        }
        return self::$pdo;
    }

    public static function query(string $sql, array $params = []): PDOStatement
    {
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public static function fetchAll(string $sql, array $params = []): array
    {
        return self::query($sql, $params)->fetchAll();
    }

    public static function fetchOne(string $sql, array $params = []): array|false
    {
        return self::query($sql, $params)->fetch();
    }

    public static function fetchColumn(string $sql, array $params = [], int $col = 0): mixed
    {
        return self::query($sql, $params)->fetchColumn($col);
    }

    public static function insert(string $table, array $data): int|string
    {
        $cols = implode(',', array_map(fn($k) => "`{$k}`", array_keys($data)));
        $placeholders = implode(',', array_fill(0, count($data), '?'));
        self::query("INSERT INTO `{$table}` ({$cols}) VALUES ({$placeholders})", array_values($data));
        return self::pdo()->lastInsertId();
    }

    public static function update(string $table, array $data, string $where, array $whereParams = []): int
    {
        $set = implode(',', array_map(fn($k) => "`{$k}`=?", array_keys($data)));
        $stmt = self::query("UPDATE `{$table}` SET {$set} WHERE {$where}", [...array_values($data), ...$whereParams]);
        return $stmt->rowCount();
    }

    public static function delete(string $table, string $where, array $params = []): int
    {
        return self::query("DELETE FROM `{$table}` WHERE {$where}", $params)->rowCount();
    }

    public static function beginTransaction(): void { self::pdo()->beginTransaction(); }
    public static function commit(): void           { self::pdo()->commit(); }
    public static function rollback(): void         { self::pdo()->rollBack(); }

    public static function lastId(): string { return self::pdo()->lastInsertId(); }
}
