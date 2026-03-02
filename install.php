<?php
/**
 * ProETS — Installer Autonomo
 * Posizionare questo file e la cartella proets/ nella root del web server.
 * Aprire http://server/install.php per avviare l'installazione.
 *
 * Copyright (C) 2025 ProETS — GNU GPL v3
 */
declare(strict_types=1);

/* ── Percorsi ─────────────────────────────────────────────────────────── */
define('PROETS_ROOT',  __DIR__ . '/proets');
define('INSTALL_LOCK', PROETS_ROOT . '/install/installed.lock');
define('SQL_SCHEMA',   PROETS_ROOT . '/install/database.sql');
define('CONFIG_DIR',   PROETS_ROOT . '/config');
define('CONFIG_FILE',  PROETS_ROOT . '/config/config.php');
define('MIN_PHP',      '8.1.0');

/* ── Sicurezza: blocca se già installato ──────────────────────────────── */
if (file_exists(INSTALL_LOCK) && !isset($_GET['force'])) {
    http_response_code(302);
    header('Location: /');
    exit;
}

/* ── Verifica presenza cartella proets/ ──────────────────────────────── */
if (!is_dir(PROETS_ROOT)) {
    http_response_code(500);
    die('<!DOCTYPE html><html lang="it"><head><meta charset="UTF-8">
    <title>Errore</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    </head><body class="d-flex align-items-center justify-content-center" style="min-height:100vh;background:#f8f9fa">
    <div class="card p-5 shadow text-center" style="max-width:500px">
    <i class="fs-1 text-danger">&#9888;</i>
    <h4 class="mt-3">Cartella <code>proets/</code> non trovata</h4>
    <p class="text-muted">Estrai l\'archivio nella root del web server: devono esistere
    <code>install.php</code> e <code>proets/</code> nella stessa directory.</p>
    </div></body></html>');
}

session_start();
$step   = (int)($_SESSION['iStep'] ?? 1);
$iData  = $_SESSION['iData']  ?? [];
$errors = [];

/* ═══════════════════════════════════════════════════════════════════════
   FUNZIONI HELPER
   ═══════════════════════════════════════════════════════════════════════ */

function e(mixed $v): string {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function checkRequirements(): array {
    $p = PROETS_ROOT;
    $checks = [
        ['name'=>'PHP >= '.MIN_PHP,                  'req'=>true,  'ok'=>version_compare(PHP_VERSION,MIN_PHP,'>='), 'val'=>PHP_VERSION],
        ['name'=>'Estensione PDO',                   'req'=>true,  'ok'=>extension_loaded('pdo'),         'val'=>extension_loaded('pdo')        ?'OK':'Mancante'],
        ['name'=>'Estensione pdo_mysql',             'req'=>true,  'ok'=>extension_loaded('pdo_mysql'),   'val'=>extension_loaded('pdo_mysql')   ?'OK':'Mancante'],
        ['name'=>'Estensione mbstring',              'req'=>true,  'ok'=>extension_loaded('mbstring'),    'val'=>extension_loaded('mbstring')    ?'OK':'Mancante'],
        ['name'=>'Estensione openssl',               'req'=>true,  'ok'=>extension_loaded('openssl'),     'val'=>extension_loaded('openssl')     ?'OK':'Mancante'],
        ['name'=>'Estensione json',                  'req'=>true,  'ok'=>extension_loaded('json'),        'val'=>extension_loaded('json')        ?'OK':'Mancante'],
        ['name'=>'Estensione zlib (backup gzip)',    'req'=>false, 'ok'=>extension_loaded('zlib'),        'val'=>extension_loaded('zlib')        ?'OK':'Consigliata'],
        ['name'=>'Estensione curl (backup cloud)',   'req'=>false, 'ok'=>extension_loaded('curl'),        'val'=>extension_loaded('curl')        ?'OK':'Consigliata'],
        ['name'=>'Schema SQL presente',              'req'=>true,  'ok'=>file_exists(SQL_SCHEMA),         'val'=>file_exists(SQL_SCHEMA)         ?'OK':'Non trovato'],
        ['name'=>'proets/config/ scrivibile',        'req'=>true,  'ok'=>is_writable($p.'/config'),       'val'=>is_writable($p.'/config')       ?'OK':'Non scrivibile'],
        ['name'=>'proets/uploads/ scrivibile',       'req'=>true,  'ok'=>is_writable($p.'/uploads'),      'val'=>is_writable($p.'/uploads')      ?'OK':'Non scrivibile'],
        ['name'=>'proets/logs/ scrivibile',          'req'=>true,  'ok'=>is_writable($p.'/logs'),         'val'=>is_writable($p.'/logs')         ?'OK':'Non scrivibile'],
        ['name'=>'proets/backups/ scrivibile',       'req'=>true,  'ok'=>is_writable($p.'/backups'),      'val'=>is_writable($p.'/backups')      ?'OK':'Non scrivibile'],
    ];
    return $checks;
}

function allRequiredOk(array $checks): bool {
    foreach ($checks as $c) {
        if ($c['req'] && !$c['ok']) return false;
    }
    return true;
}

/** Parser SQL robusto: gestisce stringhe, backtick e punto e virgola embedded */
function parseSql(string $sql): array {
    $sql     = preg_replace('/--[^\n]*\n/', "\n", $sql . "\n");
    $sql     = preg_replace('/\/\*.*?\*\//s', '', $sql);
    $out     = [];
    $buf     = '';
    $inStr   = false;
    $strCh   = '';
    $len     = strlen($sql);

    for ($i = 0; $i < $len; $i++) {
        $ch = $sql[$i];
        if (!$inStr && ($ch === "'" || $ch === '"' || $ch === '`')) {
            $inStr = true; $strCh = $ch; $buf .= $ch;
        } elseif ($inStr && $ch === $strCh) {
            /* Gestione escape doppio (es. '' in SQL) */
            if (isset($sql[$i+1]) && $sql[$i+1] === $strCh) {
                $buf .= $ch . $strCh; $i++;
            } else {
                $inStr = false; $buf .= $ch;
            }
        } elseif (!$inStr && $ch === ';') {
            $s = trim($buf);
            if ($s !== '') $out[] = $s;
            $buf = '';
        } else {
            $buf .= $ch;
        }
    }
    $s = trim($buf);
    if ($s !== '') $out[] = $s;
    return $out;
}

function generateConfig(array $db, string $appKey, string $appUrl): string {
    // Deriva base_path dal path dell'URL (es. https://domain.it/proets → /proets)
    $parsed   = parse_url($appUrl);
    $basePath = rtrim($parsed['path'] ?? '', '/');

    // session.secure = true se HTTPS
    $isHttps  = str_starts_with($appUrl, 'https://');

    $lines = [
        '<?php',
        '/**',
        ' * ProETS - Configurazione applicazione',
        ' * Generata automaticamente dall\'installer il ' . date('d/m/Y H:i:s'),
        ' * Non modificare manualmente a meno che non sia strettamente necessario.',
        ' */',
        "define('PROETS_CONFIGURED', true);",
        '',
        'return [',
        "    'app' => [",
        "        'name'      => 'ProETS',",
        "        'version'   => '1.0.0',",
        "        'key'       => " . var_export($appKey, true) . ',',
        "        'debug'     => false,",
        "        'timezone'  => 'Europe/Rome',",
        "        'locale'    => 'it_IT',",
        "        'url'       => " . var_export(rtrim($appUrl, '/'), true) . ',',
        "        'base_path' => " . var_export($basePath, true) . ',',
        "    ],",
        "    'db' => [",
        "        'host'    => " . var_export($db['host'], true) . ',',
        "        'port'    => " . (int)$db['port'] . ',',
        "        'name'    => " . var_export($db['name'], true) . ',',
        "        'user'    => " . var_export($db['user'], true) . ',',
        "        'pass'    => " . var_export($db['pass'], true) . ',',
        "        'charset' => 'utf8mb4',",
        "    ],",
        "    'session' => [",
        "        'lifetime' => 7200,",
        "        'secure'   => " . ($isHttps ? 'true' : 'false') . ',',
        "        'httponly' => true,",
        "        'samesite' => 'Strict',",
        "    ],",
        "    'gdpr' => [",
        "        'data_retention_years' => 10,",
        "        'dpo_email'            => '',",
        "        'cookie_lifetime'      => 365,",
        "    ],",
        "    'backup' => [",
        "        'local_path' => __DIR__.'/../backups/',",
        "        'max_local'  => 7,",
        "    ],",
        '];',
    ];
    return implode("\n", $lines) . "\n";
}

function detectBaseUrl(): string {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $script = $_SERVER['PHP_SELF'] ?? '/install.php';
    $dir    = rtrim(dirname($script), '/');
    return $scheme . '://' . $host . ($dir === '/' || $dir === '' ? '' : $dir);
}

function rootHtaccess(): string {
    return <<<'HTACCESS'
# ProETS — generato dall'installer
# Richiede mod_rewrite abilitato in Apache (AllowOverride All)
Options -Indexes
<IfModule mod_rewrite.c>
    RewriteEngine On

    # Permetti accesso diretto all'installer
    RewriteRule ^install\.php$ - [L]

    # Permetti file statici di proets/public/
    RewriteRule ^proets/public/ - [L]

    # Tutto il resto viene gestito dall'applicazione
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^(.*)$ proets/public/index.php [L,QSA]
</IfModule>
HTACCESS;
}

/* ═══════════════════════════════════════════════════════════════════════
   GESTIONE POST
   ═══════════════════════════════════════════════════════════════════════ */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /* Autodistruzione file installer */
    if (($_POST['action'] ?? '') === 'self_delete') {
        @unlink(__FILE__);
        // Redirect alla root dell'applicazione (gestisce sia root che sottocartella)
        $appDir = rtrim(dirname($_SERVER['PHP_SELF'] ?? '/'), '/');
        header('Location: ' . ($appDir ?: '/'));
        exit;
    }

    $postStep = (int)($_POST['from_step'] ?? 0);

    /* ── STEP 1 → 2 (solo avanzamento) ─────────────────────────────── */
    if ($postStep === 1) {
        $_SESSION['iStep'] = 2;
        header('Location: ' . $_SERVER['PHP_SELF']); exit;
    }

    /* ── STEP 2 → 3 (configurazione database) ───────────────────────── */
    if ($postStep === 2) {
        $mode     = $_POST['install_mode'] ?? 'shared'; // 'shared' | 'dedicated'
        $dbHost   = trim($_POST['db_host'] ?? 'localhost');
        $dbPort   = (int)($_POST['db_port'] ?? 3306);
        $dbName   = trim($_POST['db_name'] ?? 'proets');
        $dbUser   = trim($_POST['db_user'] ?? '');
        $dbPass   = $_POST['db_pass'] ?? '';

        if (empty($dbName)) $errors[] = 'Nome database obbligatorio.';
        if (empty($dbUser)) $errors[] = 'Nome utente database obbligatorio.';
        if (empty($dbPass)) $errors[] = 'Password utente database obbligatoria.';

        if ($mode === 'dedicated') {
            /* In modalità dedicated i campi hanno suffisso _d */
            $dbHost   = trim($_POST['root_host_d'] ?? 'localhost');
            $dbPort   = (int)($_POST['root_port_d'] ?? 3306);
            $dbName   = trim($_POST['db_name_d'] ?? 'proets');
            $dbUser   = trim($_POST['db_user_d'] ?? '');
            $dbPass   = $_POST['db_pass_d'] ?? '';
            $rootUser = trim($_POST['root_user'] ?? 'root');
            $rootPass = $_POST['root_pass'] ?? '';
            $dbPass2  = $_POST['db_pass2'] ?? '';
            if (strlen($dbPass) < 8)  $errors[] = 'Password utente DB: minimo 8 caratteri.';
            if ($dbPass !== $dbPass2) $errors[] = 'Le password dell\'utente DB non coincidono.';
            if (!preg_match('/^\w+$/', $dbName)) $errors[] = 'Nome database: solo lettere, numeri e underscore.';
            if (!preg_match('/^\w+$/', $dbUser)) $errors[] = 'Utente database: solo lettere, numeri e underscore.';
        }

        if (empty($errors)) {
            try {
                if ($mode === 'dedicated') {
                    /* Connessione root per creare DB e utente */
                    $dsn = "mysql:host={$dbHost};port={$dbPort};charset=utf8mb4";
                    $pdo = new PDO($dsn, $rootUser, $rootPass, [
                        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
                    ]);
                    $ver = $pdo->query("SELECT VERSION()")->fetchColumn();

                    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

                    $userExists = (int)$pdo->query(
                        "SELECT COUNT(*) FROM mysql.user WHERE User=" . $pdo->quote($dbUser) . " AND Host='localhost'"
                    )->fetchColumn();
                    if ($userExists) {
                        $pdo->exec("ALTER USER `{$dbUser}`@`localhost` IDENTIFIED BY " . $pdo->quote($dbPass));
                    } else {
                        $pdo->exec("CREATE USER `{$dbUser}`@`localhost` IDENTIFIED BY " . $pdo->quote($dbPass));
                    }
                    $pdo->exec("GRANT ALL PRIVILEGES ON `{$dbName}`.* TO `{$dbUser}`@`localhost`");
                    $pdo->exec("FLUSH PRIVILEGES");
                } else {
                    /* Hosting condiviso: verifica solo la connessione con le credenziali fornite */
                    $dsn = "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4";
                    $pdo = new PDO($dsn, $dbUser, $dbPass, [
                        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
                    ]);
                    $ver = $pdo->query("SELECT VERSION()")->fetchColumn();
                }

                $_SESSION['iData']['db'] = [
                    'host' => $dbHost,
                    'port' => $dbPort,
                    'name' => $dbName,
                    'user' => $dbUser,
                    'pass' => $dbPass,
                    'ver'  => $ver,
                    'mode' => $mode,
                ];
                $_SESSION['iStep'] = 3;
                header('Location: ' . $_SERVER['PHP_SELF']); exit;

            } catch (PDOException $e) {
                if ($mode === 'dedicated') {
                    $errors[] = 'Errore connessione root: ' . $e->getMessage();
                    $errors[] = 'Verifica che l\'utente root abbia i permessi di GRANT e che il firewall permetta la connessione.';
                } else {
                    $errors[] = 'Connessione al database non riuscita: ' . $e->getMessage();
                    $errors[] = 'Verifica host, nome database, utente e password forniti dal tuo hosting.';
                }
            }
        }
        $step = 2;
    }

    /* ── STEP 3 → 4 (validazione + installazione completa) ──────────── */
    if ($postStep === 3) {
        $ragSoc    = trim($_POST['ragione_sociale'] ?? '');
        $formaGiur = trim($_POST['forma_giuridica'] ?? 'APS');
        $cfAss     = strtoupper(trim($_POST['codice_fiscale'] ?? ''));
        $cittaAss  = trim($_POST['citta'] ?? '');
        $emailAss  = trim($_POST['email_ass'] ?? '');
        $esercizio = (int)($_POST['esercizio_corrente'] ?? (int)date('Y'));
        $appUrl    = rtrim(trim($_POST['app_url'] ?? detectBaseUrl()), '/');

        $adminNome = trim($_POST['admin_nome'] ?? '');
        $adminCog  = trim($_POST['admin_cognome'] ?? '');
        $adminUser = strtolower(trim($_POST['admin_username'] ?? ''));
        $adminMail = trim($_POST['admin_email'] ?? '');
        $adminPass = $_POST['admin_pass'] ?? '';
        $adminPas2 = $_POST['admin_pass2'] ?? '';
        $gdprOk    = !empty($_POST['gdpr_consent']);

        if (empty($ragSoc))          $errors[] = 'Ragione sociale obbligatoria.';
        if (empty($adminUser))       $errors[] = 'Username amministratore obbligatorio.';
        if (!preg_match('/^[a-z0-9._-]{3,50}$/', $adminUser))
                                     $errors[] = 'Username: 3-50 caratteri, solo minuscole, numeri, punto, trattino.';
        if (!filter_var($adminMail, FILTER_VALIDATE_EMAIL))
                                     $errors[] = 'Email amministratore non valida.';
        if (strlen($adminPass) < 8)  $errors[] = 'Password: minimo 8 caratteri.';
        if ($adminPass !== $adminPas2) $errors[] = 'Le password non coincidono.';
        if (!$gdprOk)                $errors[] = 'Devi accettare il trattamento dei dati personali.';

        if (empty($errors)) {
            $db = $_SESSION['iData']['db'] ?? null;
            if (!$db) {
                $errors[] = 'Sessione scaduta. Riparti dal passo 1.';
                $_SESSION['iStep'] = 1; $step = 1;
            } else {
                $log = [];
                $installOk = false;
                try {
                    /* Connessione con utente applicazione */
                    $dsn = "mysql:host={$db['host']};port={$db['port']};dbname={$db['name']};charset=utf8mb4";
                    $pdo = new PDO($dsn, $db['user'], $db['pass'], [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
                    ]);
                    $log[] = ['ok'=>true, 'msg'=>"Connessione al database <code>{$db['name']}</code> riuscita"];

                    /* Crea cartelle mancanti */
                    $dirs = ['config','uploads','uploads/loghi','logs','backups'];
                    foreach ($dirs as $d) {
                        $path = PROETS_ROOT . '/' . $d;
                        if (!is_dir($path)) {
                            if (mkdir($path, 0755, true)) {
                                $log[] = ['ok'=>true,  'msg'=>"Cartella <code>proets/{$d}/</code> creata"];
                            } else {
                                $log[] = ['ok'=>false, 'msg'=>"Impossibile creare <code>proets/{$d}/</code>"];
                            }
                        }
                    }

                    /* Importa schema SQL */
                    $sql    = file_get_contents(SQL_SCHEMA);
                    $stmts  = parseSql($sql);
                    $nOk    = 0; $nSkip = 0;
                    foreach ($stmts as $stmt) {
                        try {
                            $pdo->exec($stmt);
                            $nOk++;
                        } catch (PDOException $ex) {
                            /* Ignora "tabella già esiste" e simili */
                            $nSkip++;
                        }
                    }
                    $log[] = ['ok'=>true, 'msg'=>"Schema DB importato: {$nOk} statement" . ($nSkip ? ", {$nSkip} già presenti/ignorati" : '')];

                    /* Inserisce azienda */
                    $codice = strtoupper(preg_replace('/[^A-Z0-9]/', '', $ragSoc));
                    $codice = substr($codice ?: 'APS', 0, 8) . '001';
                    $pdo->prepare(
                        "INSERT INTO companies (codice, ragione_sociale, forma_giuridica, codice_fiscale, citta, email, esercizio_corrente)
                         VALUES (?,?,?,?,?,?,?)"
                    )->execute([$codice, $ragSoc, $formaGiur, $cfAss ?: null, $cittaAss ?: null, $emailAss ?: null, $esercizio]);
                    $companyId = (int)$pdo->lastInsertId();
                    $log[] = ['ok'=>true, 'msg'=>"Associazione <strong>" . e($ragSoc) . "</strong> registrata (ID: {$companyId})"];

                    /* Inserisce amministratore */
                    $hash = password_hash($adminPass, PASSWORD_BCRYPT, ['cost' => 12]);
                    $pdo->prepare(
                        "INSERT INTO users (username, email, password, nome, cognome, ruolo, attivo, email_verificata, gdpr_consenso, gdpr_data)
                         VALUES (?, ?, ?, ?, ?, 'superadmin', 1, 1, 1, NOW())"
                    )->execute([$adminUser, $adminMail, $hash, $adminNome, $adminCog]);
                    $userId = (int)$pdo->lastInsertId();
                    $log[] = ['ok'=>true, 'msg'=>"Amministratore <strong>" . e($adminUser) . "</strong> creato (ID: {$userId})"];

                    /* Associa utente ad azienda */
                    $pdo->prepare(
                        "INSERT INTO user_companies (user_id, company_id, ruolo_azienda) VALUES (?, ?, 'admin')"
                    )->execute([$userId, $companyId]);
                    $log[] = ['ok'=>true, 'msg'=>"Amministratore associato all'azienda"];

                    /* Genera config.php */
                    $appKey = bin2hex(random_bytes(32));
                    if (file_put_contents(CONFIG_FILE, generateConfig($db, $appKey, $appUrl)) !== false) {
                        $log[] = ['ok'=>true, 'msg'=>"File <code>proets/config/config.php</code> generato"];
                    } else {
                        throw new RuntimeException("Impossibile scrivere config.php");
                    }

                    /* Crea root .htaccess */
                    $htFile = __DIR__ . '/.htaccess';
                    if (file_put_contents($htFile, rootHtaccess()) !== false) {
                        $log[] = ['ok'=>true, 'msg'=>"File <code>.htaccess</code> root creato"];
                    } else {
                        $log[] = ['ok'=>false, 'msg'=>"Impossibile creare .htaccess root — crealo manualmente (vedi istruzioni)"];
                    }

                    /* Lock installazione */
                    file_put_contents(INSTALL_LOCK, date('Y-m-d H:i:s') . ' — installed by ' . $adminUser . "\n");
                    $log[] = ['ok'=>true, 'msg'=>"File di blocco installazione creato"];

                    $installOk = true;

                } catch (Throwable $e) {
                    $log[] = ['ok'=>false, 'msg'=>"<strong>Errore fatale:</strong> " . e($e->getMessage())];
                    $errors[] = 'Installazione non riuscita: ' . $e->getMessage();
                }

                $_SESSION['iData']['log']       = $log;
                $_SESSION['iData']['installOk'] = $installOk;
                $_SESSION['iData']['adminUser']  = $adminUser;
                $_SESSION['iData']['adminEmail'] = $adminMail;
                $_SESSION['iData']['appUrl']     = $appUrl;
                $_SESSION['iData']['ragSoc']     = $ragSoc;
                $_SESSION['iStep'] = 4;
                header('Location: ' . $_SERVER['PHP_SELF']); exit;
            }
        }
        $step = 3;
    }
}

/* Aggiorna step da sessione */
if (empty($errors)) {
    $step  = (int)($_SESSION['iStep'] ?? 1);
}
$iData = $_SESSION['iData'] ?? [];

/* ═══════════════════════════════════════════════════════════════════════
   HTML
   ═══════════════════════════════════════════════════════════════════════ */
?>
<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>ProETS — Installazione</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
  body { background: #eef2f7; font-family: 'Segoe UI', system-ui, sans-serif; padding-bottom: 3rem; }
  .inst-wrap { max-width: 800px; margin: 2.5rem auto; }
  .inst-header {
    background: linear-gradient(135deg, #1a3a5c 0%, #2563eb 100%);
    color: #fff; border-radius: 16px 16px 0 0; padding: 2rem 2.5rem 0;
  }
  .logo-mark { font-size: 2.2rem; font-weight: 800; letter-spacing: -1px; }
  .logo-sub  { opacity: .72; font-size: .9rem; margin-top: .2rem; }
  .steps     { display: flex; margin-top: 1.8rem; gap: 0; }
  .step-item {
    flex: 1; text-align: center; padding: .5rem .25rem;
    font-size: .78rem; opacity: .5;
    border-bottom: 3px solid rgba(255,255,255,.25); transition: all .2s;
  }
  .step-item.active  { opacity: 1; border-bottom-color: #fff; font-weight: 700; }
  .step-item.done    { opacity: .75; border-bottom-color: rgba(255,255,255,.55); }
  .step-num {
    width: 26px; height: 26px; border-radius: 50%;
    background: rgba(255,255,255,.2); display: inline-flex;
    align-items: center; justify-content: center; margin-bottom: .25rem;
    font-weight: 700; font-size: .8rem;
  }
  .step-item.done .step-num { background: rgba(255,255,255,.5); }
  .step-item.active .step-num { background: #fff; color: #1a3a5c; }
  .inst-body { background: #fff; border-radius: 0 0 16px 16px; padding: 2rem 2.5rem; box-shadow: 0 6px 32px rgba(0,0,0,.1); }
  .req-row   { display: flex; align-items: center; padding: .45rem 0; border-bottom: 1px solid #f3f4f6; gap: .75rem; }
  .req-icon  { width: 26px; height: 26px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: .85rem; flex-shrink: 0; }
  .icon-ok   { background: #d1fae5; color: #065f46; }
  .icon-warn { background: #fef9c3; color: #713f12; }
  .icon-err  { background: #fee2e2; color: #991b1b; }
  .log-row   { display: flex; align-items: flex-start; gap: .6rem; padding: .3rem 0; font-size: .88rem; }
  .pw-bar    { height: 4px; border-radius: 2px; transition: width .3s, background .3s; }
  .section-sep { font-size: .7rem; font-weight: 700; text-transform: uppercase; letter-spacing: .08em; color: #6b7280; margin: 1.25rem 0 .75rem; display: flex; align-items: center; gap: .5rem; }
  .section-sep::after { content:''; flex:1; height:1px; background:#e5e7eb; }
  .foot { text-align: center; color: #9ca3af; font-size: .8rem; padding: 1rem; }
</style>
</head>
<body>
<div class="inst-wrap">

  <!-- Header con step indicator -->
  <div class="inst-header">
    <div class="d-flex align-items-center gap-3">
      <i class="bi bi-building-check fs-2"></i>
      <div>
        <div class="logo-mark">ProETS</div>
        <div class="logo-sub">Installazione — Sistema Gestionale per Associazioni APS/ETS</div>
      </div>
    </div>
    <div class="steps">
      <?php
      $stepDefs = [
        1 => ['icon'=>'clipboard-check', 'label'=>'Requisiti'],
        2 => ['icon'=>'database',        'label'=>'Database'],
        3 => ['icon'=>'building',        'label'=>'Configurazione'],
        4 => ['icon'=>'check-circle',    'label'=>'Completato'],
      ];
      foreach ($stepDefs as $n => $sd):
        $cls = ($n === $step) ? 'active' : ($n < $step ? 'done' : '');
      ?>
      <div class="step-item <?= $cls ?>">
        <div class="step-num">
          <?= $n < $step ? '<i class="bi bi-check-lg"></i>' : $n ?>
        </div>
        <div><?= $sd['label'] ?></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div><!-- /inst-header -->

  <!-- Corpo -->
  <div class="inst-body">

    <!-- Errori -->
    <?php foreach ($errors as $err): ?>
    <div class="alert alert-danger py-2 d-flex align-items-start gap-2">
      <i class="bi bi-exclamation-triangle-fill flex-shrink-0 mt-1"></i>
      <span><?= $err ?></span>
    </div>
    <?php endforeach; ?>

    <?php /* ════════════════════════════════════════
       STEP 1 — REQUISITI
       ════════════════════════════════════════ */ if ($step === 1): ?>

    <h5 class="mb-3 fw-bold"><i class="bi bi-clipboard-check me-2 text-primary"></i>Verifica Requisiti di Sistema</h5>
    <?php
    $checks = checkRequirements();
    $allOk  = allRequiredOk($checks);
    foreach ($checks as $c):
      if (!$c['ok'] && !$c['req']) $cls = 'icon-warn';
      elseif ($c['ok'])            $cls = 'icon-ok';
      else                         $cls = 'icon-err';
    ?>
    <div class="req-row">
      <div class="req-icon <?= $cls ?>">
        <i class="bi bi-<?= $c['ok'] ? 'check-lg' : ($c['req'] ? 'x-lg' : 'exclamation-lg') ?>"></i>
      </div>
      <div class="flex-grow-1"><?= e($c['name']) ?></div>
      <div class="text-muted small"><?= e($c['val']) ?></div>
    </div>
    <?php endforeach; ?>

    <?php if (!$allOk): ?>
    <div class="alert alert-danger mt-3 py-2 small">
      <i class="bi bi-shield-exclamation me-2"></i>
      <strong>Risolvi i requisiti obbligatori</strong> prima di continuare.
      Le voci "Consigliata" non bloccano l'installazione ma riducono le funzionalità disponibili.
    </div>
    <a href="<?= $_SERVER['PHP_SELF'] ?>" class="btn btn-outline-secondary">
      <i class="bi bi-arrow-clockwise me-2"></i>Ricontrolla
    </a>
    <?php else: ?>
    <div class="alert alert-success mt-3 py-2 small">
      <i class="bi bi-check-circle-fill me-2"></i>Tutti i requisiti obbligatori sono soddisfatti. Puoi procedere.
    </div>
    <form method="post">
      <input type="hidden" name="from_step" value="1">
      <button type="submit" class="btn btn-primary px-4">
        <i class="bi bi-arrow-right me-2"></i>Continua — Configurazione Database
      </button>
    </form>
    <?php endif; ?>

    <?php /* ════════════════════════════════════════
       STEP 2 — DATABASE
       ════════════════════════════════════════ */ elseif ($step === 2): ?>

    <h5 class="mb-1 fw-bold"><i class="bi bi-database me-2 text-primary"></i>Configurazione Database</h5>

    <form method="post" id="form-db">
      <input type="hidden" name="from_step" value="2">

      <!-- Selezione modalità -->
      <div class="row g-3 mb-3">
        <div class="col-12">
          <label class="form-label fw-semibold">Tipo di ambiente <span class="text-danger">*</span></label>
          <div class="d-flex gap-3 flex-wrap">
            <div class="form-check form-check-inline">
              <input class="form-check-input" type="radio" name="install_mode" id="mode_shared" value="shared"
                <?= (($_POST['install_mode'] ?? 'shared') === 'shared') ? 'checked' : '' ?>
                onchange="toggleInstallMode('shared')">
              <label class="form-check-label" for="mode_shared">
                <i class="bi bi-cloud me-1 text-primary"></i>
                <strong>Hosting condiviso</strong>
                <span class="text-muted small d-block">cPanel, Plesk, Aruba, Register.it ecc.<br>DB e utente già creati dal pannello.</span>
              </label>
            </div>
            <div class="form-check form-check-inline">
              <input class="form-check-input" type="radio" name="install_mode" id="mode_dedicated" value="dedicated"
                <?= (($_POST['install_mode'] ?? '') === 'dedicated') ? 'checked' : '' ?>
                onchange="toggleInstallMode('dedicated')">
              <label class="form-check-label" for="mode_dedicated">
                <i class="bi bi-server me-1 text-secondary"></i>
                <strong>Server dedicato / VPS / locale</strong>
                <span class="text-muted small d-block">Hai accesso root MySQL.<br>L'installer crea DB e utente automaticamente.</span>
              </label>
            </div>
          </div>
        </div>
      </div>

      <!-- Sezione hosting condiviso -->
      <div id="section-shared">
        <div class="alert alert-info py-2 small mb-3">
          <i class="bi bi-info-circle me-1"></i>
          Crea prima il database e l'utente dal pannello del tuo hosting (cPanel → Database MySQL,
          Plesk → Database), poi inserisci qui le credenziali che ti sono state fornite.
        </div>
        <div class="section-sep"><i class="bi bi-database"></i>Credenziali Database (fornite dal hosting)</div>
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label fw-semibold">Host database <span class="text-danger">*</span></label>
            <input type="text" name="db_host" class="form-control" value="<?= e($_POST['db_host'] ?? 'localhost') ?>">
            <div class="form-text">Di solito <code>localhost</code>. Il tuo hosting te lo indica.</div>
          </div>
          <div class="col-md-2">
            <label class="form-label fw-semibold">Porta</label>
            <input type="number" name="db_port" class="form-control" value="<?= e($_POST['db_port'] ?? '3306') ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold">Nome database <span class="text-danger">*</span></label>
            <input type="text" name="db_name" class="form-control" value="<?= e($_POST['db_name'] ?? '') ?>" placeholder="utente_proets">
          </div>
          <div class="col-md-5">
            <label class="form-label fw-semibold">Utente database <span class="text-danger">*</span></label>
            <input type="text" name="db_user" class="form-control" value="<?= e($_POST['db_user'] ?? '') ?>" placeholder="utente_proets" autocomplete="username">
          </div>
          <div class="col-md-7">
            <label class="form-label fw-semibold">Password database <span class="text-danger">*</span></label>
            <div class="input-group">
              <input type="password" name="db_pass" id="db_pass" class="form-control" autocomplete="current-password" placeholder="Password del database">
              <button class="btn btn-outline-secondary" type="button" onclick="togglePw('db_pass',this)"><i class="bi bi-eye"></i></button>
            </div>
          </div>
        </div>
      </div>

      <!-- Sezione server dedicato / VPS -->
      <div id="section-dedicated" style="display:none">
        <div class="alert alert-secondary py-2 small mb-3">
          <i class="bi bi-person-lock me-1"></i>
          Inserisci le credenziali <strong>root</strong> di MariaDB/MySQL.
          L'installer creerà database e utente dedicato. La password root <em>non viene salvata</em>.
        </div>
        <div class="section-sep"><i class="bi bi-person-lock"></i>Credenziali Root MariaDB/MySQL</div>
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label fw-semibold">Host <span class="text-danger">*</span></label>
            <input type="text" name="root_host_d" class="form-control" value="<?= e($_POST['root_host_d'] ?? 'localhost') ?>">
          </div>
          <div class="col-md-2">
            <label class="form-label fw-semibold">Porta</label>
            <input type="number" name="root_port_d" class="form-control" value="<?= e($_POST['root_port_d'] ?? '3306') ?>">
          </div>
          <div class="col-md-4">
            <label class="form-label fw-semibold">Utente root</label>
            <input type="text" name="root_user" class="form-control" value="<?= e($_POST['root_user'] ?? 'root') ?>" autocomplete="username">
          </div>
          <div class="col-12">
            <label class="form-label fw-semibold">Password root</label>
            <div class="input-group">
              <input type="password" name="root_pass" id="root_pass" class="form-control" autocomplete="current-password" placeholder="Password root MariaDB">
              <button class="btn btn-outline-secondary" type="button" onclick="togglePw('root_pass',this)"><i class="bi bi-eye"></i></button>
            </div>
            <div class="form-text">Se root non ha password lascia vuoto (solo ambienti locali).</div>
          </div>
        </div>
        <div class="section-sep mt-4"><i class="bi bi-database-add"></i>Nuovo Database e Utente da creare</div>
        <div class="row g-3">
          <div class="col-md-5">
            <label class="form-label fw-semibold">Nome database <span class="text-danger">*</span></label>
            <input type="text" name="db_name_d" class="form-control" value="<?= e($_POST['db_name_d'] ?? 'proets') ?>"
              pattern="\w+" title="Solo lettere, numeri e underscore" placeholder="proets">
            <div class="form-text">Verrà creato automaticamente.</div>
          </div>
          <div class="col-md-5">
            <label class="form-label fw-semibold">Username utente DB <span class="text-danger">*</span></label>
            <input type="text" name="db_user_d" class="form-control" value="<?= e($_POST['db_user_d'] ?? 'proets_user') ?>"
              pattern="\w+" placeholder="proets_user">
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold">Password utente DB <span class="text-danger">*</span></label>
            <div class="input-group">
              <input type="password" name="db_pass_d" id="db_pass_d" class="form-control" minlength="8"
                autocomplete="new-password" oninput="checkPwStrength(this,'db-bar','db-lbl')" placeholder="Minimo 8 caratteri">
              <button class="btn btn-outline-secondary" type="button" onclick="togglePw('db_pass_d',this)"><i class="bi bi-eye"></i></button>
            </div>
            <div class="pw-bar mt-1" id="db-bar" style="width:0;background:#ef4444"></div>
            <div class="form-text" id="db-lbl"></div>
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold">Conferma password DB <span class="text-danger">*</span></label>
            <div class="input-group">
              <input type="password" name="db_pass2" id="db_pass2" class="form-control" minlength="8"
                autocomplete="new-password" oninput="checkMatch('db_pass_d','db_pass2','match-db')">
              <button class="btn btn-outline-secondary" type="button" onclick="togglePw('db_pass2',this)"><i class="bi bi-eye"></i></button>
            </div>
            <div class="form-text" id="match-db"></div>
          </div>
        </div>
      </div>

      <div class="d-flex gap-2 mt-4">
        <a href="<?= $_SERVER['PHP_SELF'] ?>?step=1" class="btn btn-outline-secondary">
          <i class="bi bi-arrow-left me-1"></i>Indietro
        </a>
        <button type="submit" class="btn btn-primary px-4" id="btn-db-submit">
          <i class="bi bi-plug me-2"></i><span id="btn-db-label">Verifica Connessione</span>
        </button>
      </div>
    </form>

    <?php /* ════════════════════════════════════════
       STEP 3 — ASSOCIAZIONE + ADMIN
       ════════════════════════════════════════ */ elseif ($step === 3): ?>

    <?php $dbInfo = $iData['db'] ?? []; ?>
    <h5 class="mb-1 fw-bold"><i class="bi bi-building me-2 text-primary"></i>Configurazione Applicazione</h5>
    <?php if ($dbInfo): ?>
    <div class="alert alert-success py-2 small mb-3">
      <i class="bi bi-check-circle-fill me-2"></i>
      Database <strong><?= e($dbInfo['name']) ?></strong> creato su
      <strong><?= e($dbInfo['host']) ?></strong> (MariaDB/MySQL <?= e($dbInfo['ver']) ?>)
      — Utente: <strong><?= e($dbInfo['user']) ?></strong>
    </div>
    <?php endif; ?>

    <form method="post" id="form-conf">
      <input type="hidden" name="from_step" value="3">

      <!-- Associazione -->
      <div class="section-sep"><i class="bi bi-building"></i>Dati Associazione</div>
      <div class="row g-3">
        <div class="col-md-7">
          <label class="form-label fw-semibold">Ragione Sociale / Nome Associazione <span class="text-danger">*</span></label>
          <input type="text" name="ragione_sociale" class="form-control" value="<?= e($_POST['ragione_sociale'] ?? '') ?>" required>
        </div>
        <div class="col-md-3">
          <label class="form-label fw-semibold">Forma Giuridica</label>
          <select name="forma_giuridica" class="form-select">
            <?php foreach (['APS','ODV','ETS','ONLUS','Altro'] as $fg): ?>
            <option <?= ($fg===($_POST['forma_giuridica']??'APS'))?'selected':'' ?>><?= $fg ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label fw-semibold">Codice Fiscale</label>
          <input type="text" name="codice_fiscale" class="form-control text-uppercase" value="<?= e($_POST['codice_fiscale'] ?? '') ?>" maxlength="16" placeholder="Opzionale">
        </div>
        <div class="col-md-4">
          <label class="form-label fw-semibold">Città</label>
          <input type="text" name="citta" class="form-control" value="<?= e($_POST['citta'] ?? '') ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label fw-semibold">Email Associazione</label>
          <input type="email" name="email_ass" class="form-control" value="<?= e($_POST['email_ass'] ?? '') ?>">
        </div>
        <div class="col-md-3">
          <label class="form-label fw-semibold">Esercizio Corrente</label>
          <select name="esercizio_corrente" class="form-select">
            <?php foreach (range((int)date('Y')+1, (int)date('Y')-1) as $y): ?>
            <option value="<?= $y ?>" <?= $y==(int)date('Y')?'selected':'' ?>><?= $y ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-9">
          <label class="form-label fw-semibold">URL Applicazione <span class="text-danger">*</span></label>
          <input type="url" name="app_url" class="form-control" value="<?= e($_POST['app_url'] ?? detectBaseUrl()) ?>"
            required placeholder="https://gestionale.miaassociazione.it">
          <div class="form-text">URL base senza slash finale — usato per i link nelle email.</div>
        </div>
      </div>

      <!-- Amministratore -->
      <div class="section-sep mt-4"><i class="bi bi-person-gear"></i>Amministratore Principale</div>
      <div class="row g-3">
        <div class="col-md-5">
          <label class="form-label fw-semibold">Nome</label>
          <input type="text" name="admin_nome" class="form-control" value="<?= e($_POST['admin_nome'] ?? '') ?>">
        </div>
        <div class="col-md-5">
          <label class="form-label fw-semibold">Cognome</label>
          <input type="text" name="admin_cognome" class="form-control" value="<?= e($_POST['admin_cognome'] ?? '') ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label fw-semibold">Username <span class="text-danger">*</span></label>
          <input type="text" name="admin_username" class="form-control" value="<?= e($_POST['admin_username'] ?? '') ?>"
            required pattern="[a-z0-9._\-]{3,50}" title="3-50 caratteri: minuscole, numeri, punto, trattino"
            placeholder="admin" autocomplete="username">
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">Email <span class="text-danger">*</span></label>
          <input type="email" name="admin_email" class="form-control" value="<?= e($_POST['admin_email'] ?? '') ?>" required autocomplete="email">
        </div>
        <div class="col-md-5">
          <label class="form-label fw-semibold">Password <span class="text-danger">*</span></label>
          <div class="input-group">
            <input type="password" name="admin_pass" id="admin_pass" class="form-control" required minlength="8"
              autocomplete="new-password" oninput="checkPwStrength(this,'adm-bar','adm-lbl')">
            <button class="btn btn-outline-secondary" type="button" onclick="togglePw('admin_pass',this)"><i class="bi bi-eye"></i></button>
          </div>
          <div class="pw-bar mt-1" id="adm-bar" style="width:0;background:#ef4444"></div>
          <div class="form-text" id="adm-lbl">Minimo 8 caratteri.</div>
        </div>
        <div class="col-md-5">
          <label class="form-label fw-semibold">Conferma Password <span class="text-danger">*</span></label>
          <div class="input-group">
            <input type="password" name="admin_pass2" id="admin_pass2" class="form-control" required minlength="8"
              autocomplete="new-password" oninput="checkMatch('admin_pass','admin_pass2','match-adm')">
            <button class="btn btn-outline-secondary" type="button" onclick="togglePw('admin_pass2',this)"><i class="bi bi-eye"></i></button>
          </div>
          <div class="form-text" id="match-adm"></div>
        </div>
      </div>

      <!-- GDPR -->
      <div class="card bg-light border-0 p-3 mt-4">
        <p class="small fw-semibold mb-1">Informativa Privacy (GDPR — Regolamento UE 2016/679)</p>
        <p class="small text-muted mb-2">
          I dati inseriti sono trattati esclusivamente per il funzionamento del sistema ProETS.
          Il titolare del trattamento è l'organizzazione che installa il software.
          I dati non vengono trasmessi a terzi da parte di ProETS.
        </p>
        <div class="form-check">
          <input type="checkbox" name="gdpr_consent" id="gdpr_consent" class="form-check-input" required>
          <label for="gdpr_consent" class="form-check-label small">
            Ho letto e accetto il trattamento dei miei dati personali <span class="text-danger">*</span>
          </label>
        </div>
      </div>

      <div class="d-flex gap-2 mt-4">
        <a href="<?= $_SERVER['PHP_SELF'] ?>?step=2" class="btn btn-outline-secondary">
          <i class="bi bi-arrow-left me-1"></i>Indietro
        </a>
        <button type="submit" class="btn btn-success px-4">
          <i class="bi bi-download me-2"></i>Installa ProETS
        </button>
      </div>
    </form>

    <?php /* ════════════════════════════════════════
       STEP 4 — COMPLETAMENTO
       ════════════════════════════════════════ */ elseif ($step === 4):
    $log       = $iData['log']       ?? [];
    $installOk = $iData['installOk'] ?? false;
    $adminUser  = $iData['adminUser']  ?? '';
    $adminEmail = $iData['adminEmail'] ?? '';
    $appUrl     = $iData['appUrl']     ?? '/';
    $ragSoc     = $iData['ragSoc']     ?? '';
    ?>

    <!-- Log installazione -->
    <h5 class="mb-3 fw-bold">
      <i class="bi bi-<?= $installOk ? 'check-circle-fill text-success' : 'x-circle-fill text-danger' ?> me-2"></i>
      <?= $installOk ? 'Installazione Completata!' : 'Installazione con Errori' ?>
    </h5>

    <div class="card border-0 bg-light mb-4 p-3" style="font-size:.88rem">
      <?php foreach ($log as $row): ?>
      <div class="log-row">
        <i class="bi bi-<?= $row['ok'] ? 'check-circle-fill text-success' : 'x-circle-fill text-danger' ?> flex-shrink-0 mt-1" style="font-size:1rem"></i>
        <span><?= $row['msg'] ?></span>
      </div>
      <?php endforeach; ?>
    </div>

    <?php if ($installOk): ?>
    <!-- Riepilogo credenziali -->
    <div class="row g-3 mb-4">
      <div class="col-md-6">
        <div class="card border-primary h-100">
          <div class="card-header bg-primary text-white fw-bold py-2">
            <i class="bi bi-person-circle me-2"></i>Credenziali Accesso
          </div>
          <div class="card-body small">
            <div class="mb-2"><strong>URL:</strong><br>
              <a href="<?= e($appUrl) ?>"><?= e($appUrl) ?></a>
            </div>
            <div class="mb-2"><strong>Username:</strong> <code><?= e($adminUser) ?></code></div>
            <div><strong>Email:</strong> <?= e($adminEmail) ?></div>
            <div class="alert alert-warning py-1 mt-2 mb-0 small">
              <i class="bi bi-key me-1"></i>Conserva queste credenziali in un posto sicuro.
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-6">
        <div class="card border-warning h-100">
          <div class="card-header bg-warning fw-bold py-2">
            <i class="bi bi-shield-exclamation me-2"></i>Sicurezza Post-Installazione
          </div>
          <div class="card-body small">
            <ul class="mb-0 ps-3">
              <li>Elimina o rinomina <code>install.php</code> dalla root</li>
              <li>Verifica che <code>proets/install/</code> non sia accessibile via web</li>
              <li>Verifica che <code>proets/config/config.php</code> non sia leggibile via web</li>
              <li>Configura HTTPS (Let's Encrypt / certificato SSL)</li>
              <li>Cambia la password admin al primo accesso</li>
            </ul>
          </div>
        </div>
      </div>
    </div>

    <!-- Istruzioni Apache -->
    <div class="card mb-4">
      <div class="card-header fw-bold"><i class="bi bi-server me-2 text-primary"></i>Configurazione Apache</div>
      <div class="card-body small">
        <p class="mb-2">Il file <code>.htaccess</code> nella root instrada tutto il traffico verso l'applicazione.
        Assicurati che Apache abbia <code>AllowOverride All</code> e <code>mod_rewrite</code> abilitato.</p>
        <p class="mb-1"><strong>Verifica / abilita mod_rewrite:</strong></p>
        <pre class="bg-dark text-light p-2 rounded" style="font-size:.78rem">sudo a2enmod rewrite
sudo systemctl reload apache2</pre>
        <p class="mb-1 mt-2"><strong>Nel VirtualHost (consigliato):</strong></p>
        <pre class="bg-dark text-light p-2 rounded" style="font-size:.78rem">&lt;Directory /var/www/html&gt;
    AllowOverride All
&lt;/Directory&gt;</pre>
        <p class="mb-0 mt-2"><strong>Oppure (ottimale) punta il DocumentRoot direttamente a proets/public/:</strong></p>
        <pre class="bg-dark text-light p-2 rounded mb-0" style="font-size:.78rem">DocumentRoot /var/www/html/proets/public</pre>
      </div>
    </div>

    <!-- Pulsanti azione -->
    <div class="d-flex gap-3 flex-wrap">
      <a href="<?= e($appUrl) ?>" class="btn btn-primary btn-lg">
        <i class="bi bi-box-arrow-in-right me-2"></i>Accedi a ProETS
      </a>
      <form method="post" onsubmit="return confirm('Eliminare definitivamente install.php? Azione irreversibile.')">
        <input type="hidden" name="action" value="self_delete">
        <button type="submit" class="btn btn-outline-danger btn-lg">
          <i class="bi bi-trash3 me-2"></i>Elimina install.php
        </button>
      </form>
    </div>

    <?php else: ?>
    <!-- Errore installazione -->
    <div class="alert alert-danger">
      <i class="bi bi-exclamation-triangle-fill me-2"></i>
      <strong>Installazione non riuscita.</strong> Correggi gli errori e riprova.
    </div>
    <a href="<?= $_SERVER['PHP_SELF'] ?>?step=3" class="btn btn-warning">
      <i class="bi bi-arrow-left me-2"></i>Torna al passo precedente
    </a>
    <?php endif; ?>

    <?php endif; /* fine switch step */ ?>

  </div><!-- /inst-body -->

  <div class="foot">
    ProETS v1.0.0 &mdash; <a href="https://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU GPL v3</a>
  </div>
</div><!-- /inst-wrap -->

<script>
/* Toggle modalità installazione */
function toggleInstallMode(mode) {
  const shared    = document.getElementById('section-shared');
  const dedicated = document.getElementById('section-dedicated');
  const btnLabel  = document.getElementById('btn-db-label');
  if (mode === 'shared') {
    shared.style.display    = '';
    dedicated.style.display = 'none';
    btnLabel.textContent    = 'Verifica Connessione';
  } else {
    shared.style.display    = 'none';
    dedicated.style.display = '';
    btnLabel.textContent    = 'Crea Database e Utente';
  }
}
// Inizializza stato corretto al caricamento
document.addEventListener('DOMContentLoaded', function() {
  const checked = document.querySelector('input[name="install_mode"]:checked');
  if (checked) toggleInstallMode(checked.value);
});

/* Toggle visibilità password */
function togglePw(id, btn) {
  const inp = document.getElementById(id);
  if (!inp) return;
  inp.type = inp.type === 'password' ? 'text' : 'password';
  btn.innerHTML = '<i class="bi bi-' + (inp.type === 'text' ? 'eye-slash' : 'eye') + '"></i>';
}

/* Indicatore forza password */
function checkPwStrength(inp, barId, lblId) {
  const v = inp.value;
  let score = 0;
  if (v.length >= 8)  score++;
  if (v.length >= 12) score++;
  if (/[A-Z]/.test(v)) score++;
  if (/[0-9]/.test(v)) score++;
  if (/[^A-Za-z0-9]/.test(v)) score++;

  const bar = document.getElementById(barId);
  const lbl = document.getElementById(lblId);
  const pct  = [0, 20, 40, 65, 85, 100][score] + '%';
  const cols  = ['#ef4444','#ef4444','#f97316','#eab308','#22c55e','#16a34a'];
  const lbls  = ['','Molto debole','Debole','Discreta','Buona','Ottima'];

  bar.style.width      = pct;
  bar.style.background = cols[score];
  if (lbl) lbl.textContent = v.length ? lbls[score] : 'Minimo 8 caratteri.';
}

/* Controllo corrispondenza password */
function checkMatch(id1, id2, lblId) {
  const v1 = document.getElementById(id1)?.value;
  const v2 = document.getElementById(id2)?.value;
  const lbl = document.getElementById(lblId);
  if (!lbl) return;
  if (!v2) { lbl.textContent = ''; return; }
  if (v1 === v2) {
    lbl.innerHTML = '<span class="text-success"><i class="bi bi-check-lg"></i> Le password coincidono</span>';
  } else {
    lbl.innerHTML = '<span class="text-danger"><i class="bi bi-x-lg"></i> Le password non coincidono</span>';
  }
}
</script>
</body>
</html>
