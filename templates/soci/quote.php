<?php
$e   = fn($v) => htmlspecialchars((string)$v);
$fmt = fn($v) => number_format((float)$v,2,',','.');
$quotaBadge = ['pagata'=>'success','parziale'=>'warning','attesa'=>'danger','esonerata'=>'secondary'];
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <div class="d-flex align-items-center gap-2">
    <a href="/soci/<?= $socio['id'] ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <h5 class="mb-0">Quote — <?= $e($socio['cognome'].' '.$socio['nome']) ?></h5>
  </div>
</div>

<div class="row g-3">
  <!-- Lista quote -->
  <div class="col-md-7">
    <div class="card">
      <div class="card-header fw-bold"><i class="bi bi-list-check me-2 text-primary"></i>Storico Quote</div>
      <div class="table-responsive">
        <table class="table table-sm mb-0">
          <thead>
            <tr>
              <th>Anno</th>
              <th class="text-end">Importo</th>
              <th class="text-end">Pagato</th>
              <th>Stato</th>
              <th>Data Pag.</th>
              <th>Metodo</th>
              <th class="text-center">Azioni</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($quote)): ?>
            <tr><td colspan="7" class="text-center text-muted py-4">Nessuna quota registrata.</td></tr>
            <?php else: foreach ($quote as $q): $qCls = $quotaBadge[$q['stato']] ?? 'secondary'; ?>
            <tr>
              <td><strong><?= $q['anno'] ?></strong></td>
              <td class="text-end">€ <?= $fmt($q['importo']) ?></td>
              <td class="text-end">€ <?= $fmt($q['importo_pagato']) ?></td>
              <td><span class="badge bg-<?= $qCls ?>-subtle text-<?= $qCls ?>"><?= ucfirst($q['stato']) ?></span></td>
              <td class="small"><?= $q['data_pagamento'] ? date('d/m/Y', strtotime($q['data_pagamento'])) : '-' ?></td>
              <td class="small text-muted"><?= $e($q['metodo_pagamento'] ?? '-') ?></td>
              <td class="text-center">
                <?php if ($q['stato'] !== 'pagata' && $q['stato'] !== 'esonerata'): ?>
                <button class="btn btn-xs btn-sm btn-success py-0 px-2"
                  data-quota-id="<?= $q['id'] ?>"
                  data-anno="<?= $q['anno'] ?>"
                  data-importo="<?= $q['importo'] ?>"
                  onclick="openPagamento(this)">
                  <i class="bi bi-check2"></i> Registra
                </button>
                <?php endif; ?>
                <?php if ($q['link_pagamento']): ?>
                <button class="btn btn-xs btn-sm btn-outline-secondary py-0 px-1" title="Copia link"
                  onclick="navigator.clipboard.writeText('<?= $e($q['link_pagamento']) ?>');this.innerHTML='<i class=\'bi bi-check2\'></i>'">
                  <i class="bi bi-link-45deg"></i>
                </button>
                <?php endif; ?>
              </td>
            </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Nuova quota / pagamento rapido -->
  <div class="col-md-5">
    <!-- Aggiungi quota manuale -->
    <div class="card mb-3">
      <div class="card-header fw-bold"><i class="bi bi-plus-circle me-2 text-primary"></i>Aggiungi Quota</div>
      <div class="card-body">
        <form method="post" action="/soci/<?= $socio['id'] ?>/quote">
          <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
          <input type="hidden" name="action" value="aggiungi">
          <div class="row g-2">
            <div class="col-6">
              <label class="form-label small">Anno</label>
              <input type="number" name="anno" class="form-control form-control-sm" value="<?= $esercizioCorrente ?>" min="2000" max="2099">
            </div>
            <div class="col-6">
              <label class="form-label small">Importo (€)</label>
              <input type="number" name="importo" class="form-control form-control-sm" step="0.01" min="0" value="<?= $e($importoDefault ?? '') ?>">
            </div>
            <div class="col-12">
              <label class="form-label small">Stato iniziale</label>
              <select name="stato" class="form-select form-select-sm">
                <option value="attesa">In attesa</option>
                <option value="esonerata" <?= !empty($socio['quota_esonerata'])?'selected':'' ?>>Esonerata</option>
              </select>
            </div>
            <div class="col-12">
              <button type="submit" class="btn btn-sm btn-primary w-100"><i class="bi bi-plus me-1"></i>Aggiungi</button>
            </div>
          </div>
        </form>
      </div>
    </div>

    <!-- Invia sollecito -->
    <?php if (!empty($quotaCorrente) && $quotaCorrente['stato'] === 'attesa' && $socio['email']): ?>
    <div class="card mb-3">
      <div class="card-header fw-bold"><i class="bi bi-send me-2 text-primary"></i>Sollecito Quota <?= $esercizioCorrente ?></div>
      <div class="card-body">
        <form method="post" action="/soci/<?= $socio['id'] ?>/quote">
          <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
          <input type="hidden" name="action" value="sollecito">
          <input type="hidden" name="quota_id" value="<?= $quotaCorrente['id'] ?>">
          <p class="small text-muted mb-2">Invierà un'email a <strong><?= $e($socio['email']) ?></strong> con link di pagamento.</p>
          <button type="submit" class="btn btn-sm btn-outline-primary w-100"><i class="bi bi-envelope me-1"></i>Invia Sollecito</button>
        </form>
      </div>
    </div>
    <?php endif; ?>
  </div>
</div>

<!-- Modal registrazione pagamento -->
<div class="modal fade" id="modal-pagamento" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Registra Pagamento</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <form method="post" action="/soci/<?= $socio['id'] ?>/quote">
        <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
        <input type="hidden" name="action" value="paga">
        <input type="hidden" name="quota_id" id="mp-quota-id">
        <div class="modal-body row g-3">
          <div class="col-12"><p class="mb-0">Anno: <strong id="mp-anno"></strong></p></div>
          <div class="col-6">
            <label class="form-label">Importo Pagato (€)</label>
            <input type="number" name="importo_pagato" id="mp-importo" class="form-control" step="0.01" min="0.01" required>
          </div>
          <div class="col-6">
            <label class="form-label">Data Pagamento</label>
            <input type="date" name="data_pagamento" class="form-control" value="<?= date('Y-m-d') ?>" required>
          </div>
          <div class="col-12">
            <label class="form-label">Metodo</label>
            <select name="metodo_pagamento" class="form-select">
              <option value="contanti">Contanti</option>
              <option value="bonifico">Bonifico</option>
              <option value="paypal">PayPal</option>
              <option value="carta">Carta</option>
              <option value="assegno">Assegno</option>
              <option value="altro">Altro</option>
            </select>
          </div>
          <div class="col-12">
            <label class="form-label">Riferimento (opz.)</label>
            <input type="text" name="riferimento" class="form-control" placeholder="N. ricevuta, CRO...">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annulla</button>
          <button type="submit" class="btn btn-success">Registra</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function openPagamento(btn) {
  document.getElementById('mp-quota-id').value = btn.dataset.quotaId;
  document.getElementById('mp-anno').textContent  = btn.dataset.anno;
  document.getElementById('mp-importo').value     = btn.dataset.importo;
  new bootstrap.Modal(document.getElementById('modal-pagamento')).show();
}
</script>
