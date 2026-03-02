<?php
/**
 * Migration 0001 — Baseline v1.0.0
 * Schema iniziale installato via database.sql (install.php).
 * Questa migration è vuota: serve solo come punto di riferimento
 * per le installazioni fresh, che vengono registrate dall'installer.
 */
return [
    'version'     => '1.0.0',
    'description' => 'Schema iniziale (baseline v1.0.0)',
    'up'          => function (\PDO $pdo): void {
        // Vuoto — schema già installato via install/database.sql
    },
];
