<?php
$e = fn($v) => htmlspecialchars((string)$v);
$tipoBadge = ['ordinario'=>'primary','fondatore'=>'dark','onorario'=>'info','sostenitore'=>'warning'];
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h5 class="mb-0"><i class="bi bi-megaphone me-2 text-primary"></i>Comunicazione Massiva</h5>
  <a href="/soci" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Torna ai soci</a>
</div>

<div class="row g-3">
  <!-- Form comunicazione -->
  <div class="col-md-7">
    <form method="post" action="/soci/comunicazioni">
      <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">

      <div class="card mb-3">
        <div class="card-header fw-bold"><i class="bi bi-filter me-2"></i>Destinatari</div>
        <div class="card-body">
          <div class="row g-2">
            <div class="col-md-6">
              <label class="form-label small">Tipo Socio</label>
              <select name="filtro_tipo" class="form-select form-select-sm" id="filtro-tipo">
                <option value="">Tutti i tipi</option>
                <option value="ordinario">Ordinario</option>
                <option value="fondatore">Fondatore</option>
                <option value="onorario">Onorario</option>
                <option value="sostenitore">Sostenitore</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label small">Stato Quota</label>
              <select name="filtro_quota" class="form-select form-select-sm" id="filtro-quota">
                <option value="">Tutti</option>
                <option value="attesa">In attesa</option>
                <option value="parziale">Parziale</option>
                <option value="pagata">Pagata</option>
              </select>
            </div>
            <div class="col-12">
              <div class="form-check">
                <input type="checkbox" name="solo_consenso" id="solo_consenso" class="form-check-input" value="1" checked>
                <label for="solo_consenso" class="form-check-label small">Solo soci con consenso comunicazioni</label>
              </div>
            </div>
            <div class="col-12 text-end">
              <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-anteprima">
                <i class="bi bi-eye me-1"></i>Anteprima destinatari
              </button>
              <span id="destinatari-count" class="text-muted small ms-2"></span>
            </div>
          </div>
        </div>
      </div>

      <div class="card mb-3">
        <div class="card-header fw-bold"><i class="bi bi-envelope me-2"></i>Messaggio</div>
        <div class="card-body">
          <div class="mb-3">
            <label class="form-label">Oggetto <span class="text-danger">*</span></label>
            <input type="text" name="oggetto" class="form-control" required placeholder="Oggetto dell'email...">
          </div>
          <div class="mb-2">
            <label class="form-label">Messaggio <span class="text-danger">*</span></label>
            <textarea name="messaggio" class="form-control" rows="8" required
              placeholder="Caro {nome},&#10;&#10;...&#10;&#10;Cordiali saluti,&#10;{associazione}"></textarea>
          </div>
          <div class="alert alert-info py-2 small mb-0">
            <strong>Variabili disponibili:</strong>
            <code>{nome}</code> <code>{cognome}</code> <code>{numero_tessera}</code>
            <code>{importo_quota}</code> <code>{link_pagamento}</code> <code>{associazione}</code>
          </div>
        </div>
      </div>

      <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary">
          <i class="bi bi-send me-2"></i>Invia a tutti i destinatari
        </button>
        <button type="submit" name="anteprima" value="1" class="btn btn-outline-secondary">
          <i class="bi bi-eye me-2"></i>Salva bozza
        </button>
      </div>
    </form>
  </div>

  <!-- Pannello laterale -->
  <div class="col-md-5">
    <!-- Anteprima destinatari -->
    <div class="card mb-3">
      <div class="card-header fw-bold"><i class="bi bi-people me-2"></i>Destinatari selezionati</div>
      <div style="max-height:300px;overflow-y:auto">
        <table class="table table-sm mb-0">
          <tbody id="lista-destinatari">
            <?php foreach ($soci as $s):
              $tCls = $tipoBadge[$s['tipo_socio']] ?? 'secondary'; ?>
            <tr class="dest-row"
              data-tipo="<?= $e($s['tipo_socio']) ?>"
              data-quota="<?= $e($s['stato_quota'] ?? '') ?>"
              data-consenso="<?= $s['consenso_comunicazioni'] ? '1' : '0' ?>">
              <td class="py-1 small">
                <span class="badge bg-<?= $tCls ?>-subtle text-<?= $tCls ?>" style="font-size:.55rem"><?= substr($s['tipo_socio'],0,3) ?></span>
                <?= $e($s['cognome'].' '.$s['nome']) ?>
              </td>
              <td class="py-1 small text-muted"><?= $e($s['email'] ?? '-') ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Storico comunicazioni recenti -->
    <div class="card">
      <div class="card-header fw-bold"><i class="bi bi-clock-history me-2"></i>Ultime comunicazioni</div>
      <div class="table-responsive">
        <table class="table table-sm mb-0">
          <thead><tr><th>Data</th><th>Oggetto</th><th class="text-end">N.</th></tr></thead>
          <tbody>
            <?php if (empty($storicoInvii)): ?>
            <tr><td colspan="3" class="text-center text-muted py-3">Nessuna comunicazione inviata.</td></tr>
            <?php else: foreach ($storicoInvii as $si): ?>
            <tr>
              <td class="small text-nowrap"><?= date('d/m/Y', strtotime($si['created_at'])) ?></td>
              <td class="small"><?= $e($si['oggetto']) ?></td>
              <td class="text-end small text-muted"><?= $si['destinatari_count'] ?></td>
            </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<script>
function filtraDestinatari() {
  const tipo    = document.getElementById('filtro-tipo').value;
  const quota   = document.getElementById('filtro-quota').value;
  const consenso = document.getElementById('solo_consenso').checked;
  let count = 0;
  document.querySelectorAll('.dest-row').forEach(row => {
    const match =
      (!tipo    || row.dataset.tipo  === tipo)  &&
      (!quota   || row.dataset.quota === quota) &&
      (!consenso || row.dataset.consenso === '1');
    row.style.display = match ? '' : 'none';
    if (match) count++;
  });
  document.getElementById('destinatari-count').textContent = count + ' destinatari';
}

document.getElementById('filtro-tipo').addEventListener('change', filtraDestinatari);
document.getElementById('filtro-quota').addEventListener('change', filtraDestinatari);
document.getElementById('solo_consenso').addEventListener('change', filtraDestinatari);
document.getElementById('btn-anteprima').addEventListener('click', filtraDestinatari);
filtraDestinatari();
</script>
