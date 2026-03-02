<?php
/**
 * Migration 0002 — Superadmin Panel v1.1.0-beta
 * Aggiunge pannello superadmin multiaziendale, gestione utenti globale,
 * e migliora il workflow di gestione aziende.
 * Questa migration è vuota: le modifiche erano già in produzione
 * prima dell'introduzione del sistema di migrazioni.
 */
return [
    'version'     => '1.1.0-beta',
    'description' => 'Pannello superadmin multiaziendale + gestione utenti globale',
    'up'          => function (\PDO $pdo): void {
        // Vuoto — già applicato nelle installazioni esistenti
    },
];
