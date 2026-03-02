<?php
use ProETS\Core\Auth;
$_basePath   = \ProETS\Core\Config::get('app.base_path', '');
$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
if ($_basePath !== '' && str_starts_with($currentPath, $_basePath)) {
    $currentPath = substr($currentPath, strlen($_basePath)) ?: '/';
}
$isActive = fn(string $prefix) => str_starts_with($currentPath, $prefix) ? 'active' : '';

$user    = Auth::user();
$company = Auth::company();
$companies = [];
if ($user && $user['ruolo'] === 'superadmin') {
    $companies = \ProETS\Core\Database::fetchAll("SELECT id, ragione_sociale FROM companies WHERE attivo = 1 ORDER BY ragione_sociale");
} elseif ($user) {
    $companies = \ProETS\Core\Database::fetchAll(
        "SELECT c.id, c.ragione_sociale FROM companies c JOIN user_companies uc ON uc.company_id = c.id WHERE uc.user_id = ? AND c.attivo = 1 ORDER BY c.ragione_sociale",
        [$user['id']]
    );
}
$esercizio = $company['esercizio_corrente'] ?? date('Y');
?>
<nav id="sidebar">
  <!-- Brand -->
  <div class="sidebar-brand">
    <div class="brand-name"><i class="bi bi-building-check me-2" style="color:#60a5fa"></i>ProETS</div>
    <div class="brand-sub">Gestionale per Associazioni ETS</div>
  </div>

  <!-- Company switcher -->
  <?php if (count($companies) > 1): ?>
  <div class="company-switcher">
    <div style="font-size:.67rem;color:rgba(255,255,255,.4);text-transform:uppercase;letter-spacing:.06em;margin-bottom:.25rem;">Associazione</div>
    <select id="company-select" title="Seleziona associazione">
      <?php foreach ($companies as $c): ?>
      <option value="<?= (int)$c['id'] ?>" <?= $c['id'] == ($company['id'] ?? 0) ? 'selected' : '' ?>>
        <?= htmlspecialchars($c['ragione_sociale']) ?>
      </option>
      <?php endforeach; ?>
    </select>
    <form id="company-switch-form" method="post" action="/dashboard/switch-company" style="display:none">
      <input type="hidden" name="_csrf" value="<?= \ProETS\Core\Session::csrf() ?>">
      <input type="hidden" name="company_id" value="">
    </form>
  </div>
  <?php elseif ($company): ?>
  <div class="px-3 py-2" style="font-size:.78rem;color:rgba(255,255,255,.6);">
    <i class="bi bi-house-heart me-1" style="color:#60a5fa"></i>
    <?= htmlspecialchars($company['ragione_sociale'] ?? '') ?>
  </div>
  <?php endif; ?>

  <!-- Navigation -->
  <ul class="nav flex-column mt-2 flex-grow-1">
    <!-- Dashboard -->
    <div class="nav-section">Principale</div>
    <li class="nav-item">
      <a href="/dashboard" class="nav-link <?= $currentPath === '/dashboard' || $currentPath === '/' ? 'active' : '' ?>">
        <i class="bi bi-grid-1x2-fill"></i> Dashboard
      </a>
    </li>

    <!-- Prima Nota -->
    <div class="nav-section">Contabilità</div>
    <li class="nav-item">
      <a href="/prima-nota" class="nav-link <?= $isActive('/prima-nota') ?>">
        <i class="bi bi-journal-text"></i> Prima Nota
      </a>
    </li>
    <li class="nav-item">
      <a href="/prima-nota/saldi" class="nav-link <?= $isActive('/prima-nota/saldi') ?>">
        <i class="bi bi-wallet2"></i> Saldi Conti
      </a>
    </li>
    <li class="nav-item">
      <a href="/prima-nota/riconciliazione" class="nav-link <?= $isActive('/prima-nota/riconciliazione') ?>">
        <i class="bi bi-arrow-left-right"></i> Riconciliazione
      </a>
    </li>

    <!-- Rendiconto -->
    <div class="nav-section">Rendiconto ETS</div>
    <li class="nav-item">
      <a href="/rendiconto/annuale" class="nav-link <?= $isActive('/rendiconto/annuale') ?>">
        <i class="bi bi-file-earmark-bar-graph"></i> Rendiconto Annuale
      </a>
    </li>
    <li class="nav-item">
      <a href="/rendiconto/scostamenti" class="nav-link <?= $isActive('/rendiconto/scostamenti') ?>">
        <i class="bi bi-bar-chart-steps"></i> Scostamenti
      </a>
    </li>
    <li class="nav-item">
      <a href="/rendiconto/sintetico" class="nav-link <?= $isActive('/rendiconto/sintetico') ?>">
        <i class="bi bi-file-earmark-zip"></i> Sintetico
      </a>
    </li>
    <li class="nav-item">
      <a href="/rendiconto/test-ets" class="nav-link <?= $isActive('/rendiconto/test-ets') ?>">
        <i class="bi bi-shield-check"></i> Test Secondarietà
      </a>
    </li>
    <li class="nav-item">
      <a href="/budget" class="nav-link <?= $isActive('/budget') ?>">
        <i class="bi bi-calculator"></i> Preventivo / Budget
      </a>
    </li>

    <!-- Soci -->
    <div class="nav-section">Soci</div>
    <li class="nav-item">
      <a href="/soci" class="nav-link <?= $isActive('/soci') ?>">
        <i class="bi bi-people-fill"></i> Anagrafica Soci
      </a>
    </li>
    <li class="nav-item">
      <a href="/soci/quote" class="nav-link <?= $isActive('/soci/quote') ?>">
        <i class="bi bi-credit-card-2-front"></i> Quote
      </a>
    </li>
    <li class="nav-item">
      <a href="/soci/comunicazioni" class="nav-link <?= $isActive('/soci/comunicazioni') ?>">
        <i class="bi bi-envelope-at"></i> Comunicazioni
      </a>
    </li>

    <!-- Strumenti -->
    <div class="nav-section">Strumenti</div>
    <li class="nav-item">
      <a href="/prima-nota/import" class="nav-link <?= $isActive('/prima-nota/import') ?>">
        <i class="bi bi-cloud-upload"></i> Import Movimenti
      </a>
    </li>
    <li class="nav-item">
      <a href="/backup" class="nav-link <?= $isActive('/backup') ?>">
        <i class="bi bi-hdd-rack"></i> Backup & Ripristino
      </a>
    </li>

    <!-- Configurazione (admin) -->
    <?php if (Auth::can('config')): ?>
    <div class="nav-section">Impostazioni</div>
    <li class="nav-item">
      <a href="/configurazione/azienda" class="nav-link <?= $isActive('/configurazione') ?>">
        <i class="bi bi-gear-wide-connected"></i> Configurazione
      </a>
    </li>
    <?php endif; ?>
  </ul>

  <div class="sidebar-footer">
    <i class="bi bi-person me-1"></i>
    <?= htmlspecialchars(($user['nome'] ?? '') . ' ' . ($user['cognome'] ?? '')) ?>
    <span class="badge bg-secondary ms-1" style="font-size:.6rem;"><?= htmlspecialchars($user['ruolo'] ?? '') ?></span>
    <br>Esercizio: <strong style="color:#93c5fd"><?= htmlspecialchars((string)$esercizio) ?></strong>
  </div>
</nav>
