<?php
/**
 * ProETS - Bootstrap
 */
declare(strict_types=1);

use ProETS\Core\Config;
use ProETS\Core\Database;
use ProETS\Core\Session;

// Carica configurazione
$config = require PROETS_ROOT . '/config/config.php';
Config::load($config);

// Timezone
date_default_timezone_set(Config::get('app.timezone', 'Europe/Rome'));

// Sessione sicura
Session::start();

// Connessione DB
Database::connect(Config::get('db'));

// Gestione errori
if (!Config::get('app.debug', false)) {
    error_reporting(0);
    ini_set('display_errors', '0');
    set_error_handler(function($errno, $errstr, $errfile, $errline) {
        error_log("[ProETS] Error {$errno}: {$errstr} in {$errfile}:{$errline}");
        return true;
    });
    set_exception_handler(function(Throwable $e) {
        error_log("[ProETS] Exception: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
        http_response_code(500);
        include PROETS_ROOT . '/templates/errors/500.php';
        exit;
    });
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
}
