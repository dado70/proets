<?php
$e   = fn($v) => htmlspecialchars((string)$v);
$fmt = fn($v) => '€ ' . number_format(abs((float)$v),2,',','.');
$pct = fn($v) => number_format((float)$v,2,',','.') . '%';
$ok  = fn(bool $pass) => $pass
    ? '<span class="badge bg-success"><i class="bi bi-check-lg me-1"></i>SODDISFATTO</span>'
    : '<span class="badge bg-danger"><i class="bi bi-x-lg me-1"></i>NON SODDISFATTO</span>';
?>
<!-- Toolbar -->
<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <select class="form-select form-select-sm d-inline-block" style="width:100px" onchange="location.href='/rendiconto/test-ets?anno='+this.value">
      <?php foreach ($anni as $a): ?><option value="<?= $a ?>" <?= $a==$esercizio?'selected':'' ?>><?= $a ?></option><?php endforeach; ?>
    </select>
  </div>
  <a href="/rendiconto/annuale?anno=<?= $esercizio ?>" class="btn btn-sm btn-outline-secondary">← Rendiconto</a>
</div>

<!-- Alert risultato generale -->
<div class="alert <?= $test['secondarieta_rispettata'] ? 'alert-success' : 'alert-danger' ?> d-flex align-items-center mb-4">
  <i class="bi bi-<?= $test['secondarieta_rispettata'] ? 'shield-check' : 'shield-exclamation' ?> fs-3 me-3"></i>
  <div>
    <div class="fw-bold fs-5">
      <?= $test['secondarieta_rispettata'] ? 'La secondarietà civilistica è rispettata' : 'Attenzione: secondarietà non rispettata' ?>
    </div>
    <div class="small">
      <?= $test['secondarieta_rispettata']
        ? 'Le attività diverse dell\'associazione rispettano i limiti quantitativi previsti dall\'art. 6 del Codice del Terzo Settore (D.Lgs 117/2017).'
        : 'Le attività diverse superano i limiti quantitativi. Verificare con il commercialista le implicazioni fiscali.' ?>
    </div>
  </div>
</div>

<div class="row g-3 mb-4">
  <!-- Dati base -->
  <div class="col-md-4">
    <div class="card h-100">
      <div class="card-header fw-bold"><i class="bi bi-table me-2"></i>Dati di riferimento <?= $esercizio ?></div>
      <div class="card-body p-0">
        <table class="table table-sm mb-0">
          <tr><td>Entrate totali gestione</td><td class="text-end fw-bold"><?= $fmt($test['entrate_totali']) ?></td></tr>
          <tr><td>Entrate attività commerciali (Sez. B)</td><td class="text-end text-uscita fw-bold"><?= $fmt($test['entrate_commerciali']) ?></td></tr>
          <tr><td>Entrate attività istituzionali</td><td class="text-end text-entrata fw-bold"><?= $fmt($test['entrate_istituzionali']) ?></td></tr>
          <tr><td>Costi complessivi gestione</td><td class="text-end fw-bold"><?= $fmt($test['costi_totali']) ?></td></tr>
        </table>
      </div>
    </div>
  </div>

  <!-- 1° Test Requisito A -->
  <div class="col-md-4">
    <div class="card h-100 <?= $test['test1_req_a'] ? 'border-success' : 'border-danger' ?>">
      <div class="card-header fw-bold d-flex justify-content-between">
        <span>1° Test — Requisito A</span>
        <?= $ok($test['test1_req_a']) ?>
      </div>
      <div class="card-body">
        <p class="text-muted small mb-3">I ricavi da attività diverse devono essere <strong>inferiori al 30%</strong> delle entrate totali.</p>
        <div class="mb-2">
          <div class="d-flex justify-content-between small mb-1">
            <span>Attività commerciali / Entrate totali</span>
            <strong class="<?= $test['test1_req_a']?'text-success':'text-danger' ?>"><?= $pct($test['test1_perc_entrate']) ?></strong>
          </div>
          <div class="progress" style="height:12px">
            <div class="progress-bar <?= $test['test1_req_a']?'bg-success':'bg-danger' ?>"
              style="width:<?= min(100,$test['test1_perc_entrate']) ?>%"></div>
            <div class="progress-bar bg-light border" style="width:<?= max(0,30-min(30,$test['test1_perc_entrate'])) ?>%;"></div>
          </div>
          <div class="text-muted small mt-1">Limite: 30% — Valore: <?= $pct($test['test1_perc_entrate']) ?></div>
        </div>
      </div>
    </div>
  </div>

  <!-- 1° Test Requisito B -->
  <div class="col-md-4">
    <div class="card h-100 <?= $test['test1_req_b'] ? 'border-success' : 'border-danger' ?>">
      <div class="card-header fw-bold d-flex justify-content-between">
        <span>1° Test — Requisito B</span>
        <?= $ok($test['test1_req_b']) ?>
      </div>
      <div class="card-body">
        <p class="text-muted small mb-3">I ricavi da attività diverse devono essere <strong>inferiori al 66%</strong> dei costi complessivi.</p>
        <div class="mb-2">
          <div class="d-flex justify-content-between small mb-1">
            <span>Attività commerciali / Costi totali</span>
            <strong class="<?= $test['test1_req_b']?'text-success':'text-danger' ?>"><?= $pct($test['test1_perc_costi']) ?></strong>
          </div>
          <div class="progress" style="height:12px">
            <div class="progress-bar <?= $test['test1_req_b']?'bg-success':'bg-danger' ?>"
              style="width:<?= min(100,$test['test1_perc_costi']) ?>%"></div>
          </div>
          <div class="text-muted small mt-1">Limite: 66% — Valore: <?= $pct($test['test1_perc_costi']) ?></div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Note legali -->
<div class="card">
  <div class="card-header"><i class="bi bi-info-circle me-2"></i>Note Normative</div>
  <div class="card-body small text-muted">
    <p><strong>Art. 6 D.Lgs 117/2017 (Codice del Terzo Settore)</strong>: Gli enti del Terzo Settore possono esercitare attività diverse da quelle di interesse generale, a condizione che siano secondarie e strumentali rispetto alle attività istituzionali, secondo criteri e limiti stabiliti con decreto ministeriale.</p>
    <p><strong>Criteri quantitativi (D.M. 19/05/2021, art. 2)</strong>:</p>
    <ul>
      <li>I ricavi da attività diverse non superano il <strong>30% delle entrate complessive</strong></li>
      <li>I ricavi da attività diverse non superano il <strong>66% dei costi complessivi</strong></li>
    </ul>
    <p class="mb-0">Per la qualifica di ente non commerciale ai fini fiscali, si rimanda all'art. 79 e seguenti del CTS e alla circolare dell'Agenzia delle Entrate. Consultare sempre il proprio commercialista.</p>
  </div>
</div>
