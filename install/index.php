<?php
/**
 * ProETS - Installer Web
 * Copyright (C) 2025 ProETS - Scapuzzi Alessandro <dado70@gmail.com>
 * https://www.proets.it | License: GNU GPL v3
 */
declare(strict_types=1);

define('PROETS_INSTALLER', true);
define('MIN_PHP', '8.1.0');
define('MIN_MYSQL', '8.0.0');

session_start();

$step = (int)($_GET['step'] ?? $_SESSION['install_step'] ?? 1);
$errors = [];
$success = [];

// --- Helper functions ---
function checkRequirements(): array {
    $checks = [];
    $checks[] = ['name' => 'PHP >= '.MIN_PHP, 'ok' => version_compare(PHP_VERSION, MIN_PHP, '>='), 'val' => PHP_VERSION];
    $checks[] = ['name' => 'Estensione PDO', 'ok' => extension_loaded('pdo'), 'val' => extension_loaded('pdo') ? 'OK' : 'Mancante'];
    $checks[] = ['name' => 'Estensione PDO MySQL', 'ok' => extension_loaded('pdo_mysql'), 'val' => extension_loaded('pdo_mysql') ? 'OK' : 'Mancante'];
    $checks[] = ['name' => 'Estensione mbstring', 'ok' => extension_loaded('mbstring'), 'val' => extension_loaded('mbstring') ? 'OK' : 'Mancante'];
    $checks[] = ['name' => 'Estensione openssl', 'ok' => extension_loaded('openssl'), 'val' => extension_loaded('openssl') ? 'OK' : 'Mancante'];
    $checks[] = ['name' => 'Estensione json', 'ok' => extension_loaded('json'), 'val' => extension_loaded('json') ? 'OK' : 'Mancante'];
    $checks[] = ['name' => 'Estensione curl', 'ok' => extension_loaded('curl'), 'val' => extension_loaded('curl') ? 'OK' : 'Mancante'];
    $checks[] = ['name' => 'Estensione zip', 'ok' => extension_loaded('zip'), 'val' => extension_loaded('zip') ? 'OK' : 'Mancante'];
    $checks[] = ['name' => 'Cartella config scrivibile', 'ok' => is_writable(dirname(__DIR__).'/config'), 'val' => is_writable(dirname(__DIR__).'/config') ? 'OK' : 'Non scrivibile'];
    $checks[] = ['name' => 'Cartella uploads scrivibile', 'ok' => is_writable(dirname(__DIR__).'/uploads'), 'val' => is_writable(dirname(__DIR__).'/uploads') ? 'OK' : 'Non scrivibile'];
    $checks[] = ['name' => 'Cartella backups scrivibile', 'ok' => is_writable(dirname(__DIR__).'/backups'), 'val' => is_writable(dirname(__DIR__).'/backups') ? 'OK' : 'Non scrivibile'];
    $checks[] = ['name' => 'Cartella logs scrivibile', 'ok' => is_writable(dirname(__DIR__).'/logs'), 'val' => is_writable(dirname(__DIR__).'/logs') ? 'OK' : 'Non scrivibile'];
    return $checks;
}

function allChecksPassed(array $checks): bool {
    foreach ($checks as $c) if (!$c['ok']) return false;
    return true;
}

// --- Step processing ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Step 2: Test DB connection
    if ($step === 2) {
        $dbHost = trim($_POST['db_host'] ?? 'localhost');
        $dbPort = (int)($_POST['db_port'] ?? 3306);
        $dbName = trim($_POST['db_name'] ?? '');
        $dbUser = trim($_POST['db_user'] ?? '');
        $dbPass  = $_POST['db_pass'] ?? '';
        $dbCharset = 'utf8mb4';

        if (empty($dbName) || empty($dbUser)) {
            $errors[] = 'Nome database e utente obbligatori.';
        } else {
            try {
                $dsn = "mysql:host={$dbHost};port={$dbPort};charset={$dbCharset}";
                $pdo = new PDO($dsn, $dbUser, $dbPass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
                // Check MySQL version
                $ver = $pdo->query("SELECT VERSION()")->fetchColumn();
                if (version_compare($ver, MIN_MYSQL, '<')) {
                    $errors[] = "MySQL {$ver} non supportato. Richiesto >= ".MIN_MYSQL;
                } else {
                    // Try to create/select database
                    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                    $pdo->exec("USE `{$dbName}`");
                    $_SESSION['db_config'] = compact('dbHost','dbPort','dbName','dbUser','dbPass','dbCharset');
                    $_SESSION['install_step'] = 3;
                    header('Location: ?step=3'); exit;
                }
            } catch (PDOException $e) {
                $errors[] = 'Connessione fallita: '.$e->getMessage();
            }
        }
    }

    // Step 3: Install DB and create admin
    if ($step === 3) {
        $adminUser = trim($_POST['admin_user'] ?? '');
        $adminEmail = trim($_POST['admin_email'] ?? '');
        $adminPass = $_POST['admin_pass'] ?? '';
        $adminPass2 = $_POST['admin_pass2'] ?? '';
        $adminNome = trim($_POST['admin_nome'] ?? '');
        $adminCognome = trim($_POST['admin_cognome'] ?? '');
        $gdprConsent = !empty($_POST['gdpr_consent']);

        if (!$gdprConsent) $errors[] = 'Devi accettare l\'informativa privacy per procedere.';
        if (empty($adminUser)) $errors[] = 'Username obbligatorio.';
        if (!filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email non valida.';
        if (strlen($adminPass) < 8) $errors[] = 'La password deve essere di almeno 8 caratteri.';
        if ($adminPass !== $adminPass2) $errors[] = 'Le password non coincidono.';

        if (empty($errors)) {
            try {
                $cfg = $_SESSION['db_config'];
                $dsn = "mysql:host={$cfg['dbHost']};port={$cfg['dbPort']};dbname={$cfg['dbName']};charset={$cfg['dbCharset']}";
                $pdo = new PDO($dsn, $cfg['dbUser'], $cfg['dbPass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

                // Execute SQL schema
                $sql = file_get_contents(__DIR__.'/database.sql');
                // Split by statements
                foreach (explodeSql($sql) as $stmt) {
                    $stmt = trim($stmt);
                    if ($stmt) $pdo->exec($stmt);
                }

                // Create superadmin
                $hash = password_hash($adminPass, PASSWORD_BCRYPT, ['cost' => 12]);
                $stmt = $pdo->prepare("INSERT INTO users (username,email,password,nome,cognome,ruolo,attivo,email_verificata,gdpr_consenso,gdpr_data) VALUES (?,?,?,?,?,'superadmin',1,1,1,NOW())");
                $stmt->execute([$adminUser, $adminEmail, $hash, $adminNome, $adminCognome]);

                // Generate config file
                $appKey = bin2hex(random_bytes(32));
                $configContent = generateConfig($cfg, $appKey);
                file_put_contents(dirname(__DIR__).'/config/config.php', $configContent);

                // Create install lock
                file_put_contents(__DIR__.'/installed.lock', date('Y-m-d H:i:s'));

                $_SESSION['install_step'] = 4;
                header('Location: ?step=4'); exit;
            } catch (Throwable $e) {
                $errors[] = 'Installazione fallita: '.$e->getMessage();
            }
        }
    }
}

function explodeSql(string $sql): array {
    // Remove comments and split
    $sql = preg_replace('/--[^\n]*\n/', "\n", $sql);
    $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);
    return array_filter(explode(';', $sql), fn($s) => trim($s) !== '');
}

function generateConfig(array $cfg, string $appKey): string {
    $host = addslashes($cfg['dbHost']);
    $port = (int)$cfg['dbPort'];
    $name = addslashes($cfg['dbName']);
    $user = addslashes($cfg['dbUser']);
    $pass = addslashes($cfg['dbPass']);
    return "<?php
/**
 * ProETS - Configurazione
 * Generato automaticamente dall'installer il ".date('d/m/Y H:i:s')."
 * Non modificare manualmente se non strettamente necessario.
 */
define('PROETS_CONFIGURED', true);

return [
    'app' => [
        'name'     => 'ProETS',
        'version'  => '1.0.0',
        'key'      => '{$appKey}',
        'debug'    => false,
        'timezone' => 'Europe/Rome',
        'locale'   => 'it_IT',
        'url'      => '',
    ],
    'db' => [
        'host'    => '{$host}',
        'port'    => {$port},
        'name'    => '{$name}',
        'user'    => '{$user}',
        'pass'    => '{$pass}',
        'charset' => 'utf8mb4',
    ],
    'session' => [
        'lifetime'      => 7200,
        'secure'        => false,
        'httponly'      => true,
        'samesite'      => 'Strict',
    ],
    'gdpr' => [
        'data_retention_years' => 10,
        'dpo_email'            => '',
        'cookie_lifetime'      => 365,
    ],
    'backup' => [
        'local_path' => __DIR__.'/../backups/',
        'max_local'  => 7,
    ],
];
";
}

// --- Check if already installed ---
if (file_exists(__DIR__.'/installed.lock') && $step < 4) {
    header('Location: ../index.php'); exit;
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>ProETS - Installazione</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
  body{background:#f0f2f5;font-family:'Segoe UI',system-ui,sans-serif;}
  .install-card{max-width:760px;margin:40px auto;border:none;border-radius:16px;box-shadow:0 4px 32px rgba(0,0,0,.12);}
  .install-header{background:linear-gradient(135deg,#1a3a5c 0%,#2563eb 100%);color:#fff;border-radius:16px 16px 0 0;padding:2rem;}
  .step-indicator{display:flex;gap:0;margin-top:1.5rem;}
  .step-dot{flex:1;text-align:center;padding:.4rem;font-size:.8rem;opacity:.5;border-bottom:3px solid rgba(255,255,255,.3);}
  .step-dot.active{opacity:1;border-bottom-color:#fff;font-weight:700;}
  .step-dot.done{opacity:.8;border-bottom-color:rgba(255,255,255,.6);}
  .check-row{display:flex;align-items:center;padding:.4rem 0;border-bottom:1px solid #f0f0f0;}
  .check-row .icon{width:28px;height:28px;display:flex;align-items:center;justify-content:center;border-radius:50%;font-size:.9rem;}
  .icon-ok{background:#d1fae5;color:#065f46;}
  .icon-err{background:#fee2e2;color:#991b1b;}
  .logo-text{font-size:2rem;font-weight:800;letter-spacing:-1px;}
  .logo-sub{opacity:.7;font-size:.9rem;}
</style>
</head>
<body>
<div class="install-card card">
  <div class="install-header">
    <div class="logo-text"><i class="bi bi-building-check me-2"></i>ProETS</div>
    <div class="logo-sub">Sistema Gestionale per Associazioni di Promozione Sociale</div>
    <div class="step-indicator">
      <?php
      $steps = ['Requisiti','Database','Amministratore','Completato'];
      foreach ($steps as $i => $lbl):
        $cls = $i+1 == $step ? 'active' : ($i+1 < $step ? 'done' : '');
      ?>
      <div class="step-dot <?= $cls ?>">
        <?php if($i+1 < $step): ?><i class="bi bi-check-lg"></i> <?php endif; ?>
        <?= $lbl ?>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <div class="card-body p-4">
    <?php foreach ($errors as $e): ?>
    <div class="alert alert-danger py-2"><i class="bi bi-exclamation-triangle me-2"></i><?= htmlspecialchars($e) ?></div>
    <?php endforeach; ?>

    <?php if ($step === 1): ?>
    <!-- STEP 1: Requisiti -->
    <h5 class="mb-3"><i class="bi bi-clipboard-check me-2 text-primary"></i>Verifica Requisiti di Sistema</h5>
    <?php $checks = checkRequirements(); $allOk = allChecksPassed($checks); ?>
    <?php foreach ($checks as $c): ?>
    <div class="check-row">
      <div class="icon <?= $c['ok'] ? 'icon-ok' : 'icon-err' ?> me-3">
        <i class="bi bi-<?= $c['ok'] ? 'check-lg' : 'x-lg' ?>"></i>
      </div>
      <div class="flex-grow-1"><?= htmlspecialchars($c['name']) ?></div>
      <div class="text-muted small"><?= htmlspecialchars($c['val']) ?></div>
    </div>
    <?php endforeach; ?>
    <div class="mt-4">
      <?php if ($allOk): ?>
      <div class="alert alert-success py-2"><i class="bi bi-check-circle me-2"></i>Tutti i requisiti sono soddisfatti.</div>
      <a href="?step=2" class="btn btn-primary"><i class="bi bi-arrow-right me-2"></i>Continua</a>
      <?php else: ?>
      <div class="alert alert-danger py-2"><i class="bi bi-exclamation-triangle me-2"></i>Risolvi i requisiti mancanti prima di procedere.</div>
      <a href="?step=1" class="btn btn-outline-secondary"><i class="bi bi-arrow-clockwise me-2"></i>Ricontrolla</a>
      <?php endif; ?>
    </div>

    <?php elseif ($step === 2): ?>
    <!-- STEP 2: Database -->
    <h5 class="mb-3"><i class="bi bi-database me-2 text-primary"></i>Configurazione Database MySQL</h5>
    <form method="post" action="?step=2">
      <div class="row g-3">
        <div class="col-md-8">
          <label class="form-label fw-semibold">Host Database</label>
          <input type="text" name="db_host" class="form-control" value="<?= htmlspecialchars($_POST['db_host'] ?? 'localhost') ?>" required>
        </div>
        <div class="col-md-4">
          <label class="form-label fw-semibold">Porta</label>
          <input type="number" name="db_port" class="form-control" value="<?= htmlspecialchars($_POST['db_port'] ?? '3306') ?>" required>
        </div>
        <div class="col-12">
          <label class="form-label fw-semibold">Nome Database</label>
          <input type="text" name="db_name" class="form-control" value="<?= htmlspecialchars($_POST['db_name'] ?? 'proets') ?>" required placeholder="proets">
          <div class="form-text">Il database verrà creato automaticamente se non esiste.</div>
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">Utente MySQL</label>
          <input type="text" name="db_user" class="form-control" value="<?= htmlspecialchars($_POST['db_user'] ?? '') ?>" required>
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">Password MySQL</label>
          <input type="password" name="db_pass" class="form-control" value="">
        </div>
      </div>
      <div class="mt-4 d-flex gap-2">
        <a href="?step=1" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Indietro</a>
        <button type="submit" class="btn btn-primary"><i class="bi bi-plug me-2"></i>Testa Connessione</button>
      </div>
    </form>

    <?php elseif ($step === 3): ?>
    <!-- STEP 3: Admin + Installazione -->
    <h5 class="mb-3"><i class="bi bi-person-gear me-2 text-primary"></i>Creazione Amministratore</h5>
    <div class="alert alert-info py-2 small"><i class="bi bi-info-circle me-2"></i>Verrà installato il database e creato l'utente amministratore principale.</div>
    <form method="post" action="?step=3">
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label fw-semibold">Nome</label>
          <input type="text" name="admin_nome" class="form-control" value="Alessandro" required>
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">Cognome</label>
          <input type="text" name="admin_cognome" class="form-control" value="Scapuzzi" required>
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">Username</label>
          <input type="text" name="admin_user" class="form-control" value="admin" required>
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">Email</label>
          <input type="email" name="admin_email" class="form-control" value="dado70@gmail.com" required>
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">Password <span class="text-danger">*</span></label>
          <input type="password" name="admin_pass" class="form-control" required minlength="8">
          <div class="form-text">Minimo 8 caratteri.</div>
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">Conferma Password</label>
          <input type="password" name="admin_pass2" class="form-control" required>
        </div>
        <div class="col-12">
          <div class="card bg-light border-0 p-3">
            <p class="small mb-2 fw-semibold">Informativa Privacy (GDPR - Regolamento UE 2016/679)</p>
            <p class="small text-muted mb-2">I dati inseriti sono trattati esclusivamente per la gestione del sistema ProETS. Il titolare del trattamento è l'utente che installa il software. I dati non vengono trasmessi a terzi.</p>
            <div class="form-check">
              <input type="checkbox" name="gdpr_consent" id="gdpr_consent" class="form-check-input" required>
              <label for="gdpr_consent" class="form-check-label small">Ho letto e accetto il trattamento dei miei dati personali</label>
            </div>
          </div>
        </div>
      </div>
      <div class="mt-4 d-flex gap-2">
        <a href="?step=2" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Indietro</a>
        <button type="submit" class="btn btn-success"><i class="bi bi-download me-2"></i>Installa ProETS</button>
      </div>
    </form>

    <?php elseif ($step === 4): ?>
    <!-- STEP 4: Completato -->
    <div class="text-center py-4">
      <div class="display-1 text-success mb-3"><i class="bi bi-check-circle-fill"></i></div>
      <h4 class="fw-bold mb-2">Installazione Completata!</h4>
      <p class="text-muted mb-4">ProETS è stato installato con successo. Per motivi di sicurezza rinomina o elimina la cartella <code>install/</code>.</p>
      <div class="alert alert-warning py-2 text-start small">
        <i class="bi bi-shield-exclamation me-2"></i>
        <strong>Importante:</strong> Rimuovi o proteggi la cartella <code>install/</code> dopo aver completato l'installazione.
      </div>
      <a href="../index.php" class="btn btn-primary btn-lg mt-2">
        <i class="bi bi-box-arrow-in-right me-2"></i>Accedi a ProETS
      </a>
    </div>
    <?php endif; ?>
  </div>
  <div class="card-footer text-center text-muted small py-2">
    ProETS v1.0.0 &mdash; <a href="https://www.proets.it" target="_blank">www.proets.it</a> &mdash;
    <a href="https://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU GPL v3</a>
  </div>
</div>
</body>
</html>
