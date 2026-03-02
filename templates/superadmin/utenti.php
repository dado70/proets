<?php
$e = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$roleBadge = [
    'superadmin' => 'danger',
    'admin'      => 'primary',
    'operator'   => 'warning',
    'readonly'   => 'secondary',
];
$roleLabel = [
    'superadmin' => 'Super Admin',
    'admin'      => 'Amministratore',
    'operator'   => 'Operatore',
    'readonly'   => 'Solo lettura',
];
?>

<!-- Flash messages -->
<?php if ($flash = \ProETS\Core\Session::flash('success')): ?>
<div class="alert alert-success alert-dismissible py-2 mb-3" role="alert">
  <i class="bi bi-check-circle me-2"></i><?= $flash /* può contenere <strong> */ ?>
  <button type="button" class="btn-close py-2" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>
<?php if ($flash = \ProETS\Core\Session::flash('error')): ?>
<div class="alert alert-danger alert-dismissible py-2 mb-3" role="alert">
  <i class="bi bi-exclamation-triangle me-2"></i><?= $e($flash) ?>
  <button type="button" class="btn-close py-2" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Header -->
<div class="d-flex align-items-center justify-content-between mb-4">
  <div>
    <h4 class="mb-0 fw-bold"><i class="bi bi-shield-lock me-2 text-danger"></i>Pannello Superadmin — Utenti</h4>
    <div class="text-muted small">Tutti gli utenti di tutte le associazioni</div>
  </div>
  <div class="d-flex gap-2">
    <a href="/superadmin" class="btn btn-outline-secondary btn-sm">
      <i class="bi bi-building me-1"></i>Associazioni
    </a>
    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modal-nuovo-utente">
      <i class="bi bi-person-plus me-1"></i>Nuovo Utente
    </button>
  </div>
</div>

<!-- Tabella utenti -->
<div class="card">
  <div class="card-header fw-bold">
    <i class="bi bi-people me-2 text-primary"></i>Tutti gli utenti
    <span class="badge bg-secondary ms-2"><?= count($utenti) ?></span>
  </div>
  <div class="table-responsive">
    <table class="table table-sm table-hover mb-0 align-middle">
      <thead class="table-light">
        <tr>
          <th>Username</th>
          <th>Nome e Cognome</th>
          <th>Email</th>
          <th>Ruolo</th>
          <th>Associazioni</th>
          <th>Ultimo Accesso</th>
          <th class="text-center">Stato</th>
          <th class="text-center">Azioni</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($utenti as $u):
          $rCls = $roleBadge[$u['ruolo']] ?? 'secondary';
          $isMe = ($u['id'] == $currentUser['id']);
        ?>
        <tr class="<?= !$u['attivo'] ? 'text-muted' : '' ?>">
          <td class="fw-semibold"><code><?= $e($u['username']) ?></code></td>
          <td><?= $e($u['nome_completo'] ?? '') ?></td>
          <td class="small text-muted"><?= $e($u['email'] ?? '') ?></td>
          <td>
            <span class="badge bg-<?= $rCls ?>-subtle text-<?= $rCls ?>">
              <?= $roleLabel[$u['ruolo']] ?? $e($u['ruolo']) ?>
            </span>
          </td>
          <td class="small text-muted"><?= $e($u['aziende'] ?? '—') ?></td>
          <td class="small text-muted">
            <?= $u['ultimo_accesso'] ? date('d/m/Y H:i', strtotime($u['ultimo_accesso'])) : 'Mai' ?>
          </td>
          <td class="text-center">
            <span class="badge bg-<?= $u['attivo'] ? 'success' : 'secondary' ?>-subtle text-<?= $u['attivo'] ? 'success' : 'secondary' ?>">
              <?= $u['attivo'] ? 'Attivo' : 'Disabilitato' ?>
            </span>
          </td>
          <td class="text-center">
            <?php if ($isMe): ?>
            <span class="text-muted small">Tu</span>
            <?php else: ?>
            <!-- Toggle attivo/disattivo -->
            <form method="post" action="/superadmin/utenti" class="d-inline"
                  onsubmit="return confirm('<?= $u['attivo'] ? 'Disabilitare' : 'Riabilitare' ?> questo utente?')">
              <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
              <input type="hidden" name="action" value="toggle">
              <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
              <button type="submit"
                class="btn btn-sm btn-outline-<?= $u['attivo'] ? 'warning' : 'success' ?> py-0 px-1"
                title="<?= $u['attivo'] ? 'Disabilita' : 'Riabilita' ?>">
                <i class="bi bi-<?= $u['attivo'] ? 'pause-circle' : 'play-circle' ?>"></i>
              </button>
            </form>
            <!-- Reset password -->
            <form method="post" action="/superadmin/utenti" class="d-inline"
                  onsubmit="return confirm('Reimpostare la password di questo utente? La nuova password temporanea verrà mostrata nella notifica.')">
              <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
              <input type="hidden" name="action" value="reset_password">
              <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
              <button type="submit" class="btn btn-sm btn-outline-secondary py-0 px-1" title="Reset Password">
                <i class="bi bi-key"></i>
              </button>
            </form>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($utenti)): ?>
        <tr><td colspan="8" class="text-center text-muted py-3">Nessun utente trovato.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Legenda ruoli -->
<div class="row g-2 mt-3">
  <div class="col-12">
    <div class="card border-0 bg-light">
      <div class="card-body py-2">
        <div class="small fw-semibold mb-1">Gerarchia ruoli:</div>
        <div class="d-flex flex-wrap gap-2 small">
          <span><span class="badge bg-danger-subtle text-danger">Super Admin</span> — gestisce tutte le associazioni e tutti gli utenti</span>
          <span class="text-muted">›</span>
          <span><span class="badge bg-primary-subtle text-primary">Amministratore</span> — gestisce una singola associazione e i suoi utenti</span>
          <span class="text-muted">›</span>
          <span><span class="badge bg-warning-subtle text-warning">Operatore</span> — inserisce dati, non può configurare</span>
          <span class="text-muted">›</span>
          <span><span class="badge bg-secondary-subtle text-secondary">Solo lettura</span> — visualizza e scarica report</span>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Modal Nuovo Utente -->
<div class="modal fade" id="modal-nuovo-utente" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title"><i class="bi bi-person-plus me-2"></i>Nuovo Utente</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="post" action="/superadmin/utenti">
        <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
        <input type="hidden" name="action" value="crea_admin">
        <div class="modal-body row g-3">
          <div class="col-12">
            <label class="form-label">Associazione <span class="text-danger">*</span></label>
            <select name="company_id" class="form-select" required>
              <option value="">— Seleziona associazione —</option>
              <?php foreach ($companies as $c): ?>
              <option value="<?= (int)$c['id'] ?>"><?= $e($c['ragione_sociale']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label">Username <span class="text-danger">*</span></label>
            <input type="text" name="username" class="form-control" required
                   pattern="[a-z0-9._-]{3,50}" title="Lettere minuscole, numeri, punto, underscore, trattino (3-50 caratteri)">
          </div>
          <div class="col-md-6">
            <label class="form-label">Ruolo <span class="text-danger">*</span></label>
            <select name="ruolo" class="form-select" required>
              <option value="admin" selected>Amministratore</option>
              <option value="operator">Operatore</option>
              <option value="readonly">Solo lettura</option>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label">Nome</label>
            <input type="text" name="nome" class="form-control" maxlength="100">
          </div>
          <div class="col-md-6">
            <label class="form-label">Cognome</label>
            <input type="text" name="cognome" class="form-control" maxlength="100">
          </div>
          <div class="col-12">
            <label class="form-label">Email <span class="text-danger">*</span></label>
            <input type="email" name="email" class="form-control" required>
          </div>
          <div class="col-12">
            <label class="form-label">Password temporanea <span class="text-danger">*</span></label>
            <input type="password" name="password" class="form-control" required minlength="8"
                   autocomplete="new-password">
            <div class="form-text">Minimo 8 caratteri. L'utente dovrà cambiarla al primo accesso.</div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annulla</button>
          <button type="submit" class="btn btn-primary">
            <i class="bi bi-person-plus me-1"></i>Crea Utente
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
