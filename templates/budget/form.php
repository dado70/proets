<?php
$e    = fn($v) => htmlspecialchars((string)$v);
$fmt  = fn($v) => number_format(abs((float)$v),2,',','.');
$isEdit = isset($budget['id']);

$sezLabel = ['A'=>'Interesse Generale','B'=>'Attività Diverse','C'=>'Raccolta Fondi','D'=>'Finanziarie e Patrimoniali','E'=>'Supporto Generale'];
$struttura = [
    'A' => [
        'uscite'  => ['UA1'=>'Materie prime, sussidiarie e merci','UA2'=>'Servizi','UA3'=>'Godimento beni di terzi','UA4'=>'Personale','UA5'=>'Altre uscite'],
        'entrate' => ['EA1'=>'Quote associative','EA2'=>'Contributi da associati','EA3'=>'Contributi da privati','EA4'=>'Contributi da enti pubblici','EA5'=>'Contributi da enti del Terzo settore','EA6'=>'Ricavi da attività istituzionali','EA7'=>'Ricavi da attività commerciali connesse','EA8'=>'Erogazioni liberali','EA9'=>'Ricavi da 5x1000','EA10'=>'Altre entrate'],
    ],
    'B' => [
        'uscite'  => ['UB1'=>'Materie prime, sussidiarie e merci','UB2'=>'Servizi','UB3'=>'Godimento beni di terzi','UB4'=>'Personale','UB5'=>'Altre uscite'],
        'entrate' => ['EB1'=>'Quote associative','EB2'=>'Contributi da associati','EB3'=>'Contributi da privati','EB4'=>'Contributi da enti pubblici','EB5'=>'Ricavi da attività commerciali','EB6'=>'Altre entrate'],
    ],
    'C' => [
        'uscite'  => ['UC1'=>'Raccolta fondi occasionale','UC2'=>'Raccolta fondi continuativa','UC3'=>'Altre uscite raccolta'],
        'entrate' => ['EC1'=>'Raccolta fondi occasionale','EC2'=>'Raccolta fondi continuativa','EC3'=>'Altre entrate raccolta'],
    ],
    'D' => [
        'uscite'  => ['UD1'=>'Interessi passivi e oneri finanziari','UD2'=>'Perdite su cambi','UD3'=>'Rettifiche di valore attività finanziarie','UD4'=>'Uscite patrimoniali','UD5'=>'Altre uscite finanziarie'],
        'entrate' => ['ED1'=>'Interessi attivi','ED2'=>'Utili su cambi','ED3'=>'Proventi da partecipazioni','ED4'=>'Entrate patrimoniali','ED5'=>'Altre entrate finanziarie'],
    ],
    'E' => [
        'uscite'  => ['UE1'=>'Personale di supporto','UE2'=>'Servizi generali','UE3'=>'Godimento beni terzi (sede)','UE4'=>'Ammortamenti','UE5'=>'Altre uscite supporto'],
        'entrate' => ['EE1'=>'Rimborsi e recuperi','EE2'=>'Altre entrate supporto'],
    ],
];
$investimenti = ['INV1'=>'Immobili','INV2'=>'Impianti e macchinari','INV3'=>'Attrezzature','INV4'=>'Partecipazioni e titoli'];
$disinvestimenti = ['DIS1'=>'Immobili','DIS2'=>'Impianti e macchinari','DIS3'=>'Attrezzature','DIS4'=>'Partecipazioni e titoli'];
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h5 class="mb-0"><?= $isEdit ? 'Modifica Preventivo' : 'Nuovo Preventivo' ?></h5>
  <a href="/budget" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Torna ai preventivi</a>
</div>

<form method="post" action="<?= $isEdit ? '/budget/'.$budget['id'].'/modifica' : '/budget/nuovo' ?>" id="form-budget">
  <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">

  <!-- Intestazione -->
  <div class="card mb-3">
    <div class="card-body">
      <div class="row g-3">
        <div class="col-md-5">
          <label class="form-label">Nome preventivo <span class="text-danger">*</span></label>
          <input type="text" name="nome" class="form-control" value="<?= $e($budget['nome'] ?? 'Preventivo '.$esercizio) ?>" required>
        </div>
        <div class="col-md-2">
          <label class="form-label">Anno <span class="text-danger">*</span></label>
          <input type="number" name="anno" class="form-control" value="<?= $e($budget['anno'] ?? $esercizio) ?>" min="2020" max="2099" required>
        </div>
        <div class="col-md-5">
          <label class="form-label">Note</label>
          <input type="text" name="note" class="form-control" value="<?= $e($budget['note'] ?? '') ?>" placeholder="Note opzionali">
        </div>
      </div>
    </div>
  </div>

  <!-- Voci per sezione -->
  <?php foreach ($struttura as $sez => $parti): ?>
  <div class="card mb-3">
    <div class="card-header fw-bold">
      <i class="bi bi-grid-1x2 me-2 text-primary"></i>Sezione <?= $sez ?> — <?= $sezLabel[$sez] ?>
    </div>
    <div class="card-body p-0">
      <div class="row g-0">
        <!-- Uscite -->
        <div class="col-md-6 border-end">
          <div class="p-2 bg-danger bg-opacity-10 small fw-bold text-danger border-bottom">Uscite</div>
          <?php foreach ($parti['uscite'] as $cod => $label): ?>
          <div class="d-flex align-items-center px-3 py-2 border-bottom">
            <div class="flex-grow-1 small">
              <span class="text-muted me-1"><?= $cod ?></span><?= $e($label) ?>
            </div>
            <div style="width:130px">
              <div class="input-group input-group-sm">
                <span class="input-group-text">€</span>
                <input type="number" name="voci[<?= $cod ?>]" class="form-control text-end voce-input"
                  step="0.01" min="0"
                  value="<?= isset($voci[$cod]) ? number_format($voci[$cod],2,'.',''): '' ?>"
                  placeholder="0,00"
                  onchange="ricalcola()">
              </div>
            </div>
          </div>
          <?php endforeach; ?>
          <div class="d-flex justify-content-between align-items-center px-3 py-2 bg-danger bg-opacity-5">
            <span class="small fw-bold">Totale uscite <?= $sez ?></span>
            <span class="fw-bold text-danger" id="tot-u-<?= $sez ?>">€ 0,00</span>
          </div>
        </div>
        <!-- Entrate -->
        <div class="col-md-6">
          <div class="p-2 bg-success bg-opacity-10 small fw-bold text-success border-bottom">Entrate</div>
          <?php foreach ($parti['entrate'] as $cod => $label): ?>
          <div class="d-flex align-items-center px-3 py-2 border-bottom">
            <div class="flex-grow-1 small">
              <span class="text-muted me-1"><?= $cod ?></span><?= $e($label) ?>
            </div>
            <div style="width:130px">
              <div class="input-group input-group-sm">
                <span class="input-group-text">€</span>
                <input type="number" name="voci[<?= $cod ?>]" class="form-control text-end voce-input"
                  step="0.01" min="0"
                  value="<?= isset($voci[$cod]) ? number_format($voci[$cod],2,'.',''): '' ?>"
                  placeholder="0,00"
                  onchange="ricalcola()">
              </div>
            </div>
          </div>
          <?php endforeach; ?>
          <div class="d-flex justify-content-between align-items-center px-3 py-2 bg-success bg-opacity-5">
            <span class="small fw-bold">Totale entrate <?= $sez ?></span>
            <span class="fw-bold text-success" id="tot-e-<?= $sez ?>">€ 0,00</span>
          </div>
        </div>
      </div>
    </div>
  </div>
  <?php endforeach; ?>

  <!-- Investimenti / Disinvestimenti -->
  <div class="card mb-3">
    <div class="card-header fw-bold"><i class="bi bi-building me-2 text-primary"></i>Investimenti / Disinvestimenti Patrimoniali</div>
    <div class="card-body p-0">
      <div class="row g-0">
        <div class="col-md-6 border-end">
          <div class="p-2 bg-danger bg-opacity-10 small fw-bold text-danger border-bottom">Investimenti</div>
          <?php foreach ($investimenti as $cod => $label): ?>
          <div class="d-flex align-items-center px-3 py-2 border-bottom">
            <div class="flex-grow-1 small"><span class="text-muted me-1"><?= $cod ?></span><?= $e($label) ?></div>
            <div style="width:130px">
              <div class="input-group input-group-sm">
                <span class="input-group-text">€</span>
                <input type="number" name="voci[<?= $cod ?>]" class="form-control text-end voce-input" step="0.01" min="0"
                  value="<?= isset($voci[$cod]) ? number_format($voci[$cod],2,'.',''): '' ?>"
                  placeholder="0,00" onchange="ricalcola()">
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <div class="col-md-6">
          <div class="p-2 bg-success bg-opacity-10 small fw-bold text-success border-bottom">Disinvestimenti</div>
          <?php foreach ($disinvestimenti as $cod => $label): ?>
          <div class="d-flex align-items-center px-3 py-2 border-bottom">
            <div class="flex-grow-1 small"><span class="text-muted me-1"><?= $cod ?></span><?= $e($label) ?></div>
            <div style="width:130px">
              <div class="input-group input-group-sm">
                <span class="input-group-text">€</span>
                <input type="number" name="voci[<?= $cod ?>]" class="form-control text-end voce-input" step="0.01" min="0"
                  value="<?= isset($voci[$cod]) ? number_format($voci[$cod],2,'.',''): '' ?>"
                  placeholder="0,00" onchange="ricalcola()">
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- Riepilogo totali -->
  <div class="card mb-3 border-primary">
    <div class="card-header fw-bold bg-primary text-white">Riepilogo Preventivo</div>
    <div class="card-body">
      <div class="row text-center">
        <div class="col-md-4">
          <div class="h5 text-success mb-0" id="riepilogo-entrate">€ 0,00</div>
          <div class="small text-muted">Totale entrate</div>
        </div>
        <div class="col-md-4">
          <div class="h5 text-danger mb-0" id="riepilogo-uscite">€ 0,00</div>
          <div class="small text-muted">Totale uscite</div>
        </div>
        <div class="col-md-4">
          <div class="h5 mb-0" id="riepilogo-avanzo">€ 0,00</div>
          <div class="small text-muted">Avanzo/Disavanzo</div>
        </div>
      </div>
    </div>
  </div>

  <div class="d-flex gap-2">
    <button type="submit" class="btn btn-primary"><i class="bi bi-check2-circle me-2"></i><?= $isEdit ? 'Salva Modifiche' : 'Salva Preventivo' ?></button>
    <a href="/budget" class="btn btn-outline-secondary">Annulla</a>
  </div>
</form>

<script>
function fmtEur(v) {
  return '€ ' + Math.abs(v).toLocaleString('it-IT',{minimumFractionDigits:2,maximumFractionDigits:2});
}
function ricalcola() {
  const inputs = document.querySelectorAll('.voce-input');
  const sezioniU = {};
  const sezioniE = {};
  let totE = 0, totU = 0;

  inputs.forEach(inp => {
    const name = inp.name.match(/voci\[(\w+)\]/);
    if (!name) return;
    const cod = name[1];
    const val = parseFloat(inp.value) || 0;
    const lettera = cod[0];

    if (cod[0] === 'U') { sezioniU[lettera] = (sezioniU[lettera] || 0) + val; totU += val; }
    if (cod[0] === 'E') { sezioniE[lettera] = (sezioniE[lettera] || 0) + val; totE += val; }
    if (cod.startsWith('INV')) totU += val;
    if (cod.startsWith('DIS')) totE += val;
  });

  ['A','B','C','D','E'].forEach(s => {
    const eu = document.getElementById('tot-u-'+s);
    const ee = document.getElementById('tot-e-'+s);
    if (eu) eu.textContent = fmtEur(sezioniU['U'+s] || 0);
    if (ee) ee.textContent = fmtEur(sezioniE['E'+s] || 0);
  });

  const avanzo = totE - totU;
  document.getElementById('riepilogo-entrate').textContent = fmtEur(totE);
  document.getElementById('riepilogo-uscite').textContent  = fmtEur(totU);
  const el = document.getElementById('riepilogo-avanzo');
  el.textContent = (avanzo >= 0 ? '+' : '-') + ' ' + fmtEur(Math.abs(avanzo));
  el.className = 'h5 mb-0 ' + (avanzo >= 0 ? 'text-success' : 'text-danger');
}
document.querySelectorAll('.voce-input').forEach(i => i.addEventListener('input', ricalcola));
ricalcola();
</script>
