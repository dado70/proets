<?php
$tipoIco = ['cassa'=>'cash','banca'=>'bank2','paypal'=>'paypal','stripe'=>'credit-card','carta_credito'=>'credit-card-2-front','altro'=>'wallet'];
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <form class="d-flex align-items-center gap-2">
    <label class="form-label mb-0 fw-semibold">Anno:</label>
    <select name="anno" class="form-select form-select-sm" style="width:90px" onchange="this.form.submit()">
      <?php foreach (range(date('Y'), 2020) as $a): ?><option value="<?= $a ?>" <?= $a==$anno?'selected':'' ?>><?= $a ?></option><?php endforeach; ?>
    </select>
  </form>
  <a href="/configurazione/azienda" class="btn btn-sm btn-outline-primary"><i class="bi bi-plus me-1"></i>Configura Conti</a>
</div>

<div class="row g-3">
  <?php foreach ($saldi as $s):
    $saldo = $s['saldo_attuale'];
    $ico   = $tipoIco[$s['tipo']] ?? 'wallet';
  ?>
  <div class="col-xl-3 col-md-6">
    <div class="card h-100">
      <div class="card-body">
        <div class="d-flex align-items-start justify-content-between mb-2">
          <div>
            <div class="fw-bold"><?= htmlspecialchars($s['nome']) ?></div>
            <div class="text-muted small"><?= htmlspecialchars(ucfirst($s['tipo'])) ?></div>
          </div>
          <i class="bi bi-<?= $ico ?> fs-3 text-primary opacity-50"></i>
        </div>
        <div class="fs-4 fw-bold <?= $saldo >= 0 ? 'text-success' : 'text-danger' ?>">
          € <?= number_format(abs($saldo), 2, ',', '.') ?>
          <?php if ($saldo < 0): ?><small class="text-danger">(negativo)</small><?php endif; ?>
        </div>
        <hr class="my-2">
        <div class="row text-center small">
          <div class="col">
            <div class="text-muted">Entrate <?= $anno ?></div>
            <div class="text-entrata fw-semibold">€ <?= number_format($s['entrate_anno'],2,',','.') ?></div>
          </div>
          <div class="col">
            <div class="text-muted">Uscite <?= $anno ?></div>
            <div class="text-uscita fw-semibold">€ <?= number_format($s['uscite_anno'],2,',','.') ?></div>
          </div>
        </div>
        <?php if ($s['iban']): ?>
        <div class="mt-2 text-muted" style="font-size:.7rem">IBAN: <?= htmlspecialchars($s['iban']) ?></div>
        <?php endif; ?>
      </div>
      <div class="card-footer py-1 d-flex gap-1">
        <a href="/prima-nota?account_id=<?= $s['id'] ?>&anno=<?= $anno ?>" class="btn btn-xs btn-sm btn-outline-secondary flex-fill">
          <i class="bi bi-list-ul me-1"></i>Movimenti
        </a>
        <a href="/prima-nota/riconciliazione" class="btn btn-xs btn-sm btn-outline-secondary flex-fill">
          <i class="bi bi-arrow-left-right me-1"></i>Riconcilia
        </a>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
  <?php if (empty($saldi)): ?>
  <div class="col-12 text-center py-5 text-muted">
    <i class="bi bi-wallet2 display-4 d-block mb-3"></i>
    Nessun conto configurato.<br>
    <a href="/configurazione/azienda" class="btn btn-primary mt-3">Configura i conti</a>
  </div>
  <?php endif; ?>
</div>
