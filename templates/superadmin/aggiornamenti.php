<?php
$e = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');

// Confronto versioni
$appVer    = $currentVersion ?? '0.0.0';
$dbVer     = $dbVersion ?? null;
$latestTag = $latestRelease['tag_name'] ?? null;
// Rimuovi prefisso 'v' dal tag GitHub (es. v1.1.0 → 1.1.0)
$latestClean = $latestTag ? ltrim($latestTag, 'v') : null;
$hasUpdate   = $latestClean && version_compare($latestClean, $appVer, '>');
$dbInSync    = ($dbVer === null || $dbVer === $appVer || empty($pending));
?>

<!-- Flash messages -->
<?php if ($flash = \ProETS\Core\Session::flash('success')): ?>
<div class="alert alert-success alert-dismissible py-2 mb-3" role="alert">
  <i class="bi bi-check-circle me-2"></i><?= $flash ?>
  <button type="button" class="btn-close py-2" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>
<?php if ($flash = \ProETS\Core\Session::flash('error')): ?>
<div class="alert alert-danger alert-dismissible py-2 mb-3" role="alert">
  <i class="bi bi-exclamation-triangle me-2"></i><?= $e($flash) ?>
  <button type="button" class="btn-close py-2" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>
<?php if ($flash = \ProETS\Core\Session::flash('info')): ?>
<div class="alert alert-info alert-dismissible py-2 mb-3" role="alert">
  <i class="bi bi-info-circle me-2"></i><?= $e($flash) ?>
  <button type="button" class="btn-close py-2" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Header -->
<div class="d-flex align-items-center justify-content-between mb-4">
  <div>
    <h4 class="mb-0 fw-bold"><i class="bi bi-arrow-repeat me-2 text-primary"></i>Sistema — Aggiornamenti</h4>
    <div class="text-muted small">Versioni e migrazioni database</div>
  </div>
  <a href="/superadmin" class="btn btn-outline-secondary btn-sm">
    <i class="bi bi-arrow-left me-1"></i>Pannello Superadmin
  </a>
</div>

<!-- Riquadri versione -->
<div class="row g-3 mb-4">
  <!-- Versione applicazione -->
  <div class="col-md-4">
    <div class="card border-0 bg-primary bg-opacity-10 h-100">
      <div class="card-body py-3">
        <div class="d-flex align-items-center gap-3">
          <i class="bi bi-code-square fs-2 text-primary"></i>
          <div>
            <div class="small text-muted">Versione applicazione</div>
            <div class="fs-5 fw-bold font-monospace"><?= $e($appVer) ?></div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Versione DB -->
  <div class="col-md-4">
    <?php $dbOk = empty($pending); ?>
    <div class="card border-0 bg-<?= $dbOk ? 'success' : 'warning' ?> bg-opacity-10 h-100">
      <div class="card-body py-3">
        <div class="d-flex align-items-center gap-3">
          <i class="bi bi-database fs-2 text-<?= $dbOk ? 'success' : 'warning' ?>"></i>
          <div>
            <div class="small text-muted">Versione database</div>
            <?php if ($dbVer): ?>
            <div class="fs-5 fw-bold font-monospace"><?= $e($dbVer) ?></div>
            <?php else: ?>
            <div class="fs-5 fw-bold text-muted">—</div>
            <?php endif; ?>
            <?php if (!$dbOk): ?>
            <div class="small text-warning fw-semibold">
              <i class="bi bi-exclamation-triangle me-1"></i><?= count($pending) ?> migrazione/i pendente/i
            </div>
            <?php else: ?>
            <div class="small text-success"><i class="bi bi-check-circle me-1"></i>In sync</div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Ultima release GitHub -->
  <div class="col-md-4">
    <div class="card border-0 bg-<?= $hasUpdate ? 'danger' : 'secondary' ?> bg-opacity-10 h-100">
      <div class="card-body py-3">
        <div class="d-flex align-items-center gap-3">
          <i class="bi bi-github fs-2 text-<?= $hasUpdate ? 'danger' : 'secondary' ?>"></i>
          <div>
            <div class="small text-muted">Ultima release (GitHub)</div>
            <?php if ($latestClean): ?>
            <div class="fs-5 fw-bold font-monospace"><?= $e($latestClean) ?></div>
            <?php if ($hasUpdate): ?>
            <div class="small text-danger fw-semibold">
              <i class="bi bi-arrow-up-circle me-1"></i>Aggiornamento disponibile
            </div>
            <?php else: ?>
            <div class="small text-success"><i class="bi bi-check-circle me-1"></i>Sei aggiornato</div>
            <?php endif; ?>
            <?php elseif ($latestRelease === null): ?>
            <div class="fs-5 fw-bold text-muted">N/D</div>
            <div class="small text-muted">GitHub non raggiungibile</div>
            <?php else: ?>
            <div class="fs-5 fw-bold text-muted">—</div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Avviso aggiornamento disponibile -->
<?php if ($hasUpdate && !empty($latestRelease['html_url'])): ?>
<div class="alert alert-warning mb-4 py-3">
  <div class="d-flex align-items-start gap-3">
    <i class="bi bi-arrow-up-circle fs-4 mt-1 text-warning"></i>
    <div>
      <strong>Aggiornamento disponibile: <?= $e($latestClean) ?></strong>
      <?php if (!empty($latestRelease['name']) && $latestRelease['name'] !== $latestTag): ?>
      — <?= $e($latestRelease['name']) ?>
      <?php endif; ?>
      <?php if (!empty($latestRelease['body'])): ?>
      <div class="small text-muted mt-1" style="white-space:pre-line"><?= $e(mb_substr($latestRelease['body'], 0, 500)) ?><?= strlen($latestRelease['body']) > 500 ? '…' : '' ?></div>
      <?php endif; ?>
      <div class="mt-2">
        <a href="<?= $e($latestRelease['html_url']) ?>" target="_blank" rel="noopener"
           class="btn btn-sm btn-warning">
          <i class="bi bi-box-arrow-up-right me-1"></i>Vai alle release su GitHub
        </a>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- Migrazioni pendenti -->
<?php if (!empty($pending)): ?>
<div class="card mb-4 border-warning">
  <div class="card-header bg-warning bg-opacity-25 fw-bold">
    <i class="bi bi-exclamation-triangle me-2 text-warning"></i>
    Migrazioni da applicare (<?= count($pending) ?>)
  </div>
  <div class="table-responsive">
    <table class="table table-sm mb-0 align-middle">
      <thead class="table-light">
        <tr>
          <th>Nome</th>
          <th>Versione</th>
          <th>Descrizione</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($pending as $name => $def): ?>
        <tr>
          <td><code class="small"><?= $e($name) ?></code></td>
          <td><span class="badge bg-secondary-subtle text-secondary"><?= $e($def['version'] ?? '—') ?></span></td>
          <td class="text-muted small"><?= $e($def['description'] ?? '—') ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <div class="card-footer bg-transparent">
    <form method="post" action="/superadmin/aggiornamenti"
          onsubmit="return confirm('Applicare tutte le migrazioni pendenti? Operazione non reversibile.')">
      <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
      <button type="submit" class="btn btn-warning btn-sm">
        <i class="bi bi-play-circle me-1"></i>Applica <?= count($pending) ?> migrazione/i
      </button>
    </form>
  </div>
</div>
<?php else: ?>
<div class="alert alert-success mb-4 py-2">
  <i class="bi bi-check-circle me-2"></i>Database aggiornato — nessuna migrazione pendente.
</div>
<?php endif; ?>

<!-- Risultati ultima esecuzione -->
<?php if ($results !== null && !empty($results)): ?>
<div class="card mb-4">
  <div class="card-header fw-bold">
    <i class="bi bi-list-check me-2"></i>Risultato migrazioni
  </div>
  <div class="table-responsive">
    <table class="table table-sm mb-0 align-middle">
      <thead class="table-light">
        <tr><th>Migrazione</th><th>Esito</th><th>Messaggio</th></tr>
      </thead>
      <tbody>
        <?php foreach ($results as $r): ?>
        <tr>
          <td><code class="small"><?= $e($r['name']) ?></code></td>
          <td>
            <?php if ($r['ok']): ?>
            <span class="badge bg-success-subtle text-success"><i class="bi bi-check-circle me-1"></i>OK</span>
            <?php else: ?>
            <span class="badge bg-danger-subtle text-danger"><i class="bi bi-x-circle me-1"></i>Errore</span>
            <?php endif; ?>
          </td>
          <td class="small text-muted"><?= $e($r['message']) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<!-- Storico migrazioni applicate -->
<div class="card">
  <div class="card-header fw-bold">
    <i class="bi bi-clock-history me-2 text-secondary"></i>Storico migrazioni applicate
  </div>
  <?php if (!empty($applied)): ?>
  <div class="table-responsive">
    <table class="table table-sm mb-0 align-middle">
      <thead class="table-light">
        <tr>
          <th>#</th>
          <th>Migrazione</th>
          <th>Versione</th>
          <th>Descrizione</th>
          <th>Applicata il</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($applied as $i => $row): ?>
        <tr>
          <td class="text-muted small"><?= $i + 1 ?></td>
          <td><code class="small"><?= $e($row['migration']) ?></code></td>
          <td><span class="badge bg-success-subtle text-success"><?= $e($row['version'] ?? '—') ?></span></td>
          <td class="text-muted small"><?= $e($row['description'] ?? '—') ?></td>
          <td class="text-muted small">
            <?php
            $ts = strtotime($row['applied_at'] ?? '');
            echo $ts ? date('d/m/Y H:i', $ts) : $e($row['applied_at'] ?? '—');
            ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php else: ?>
  <div class="card-body text-muted small">
    <i class="bi bi-info-circle me-1"></i>Nessuna migrazione ancora applicata (tabella <code>db_migrations</code> vuota o non presente).
  </div>
  <?php endif; ?>
</div>
