<?php
$e   = fn($v) => htmlspecialchars((string)$v);
$fmt = fn($v) => number_format((float)$v,2,',','.');
$sezioniMenu = [
  'azienda'  => ['icon'=>'building','label'=>'Associazione'],
  'utenti'   => ['icon'=>'people','label'=>'Utenti'],
  'email'    => ['icon'=>'envelope-at','label'=>'Email SMTP'],
  'quote-annuali'=>['icon'=>'credit-card-2-front','label'=>'Quote Annuali'],
  'gdpr'     => ['icon'=>'shield-check','label'=>'GDPR & Privacy'],
];
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
    <?php if ($success ?? ''): ?><div class="alert alert-success py-2"><?= $e($success) ?></div><?php endif; ?>
    <?php if ($error ?? ''): ?><div class="alert alert-danger py-2"><?= $e($error) ?></div><?php endif; ?>

    <!-- Quote per tipo socio per anno -->
    <div class="card mb-4">
      <div class="card-header d-flex justify-content-between align-items-center fw-bold">
        <span><i class="bi bi-credit-card-2-front me-2 text-primary"></i>Quote Annuali per Tipo Socio</span>
        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modal-quota">
          <i class="bi bi-plus me-1"></i>Aggiungi
        </button>
      </div>
      <div class="table-responsive">
        <table class="table table-sm mb-0">
          <thead>
            <tr>
              <th>Anno</th>
              <th>Tipo Socio</th>
              <th class="text-end">Importo (€)</th>
              <th>Scadenza</th>
              <th>Note</th>
              <th class="text-center">Azioni</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($quoteAnnuali)): ?>
            <tr><td colspan="6" class="text-center text-muted py-4">Nessuna quota configurata.</td></tr>
            <?php else: foreach ($quoteAnnuali as $q): ?>
            <tr>
              <td><strong><?= $q['anno'] ?></strong></td>
              <td><span class="badge bg-primary-subtle text-primary"><?= ucfirst($q['tipo_socio']) ?></span></td>
              <td class="text-end">€ <?= $fmt($q['importo']) ?></td>
              <td class="small text-muted"><?= $q['data_scadenza'] ? date('d/m/Y', strtotime($q['data_scadenza'])) : '-' ?></td>
              <td class="small text-muted"><?= $e($q['note'] ?? '') ?></td>
              <td class="text-center">
                <form method="post" action="/configurazione/quote-annuali" class="d-inline"
                  onsubmit="return confirm('Eliminare questa quota?')">
                  <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                  <input type="hidden" name="action" value="elimina">
                  <input type="hidden" name="quota_id" value="<?= $q['id'] ?>">
                  <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-1"><i class="bi bi-trash3"></i></button>
                </form>
              </td>
            </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Genera quote soci -->
    <div class="card">
      <div class="card-header fw-bold"><i class="bi bi-magic me-2 text-primary"></i>Genera Quote Soci</div>
      <div class="card-body">
        <form method="post" action="/configurazione/quote-annuali">
          <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
          <input type="hidden" name="action" value="genera">
          <div class="row g-3 align-items-end">
            <div class="col-md-3">
              <label class="form-label">Anno</label>
              <select name="anno_genera" class="form-select">
                <?php foreach (range(date('Y')+1, date('Y')-1) as $y): ?>
                <option value="<?= $y ?>" <?= $y==date('Y')?'selected':'' ?>><?= $y ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-5">
              <p class="small text-muted mb-0">
                Crea un record quota per tutti i soci attivi per l'anno selezionato,
                usando gli importi configurati sopra per tipo socio.
              </p>
            </div>
            <div class="col-md-4">
              <button type="submit" class="btn btn-primary w-100"
                onclick="return confirm('Generare le quote per i soci attivi?')">
                <i class="bi bi-magic me-2"></i>Genera quote soci
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Modal Nuova Quota -->
<div class="modal fade" id="modal-quota" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Aggiungi Quota Annuale</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <form method="post" action="/configurazione/quote-annuali">
        <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
        <input type="hidden" name="action" value="aggiungi">
        <div class="modal-body row g-3">
          <div class="col-md-6">
            <label class="form-label">Anno <span class="text-danger">*</span></label>
            <input type="number" name="anno" class="form-control" value="<?= date('Y') ?>" min="2020" max="2099" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Tipo Socio <span class="text-danger">*</span></label>
            <select name="tipo_socio" class="form-select" required>
              <option value="ordinario">Ordinario</option>
              <option value="fondatore">Fondatore</option>
              <option value="onorario">Onorario</option>
              <option value="sostenitore">Sostenitore</option>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label">Importo (€) <span class="text-danger">*</span></label>
            <input type="number" name="importo" class="form-control" step="0.01" min="0" required placeholder="30.00">
          </div>
          <div class="col-md-6">
            <label class="form-label">Scadenza</label>
            <input type="date" name="data_scadenza" class="form-control" value="<?= date('Y').'-03-31' ?>">
          </div>
          <div class="col-12">
            <label class="form-label">Note</label>
            <input type="text" name="note" class="form-control" placeholder="es. Quota ridotta under 18">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annulla</button>
          <button type="submit" class="btn btn-primary">Aggiungi</button>
        </div>
      </form>
    </div>
  </div>
</div>
