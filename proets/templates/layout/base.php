<!DOCTYPE html>
<html lang="it" data-bs-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($pageTitle ?? 'ProETS') ?> — ProETS</title>
<meta name="robots" content="noindex,nofollow">
<!-- Bootstrap 5 -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<!-- ProETS CSS -->
<style>
:root {
  --proets-primary:   #1a3a5c;
  --proets-accent:    #2563eb;
  --proets-success:   #16a34a;
  --proets-sidebar-w: 260px;
}

/* Layout */
body { font-family: 'Segoe UI', system-ui, -apple-system, sans-serif; background: #f4f6fb; font-size: .9rem; }
#wrapper { display: flex; min-height: 100vh; }

/* Sidebar */
#sidebar {
  width: var(--proets-sidebar-w);
  min-height: 100vh;
  background: var(--proets-primary);
  color: #cbd5e1;
  flex-shrink: 0;
  display: flex;
  flex-direction: column;
  position: fixed;
  top: 0; left: 0; bottom: 0;
  z-index: 1000;
  transition: transform .3s ease;
}
#sidebar .sidebar-brand {
  padding: 1.25rem 1.25rem 1rem;
  border-bottom: 1px solid rgba(255,255,255,.1);
}
#sidebar .brand-name {
  font-size: 1.4rem; font-weight: 800; color: #fff; letter-spacing: -0.5px;
}
#sidebar .brand-sub { font-size: .7rem; color: rgba(255,255,255,.5); line-height: 1.3; }
#sidebar .nav-section {
  padding: .5rem 1rem .25rem;
  font-size: .67rem; text-transform: uppercase; letter-spacing: .1em;
  color: rgba(255,255,255,.35); font-weight: 700; margin-top: .5rem;
}
#sidebar .nav-link {
  display: flex; align-items: center; gap: .6rem;
  color: rgba(255,255,255,.7); padding: .5rem 1.25rem; border-radius: 0;
  font-size: .85rem; transition: all .15s;
}
#sidebar .nav-link i { font-size: 1rem; width: 20px; text-align: center; }
#sidebar .nav-link:hover, #sidebar .nav-link.active {
  color: #fff; background: rgba(255,255,255,.1);
}
#sidebar .nav-link.active {
  background: var(--proets-accent); border-right: 3px solid #93c5fd;
}
#sidebar .sidebar-footer {
  margin-top: auto; padding: 1rem 1.25rem;
  border-top: 1px solid rgba(255,255,255,.1);
  font-size: .75rem; color: rgba(255,255,255,.4);
}
#sidebar .company-switcher {
  background: rgba(0,0,0,.2); border-radius: 8px; padding: .6rem .75rem; margin: .5rem 1rem 0;
}
#sidebar .company-switcher select {
  background: transparent; border: none; color: #fff; width: 100%; font-size: .8rem; outline: none;
}
#sidebar .company-switcher select option { background: var(--proets-primary); color: #fff; }

/* Main content */
#main-content {
  margin-left: var(--proets-sidebar-w);
  flex: 1;
  display: flex;
  flex-direction: column;
  min-height: 100vh;
}
#topbar {
  background: #fff;
  border-bottom: 1px solid #e5e7eb;
  padding: .6rem 1.5rem;
  display: flex; align-items: center; justify-content: space-between;
  position: sticky; top: 0; z-index: 900;
  box-shadow: 0 1px 3px rgba(0,0,0,.05);
}
#topbar .page-title { font-weight: 700; font-size: 1rem; color: #1e293b; }
#topbar .topbar-right { display: flex; align-items: center; gap: 1rem; }
#topbar .user-badge {
  background: #f1f5f9; padding: .3rem .8rem; border-radius: 20px;
  font-size: .8rem; color: #475569;
}
#page-content { padding: 1.5rem; flex: 1; }

/* Cards & Tables */
.card { border: none; border-radius: 12px; box-shadow: 0 1px 8px rgba(0,0,0,.06); }
.card-header { background: #fff; border-bottom: 1px solid #f1f5f9; font-weight: 700; border-radius: 12px 12px 0 0 !important; }
.table thead th { background: #f8fafc; font-size: .8rem; text-transform: uppercase; letter-spacing: .04em; color: #64748b; font-weight: 600; border-bottom-width: 1px; }
.table-hover tbody tr:hover { background: #f0f9ff; }
.badge-causale { font-size: .7rem; padding: .25em .55em; border-radius: 4px; }

/* Stat cards */
.stat-card { border: none; border-radius: 12px; padding: 1.25rem 1.5rem; color: #fff; position: relative; overflow: hidden; }
.stat-card .stat-icon { position: absolute; right: 1rem; top: 50%; transform: translateY(-50%); font-size: 3rem; opacity: .2; }
.stat-card .stat-value { font-size: 1.6rem; font-weight: 800; }
.stat-card .stat-label { font-size: .78rem; opacity: .85; }
.stat-card.entrate  { background: linear-gradient(135deg, #16a34a, #4ade80); }
.stat-card.uscite   { background: linear-gradient(135deg, #dc2626, #f87171); }
.stat-card.saldo    { background: linear-gradient(135deg, #1a3a5c, #2563eb); }
.stat-card.soci     { background: linear-gradient(135deg, #7c3aed, #a78bfa); }

/* Form */
.form-label { font-weight: 600; color: #374151; font-size: .85rem; }
.form-control, .form-select { font-size: .875rem; }
.form-control:focus, .form-select:focus { border-color: var(--proets-accent); box-shadow: 0 0 0 .2rem rgba(37,99,235,.15); }
.btn-primary { background: var(--proets-accent); border-color: var(--proets-accent); }
.btn-primary:hover { background: #1d4ed8; border-color: #1d4ed8; }

/* Importo colorato */
.text-entrata { color: #16a34a !important; font-weight: 600; }
.text-uscita  { color: #dc2626 !important; font-weight: 600; }

/* Responsive */
@media (max-width: 992px) {
  #sidebar { transform: translateX(-100%); }
  #sidebar.show { transform: translateX(0); }
  #main-content { margin-left: 0; }
}

/* Stampa */
@media print {
  #sidebar, #topbar, .no-print { display: none !important; }
  #main-content { margin-left: 0; }
  .card { box-shadow: none; border: 1px solid #dee2e6; }
}
</style>
</head>
<body>
<div id="wrapper">
  <!-- SIDEBAR -->
  <?php include PROETS_ROOT . '/templates/layout/sidebar.php'; ?>

  <!-- MAIN -->
  <div id="main-content">
    <!-- TOPBAR -->
    <div id="topbar">
      <div class="d-flex align-items-center gap-3">
        <button class="btn btn-sm btn-light d-lg-none" id="sidebar-toggle">
          <i class="bi bi-list fs-5"></i>
        </button>
        <span class="page-title"><?= htmlspecialchars($pageTitle ?? '') ?></span>
      </div>
      <div class="topbar-right">
        <?php if ($esercizioAttivo ?? false): ?>
        <span class="badge bg-primary-subtle text-primary fw-semibold">
          <i class="bi bi-calendar-event me-1"></i>Esercizio <?= htmlspecialchars((string)($esercizioAttivo ?? '')) ?>
        </span>
        <?php endif; ?>
        <div class="user-badge">
          <i class="bi bi-person-circle me-1"></i>
          <?= htmlspecialchars((\ProETS\Core\Auth::user()['nome'] ?? '') . ' ' . (\ProETS\Core\Auth::user()['cognome'] ?? '')) ?>
        </div>
        <a href="/auth/logout" class="btn btn-sm btn-outline-secondary" title="Esci">
          <i class="bi bi-box-arrow-right"></i>
        </a>
      </div>
    </div>

    <!-- FLASH MESSAGES -->
    <div class="px-4 pt-3">
      <?php foreach (['success'=>'success','error'=>'danger','warning'=>'warning','info'=>'info'] as $type => $cls): ?>
        <?php if ($msg = \ProETS\Core\Session::flash($type)): ?>
        <div class="alert alert-<?= $cls ?> alert-dismissible fade show py-2 mb-2" role="alert">
          <i class="bi bi-<?= $type==='success'?'check-circle':($type==='error'?'exclamation-triangle':'info-circle') ?> me-2"></i>
          <?= htmlspecialchars($msg) ?>
          <button type="button" class="btn-close btn-sm" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
      <?php endforeach; ?>
    </div>

    <!-- PAGE CONTENT -->
    <div id="page-content">
      <?= $content ?? '' ?>
    </div>

    <footer class="text-center text-muted py-3 border-top bg-white mt-auto" style="font-size:.72rem;">
      ProETS v1.0.0 &mdash; <a href="https://www.proets.it" target="_blank" class="text-muted">www.proets.it</a>
      &mdash; <a href="https://www.gnu.org/licenses/gpl-3.0.html" target="_blank" class="text-muted">GNU GPL v3</a>
    </footer>
  </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Sidebar mobile toggle
document.getElementById('sidebar-toggle')?.addEventListener('click', () => {
  document.getElementById('sidebar').classList.toggle('show');
});
// Close sidebar clicking outside (mobile)
document.addEventListener('click', e => {
  const sidebar = document.getElementById('sidebar');
  const toggle = document.getElementById('sidebar-toggle');
  if (window.innerWidth < 992 && sidebar.classList.contains('show') &&
      !sidebar.contains(e.target) && e.target !== toggle) {
    sidebar.classList.remove('show');
  }
});
// Company switcher
document.getElementById('company-select')?.addEventListener('change', function() {
  const form = document.getElementById('company-switch-form');
  form.querySelector('input[name=company_id]').value = this.value;
  form.submit();
});
</script>
<?= $scripts ?? '' ?>
</body>
</html>
