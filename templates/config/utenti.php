<?php
$e = fn($v) => htmlspecialchars((string)$v);
$sezioniMenu = [
  'azienda'  => ['icon'=>'building','label'=>'Associazione'],
  'utenti'   => ['icon'=>'people','label'=>'Utenti'],
  'email'    => ['icon'=>'envelope-at','label'=>'Email SMTP'],
  'quote-annuali'=>['icon'=>'credit-card-2-front','label'=>'Quote Annuali'],
  'gdpr'     => ['icon'=>'shield-check','label'=>'GDPR & Privacy'],
];
$roleBadge = ['superadmin'=>'danger','admin'=>'primary','operator'=>'warning','readonly'=>'secondary'];
$roleLabel = ['superadmin'=>'Super Admin','admin'=>'Amministratore','operator'=>'Operatore','readonly'=>'Solo lettura'];
?>
<div class="row g-3">
  <div class="col-lg-2 col-md-3">
    <div class="card">
      <div class="card-body p-2">
        <?php foreach ($sezioniMenu as $slug => $item): ?>
        <a href="/configurazione/<?= $slug ?>" class="d-flex align-items-center gap-2 px-2 py-2 rounded text-decoration-none mb-1 <?= str_ends_with($_SERVER['REQUEST_URI']??'', $slug)?'bg-primary text-white':'text-dark' ?>">
          <i class="bi bi-<?= $item['icon'] ?>"></i><span class="small"><?= $item['label'] ?></span>
        </a>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <div class="col-lg-10 col-md-9">
    <?php if ($error ?? ''): ?><div class="alert alert-danger py-2"><?= $e($error) ?></div><?php endif; ?>

    <!-- Lista utenti -->
    <div class="card mb-4">
      <div class="card-header d-flex justify-content-between align-items-center fw-bold">
        <span><i class="bi bi-people me-2 text-primary"></i>Utenti</span>
        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modal-utente">
          <i class="bi bi-plus me-1"></i>Aggiungi Utente
        </button>
      </div>
      <div class="table-responsive">
        <table class="table table-sm mb-0">
          <thead><tr><th>Username</th><th>Nome</th><th>Email</th><th>Ruolo</th><th>Ultimo Accesso</th><th class="text-center">Stato</th><th class="text-center">Azioni</th></tr></thead>
          <tbody>
            <?php foreach ($utenti as $u):
              $rCls = $roleBadge[$u['ruolo']] ?? 'secondary'; ?>
            <tr class="<?= !$u['attivo']?'text-muted':'' ?>">
              <td class="fw-semibold"><code><?= $e($u['username']) ?></code></td>
              <td><?= $e($u['nome_completo'] ?? '') ?></td>
              <td class="small text-muted"><?= $e($u['email'] ?? '') ?></td>
              <td><span class="badge bg-<?= $rCls ?>-subtle text-<?= $rCls ?>"><?= $roleLabel[$u['ruolo']] ?? $u['ruolo'] ?></span></td>
              <td class="small text-muted"><?= $u['ultimo_accesso'] ? date('d/m/Y H:i', strtotime($u['ultimo_accesso'])) : 'Mai' ?></td>
              <td class="text-center">
                <span class="badge bg-<?= $u['attivo']?'success':'secondary' ?>-subtle text-<?= $u['attivo']?'success':'secondary' ?>"><?= $u['attivo']?'Attivo':'Disabilitato' ?></span>
              </td>
              <td class="text-center">
                <?php if ($u['id'] !== $currentUser['id']): ?>
                <form method="post" action="/configurazione/utenti" class="d-inline">
                  <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                  <input type="hidden" name="action" value="toggle">
                  <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                  <button type="submit" class="btn btn-sm btn-outline-<?= $u['attivo']?'warning':'success' ?> py-0 px-1"
                    title="<?= $u['attivo']?'Disabilita':'Abilita' ?>"
                    onclick="return confirm('<?= $u['attivo']?'Disabilitare':'Abilitare' ?> questo utente?')">
                    <i class="bi bi-<?= $u['attivo']?'pause-circle':'play-circle' ?>"></i>
                  </button>
                </form>
                <form method="post" action="/configurazione/utenti" class="d-inline">
                  <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                  <input type="hidden" name="action" value="reset_password">
                  <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                  <button type="submit" class="btn btn-sm btn-outline-secondary py-0 px-1" title="Reset Password">
                    <i class="bi bi-key"></i>
                  </button>
                </form>
                <?php else: ?>
                <span class="text-muted small">Tu</span>
                <?php endif; ?>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- Modal Nuovo Utente -->
<div class="modal fade" id="modal-utente" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Aggiungi Utente</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <form method="post" action="/configurazione/utenti">
        <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
        <input type="hidden" name="action" value="crea">
        <div class="modal-body row g-3">
          <div class="col-md-6">
            <label class="form-label">Username <span class="text-danger">*</span></label>
            <input type="text" name="username" class="form-control" required pattern="[a-z0-9._-]{3,50}" title="Usa solo lettere minuscole, numeri, punto, underscore">
          </div>
          <div class="col-md-6">
            <label class="form-label">Nome completo</label>
            <input type="text" name="nome_completo" class="form-control">
          </div>
          <div class="col-12">
            <label class="form-label">Email <span class="text-danger">*</span></label>
            <input type="email" name="email" class="form-control" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Password temporanea <span class="text-danger">*</span></label>
            <input type="password" name="password" class="form-control" required minlength="8">
          </div>
          <div class="col-md-6">
            <label class="form-label">Ruolo <span class="text-danger">*</span></label>
            <select name="ruolo" class="form-select" required>
              <option value="readonly">Solo lettura</option>
              <option value="operator">Operatore</option>
              <option value="admin" selected>Amministratore</option>
              <?php if ($currentUser['ruolo'] === 'superadmin'): ?>
              <option value="superadmin">Super Admin</option>
              <?php endif; ?>
            </select>
          </div>
          <div class="col-12">
            <div class="form-text">L'utente dovrà cambiare la password al primo accesso.</div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annulla</button>
          <button type="submit" class="btn btn-primary">Crea Utente</button>
        </div>
      </form>
    </div>
  </div>
</div>
