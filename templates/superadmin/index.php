<?php
$e = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$formeLegali = ['APS','ODV','ETS','ONLUS','Altro'];
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
    <h4 class="mb-0 fw-bold"><i class="bi bi-shield-lock me-2 text-danger"></i>Pannello Superadmin</h4>
    <div class="text-muted small">Gestione centralizzata di tutte le associazioni</div>
  </div>
  <div class="d-flex gap-2">
    <a href="/superadmin/utenti" class="btn btn-outline-primary btn-sm">
      <i class="bi bi-people me-1"></i>Gestione Utenti
    </a>
    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modal-nuova-azienda">
      <i class="bi bi-plus-lg me-1"></i>Nuova Associazione
    </button>
  </div>
</div>

<!-- Stats bar -->
<div class="row g-3 mb-4">
  <div class="col-sm-4">
    <div class="card border-0 bg-primary bg-opacity-10">
      <div class="card-body py-3">
        <div class="d-flex align-items-center gap-3">
          <i class="bi bi-building fs-3 text-primary"></i>
          <div>
            <div class="fs-4 fw-bold"><?= count(array_filter($companies, fn($c) => $c['attivo'])) ?></div>
            <div class="small text-muted">Associazioni attive</div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-sm-4">
    <div class="card border-0 bg-success bg-opacity-10">
      <div class="card-body py-3">
        <div class="d-flex align-items-center gap-3">
          <i class="bi bi-people fs-3 text-success"></i>
          <div>
            <div class="fs-4 fw-bold"><?= array_sum(array_column($companies, 'n_utenti')) ?></div>
            <div class="small text-muted">Utenti totali</div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-sm-4">
    <div class="card border-0 bg-warning bg-opacity-10">
      <div class="card-body py-3">
        <div class="d-flex align-items-center gap-3">
          <i class="bi bi-person-gear fs-3 text-warning"></i>
          <div>
            <div class="fs-4 fw-bold"><?= array_sum(array_column($companies, 'n_admin')) ?></div>
            <div class="small text-muted">Amministratori</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Tabella associazioni -->
<div class="card">
  <div class="card-header fw-bold">
    <i class="bi bi-building me-2 text-primary"></i>Associazioni registrate
  </div>
  <div class="table-responsive">
    <table class="table table-sm table-hover mb-0 align-middle">
      <thead class="table-light">
        <tr>
          <th>Codice</th>
          <th>Ragione Sociale</th>
          <th>Forma</th>
          <th>Città</th>
          <th>Esercizio</th>
          <th class="text-center">Utenti</th>
          <th class="text-center">Admin</th>
          <th class="text-center">Stato</th>
          <th class="text-center">Azioni</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($companies as $c): ?>
        <tr class="<?= !$c['attivo'] ? 'text-muted' : '' ?>">
          <td><code class="small"><?= $e($c['codice']) ?></code></td>
          <td class="fw-semibold">
            <?= $e($c['ragione_sociale']) ?>
            <?php if ($c['id'] == ($company['id'] ?? 0)): ?>
            <span class="badge bg-primary-subtle text-primary ms-1" style="font-size:.6rem">Attiva</span>
            <?php endif; ?>
          </td>
          <td><span class="badge bg-secondary-subtle text-secondary"><?= $e($c['forma_giuridica']) ?></span></td>
          <td class="small text-muted"><?= $e($c['citta'] ?? '—') ?></td>
          <td class="small"><?= $e($c['esercizio_corrente'] ?? '—') ?></td>
          <td class="text-center"><?= (int)$c['n_utenti'] ?></td>
          <td class="text-center">
            <?php if ($c['n_admin'] == 0): ?>
            <span class="badge bg-danger-subtle text-danger">0 ⚠</span>
            <?php else: ?>
            <?= (int)$c['n_admin'] ?>
            <?php endif; ?>
          </td>
          <td class="text-center">
            <span class="badge bg-<?= $c['attivo'] ? 'success' : 'secondary' ?>-subtle text-<?= $c['attivo'] ? 'success' : 'secondary' ?>">
              <?= $c['attivo'] ? 'Attiva' : 'Disabilitata' ?>
            </span>
          </td>
          <td class="text-center">
            <!-- Switch azienda -->
            <form method="post" action="/dashboard/switch-company" class="d-inline">
              <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
              <input type="hidden" name="company_id" value="<?= (int)$c['id'] ?>">
              <button type="submit" class="btn btn-sm btn-outline-primary py-0 px-1" title="Entra come questa associazione">
                <i class="bi bi-box-arrow-in-right"></i>
              </button>
            </form>
            <!-- Toggle attivo -->
            <?php if ($c['id'] != ($company['id'] ?? 0)): ?>
            <form method="post" action="/superadmin/aziende/<?= (int)$c['id'] ?>/toggle" class="d-inline"
                  onsubmit="return confirm('<?= $c['attivo'] ? 'Disabilitare' : 'Riabilitare' ?> questa associazione?')">
              <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
              <button type="submit"
                class="btn btn-sm btn-outline-<?= $c['attivo'] ? 'warning' : 'success' ?> py-0 px-1"
                title="<?= $c['attivo'] ? 'Disabilita' : 'Riabilita' ?>">
                <i class="bi bi-<?= $c['attivo'] ? 'pause-circle' : 'play-circle' ?>"></i>
              </button>
            </form>
            <?php else: ?>
            <span class="text-muted small">—</span>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($companies)): ?>
        <tr><td colspan="9" class="text-center text-muted py-3">Nessuna associazione trovata.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Info box -->
<div class="alert alert-info mt-3 py-2 small">
  <i class="bi bi-info-circle me-2"></i>
  <strong>Come funziona:</strong> usa il pulsante <i class="bi bi-box-arrow-in-right"></i> per passare a un'associazione e poi vai su
  <strong>Configurazione → Utenti</strong> per gestirne gli utenti. Oppure usa
  <a href="/superadmin/utenti">Gestione Utenti</a> per creare un admin direttamente da qui.
</div>

<!-- Modal Nuova Associazione -->
<div class="modal fade" id="modal-nuova-azienda" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title"><i class="bi bi-building-add me-2"></i>Nuova Associazione</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="post" action="/superadmin">
        <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
        <div class="modal-body row g-3">
          <div class="col-md-8">
            <label class="form-label">Ragione Sociale <span class="text-danger">*</span></label>
            <input type="text" name="ragione_sociale" class="form-control" required maxlength="255"
                   placeholder="Es. Associazione Culturale Il Girasole">
          </div>
          <div class="col-md-4">
            <label class="form-label">Forma Giuridica</label>
            <select name="forma_giuridica" class="form-select">
              <?php foreach ($formeLegali as $f): ?>
              <option value="<?= $f ?>" <?= $f === 'APS' ? 'selected' : '' ?>><?= $f ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label">Codice Fiscale</label>
            <input type="text" name="codice_fiscale" class="form-control" maxlength="20"
                   placeholder="Es. 90012345678">
          </div>
          <div class="col-md-4">
            <label class="form-label">Città</label>
            <input type="text" name="citta" class="form-control" maxlength="100">
          </div>
          <div class="col-md-4">
            <label class="form-label">Esercizio corrente</label>
            <input type="number" name="esercizio_corrente" class="form-control"
                   value="<?= date('Y') ?>" min="2000" max="2099">
          </div>
          <div class="col-12">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" maxlength="150">
          </div>
          <div class="col-12">
            <div class="alert alert-info py-2 small mb-0">
              <i class="bi bi-info-circle me-1"></i>
              Dopo la creazione, vai su <strong>Gestione Utenti</strong> per creare l'amministratore di questa associazione.
              Vengono creati automaticamente un conto <em>Cassa Contanti</em> e un <em>Conto Bancario</em> di default.
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annulla</button>
          <button type="submit" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i>Crea Associazione
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
