<?php
/**
 * ProETS - Entry Point
 * Copyright (C) 2025 ProETS - Scapuzzi Alessandro <dado70@gmail.com>
 * https://www.proets.it | License: GNU GPL v3
 */
declare(strict_types=1);

define('PROETS_ROOT', __DIR__);
define('PROETS_START', microtime(true));

// Blocca accesso se non configurato → redirect a installer
$configFile = PROETS_ROOT . '/config/config.php';
if (!file_exists($configFile)) {
    header('Location: install.php');
    exit;
}

// Autoload Composer (obbligatorio — tcpdf, phpmailer, league/csv, guzzle)
$autoload = PROETS_ROOT . '/vendor/autoload.php';
if (!file_exists($autoload)) {
    http_response_code(500);
    echo '<!DOCTYPE html><html lang="it"><head><meta charset="UTF-8">
    <title>ProETS — Setup incompleto</title>
    <style>body{font-family:sans-serif;max-width:640px;margin:60px auto;padding:20px;color:#333}
    code,pre{background:#f4f4f4;padding:2px 6px;border-radius:3px;font-size:.95em}
    pre{display:block;padding:12px;margin:8px 0}a{color:#0d6efd}</style></head><body>
    <h2>&#9888; ProETS — Dipendenze PHP mancanti</h2>
    <p>La cartella <code>vendor/</code> non è presente. Le librerie PHP richieste
    (TCPDF, PHPMailer, League/CSV, Guzzle) non sono installate.</p>
    <h3>Soluzione — via SSH:</h3>
    <pre>cd /percorso/del/sito
composer install --no-dev --optimize-autoloader</pre>
    <h3>Alternativa — senza SSH:</h3>
    <p>Scarica il pacchetto completo con <code>vendor/</code> incluso dalla pagina
    <a href="https://github.com/dado70/proets/releases">GitHub Releases</a>
    ed estrai sovrascrivendo i file sul server.</p>
    </body></html>';
    exit;
}
require $autoload;

// Bootstrap applicazione
require PROETS_ROOT . '/src/bootstrap.php';

// Lancia il router
use ProETS\Core\Application;
Application::run();
