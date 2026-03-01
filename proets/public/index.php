<?php
/**
 * ProETS - Entry Point
 * Copyright (C) 2025 ProETS - Scapuzzi Alessandro <dado70@gmail.com>
 * https://www.proets.it | License: GNU GPL v3
 */
declare(strict_types=1);

define('PROETS_ROOT', dirname(__DIR__));
define('PROETS_START', microtime(true));

// Blocca accesso se non configurato → redirect a installer
$configFile = PROETS_ROOT . '/config/config.php';
if (!file_exists($configFile)) {
    header('Location: /install/');
    exit;
}

// Autoload
$autoload = PROETS_ROOT . '/vendor/autoload.php';
if (file_exists($autoload)) {
    require $autoload;
}

// Bootstrap applicazione
require PROETS_ROOT . '/src/bootstrap.php';

// Lancia il router
use ProETS\Core\Application;
Application::run();
