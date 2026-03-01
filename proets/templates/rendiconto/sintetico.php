<?php
$e    = fn($v) => htmlspecialchars((string)$v);
$fmt  = fn($v) => number_format(abs((float)$v),2,',','.');
$fmtS = fn($v) => ((float)$v >= 0 ? '' : '-') . '€ ' . number_format(abs((float)$v),2,',','.');
$sezLabel = ['A'=>'Interesse Generale','B'=>'Attività Diverse','C'=>'Raccolta Fondi','D'=>'Finanziarie e Patrimoniali','E'=>'Supporto Generale'];
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <select class="form-select form-select-sm d-inline-block" style="width:100px" onchange="location.href='/rendiconto/sintetico?anno='+this.value">
    <?php foreach ($anni as $a): ?><option value="<?= $a ?>" <?= $a==$esercizio?'selected':'' ?>><?= $a ?></option><?php endforeach; ?>
  </select>
  <div class="d-flex gap-2">
    <a href="/rendiconto/pdf/sintetico?anno=<?= $esercizio ?>" class="btn btn-sm btn-danger"><i class="bi bi-file-earmark-pdf me-1"></i>PDF</a>
    <button onclick="window.print()" class="btn btn-sm btn-outline-secondary no-print"><i class="bi bi-printer me-1"></i>Stampa</button>
    <a href="/rendiconto/annuale?anno=<?= $esercizio ?>" class="btn btn-sm btn-outline-primary">Dettagliato</a>
  </div>
</div>

<!-- Intestazione -->
<div class="card mb-3">
  <div class="card-body py-2 text-center">
    <div class="fw-bold"><?= $e($company['ragione_sociale'] ?? '') ?></div>
    <div class="small text-muted">RENDICONTO SINTETICO <?= $esercizio ?> (art. 13, c. 2, D.Lgs 117/2017)</div>
  </div>
</div>

<div class="card">
  <div class="table-responsive">
    <table class="table table-sm table-bordered mb-0" style="font-size:.85rem">
      <thead class="table-dark">
        <tr>
          <th>Sezione</th>
          <th class="text-end"><?= $esercizio ?> — Uscite</th>
          <th class="text-end"><?= $esercizio ?> — Entrate</th>
          <th class="text-end">Avanzo/Disavanzo</th>
          <th class="text-end"><?= $esercizioPrec ?> — Avanzo/Disavanzo</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($dati['sezioni'] as $sez => $sd): ?>
        <tr>
          <td class="fw-semibold"><?= $sez ?>) <?= $sezLabel[$sez] ?></td>
          <td class="text-end text-uscita">€ <?= $fmt($sd['tot_uscite']) ?></td>
          <td class="text-end text-entrata">€ <?= $fmt($sd['tot_entrate']) ?></td>
          <td class="text-end fw-bold <?= $sd['avanzo']>=0?'text-success':'text-danger' ?>"><?= $fmtS($sd['avanzo']) ?></td>
          <td class="text-end text-muted small"><?= $fmtS($datiPrec['sezioni'][$sez]['avanzo'] ?? 0) ?></td>
        </tr>
        <?php endforeach; ?>
        <!-- Gestione -->
        <tr class="table-light fw-bold">
          <td>Totale gestione</td>
          <td class="text-end">€ <?= $fmt($dati['tot_uscite_gest']) ?></td>
          <td class="text-end">€ <?= $fmt($dati['tot_entrate_gest']) ?></td>
          <td class="text-end <?= $dati['avanzo_gestione']>=0?'text-success':'text-danger' ?>"><?= $fmtS($dati['avanzo_gestione']) ?></td>
          <td class="text-end text-muted small"><?= $fmtS($datiPrec['avanzo_gestione']??0) ?></td>
        </tr>
        <?php if ($dati['imposte']): ?>
        <tr><td>Imposte</td><td class="text-end text-uscita">€ <?= $fmt($dati['imposte']) ?></td><td colspan="3"></td></tr>
        <?php endif; ?>
        <!-- Investimenti -->
        <tr>
          <td>Inv./Disinvestimenti patrimoniali</td>
          <td class="text-end text-uscita">€ <?= $fmt($dati['tot_inv']) ?></td>
          <td class="text-end text-entrata">€ <?= $fmt($dati['tot_dis']) ?></td>
          <td class="text-end <?= $dati['avanzo_inv_dis']>=0?'text-success':'text-danger' ?>"><?= $fmtS($dati['avanzo_inv_dis']) ?></td>
          <td class="text-end text-muted small"><?= $fmtS($datiPrec['avanzo_inv_dis']??0) ?></td>
        </tr>
        <!-- Avanzo complessivo -->
        <tr class="<?= $dati['avanzo_complessivo']>=0?'table-success':'table-danger' ?> fw-bold fs-6">
          <td colspan="3" class="text-end">Avanzo/Disavanzo complessivo d'esercizio</td>
          <td class="text-end"><?= $fmtS($dati['avanzo_complessivo']) ?></td>
          <td class="text-end text-muted"><?= $fmtS($datiPrec['avanzo_complessivo']??0) ?></td>
        </tr>
      </tbody>
    </table>
  </div>
</div>

<!-- Grafico ad anello sezioni -->
<div class="card mt-3 no-print">
  <div class="card-header"><i class="bi bi-pie-chart me-2"></i>Distribuzione Entrate per Sezione</div>
  <div class="card-body" style="max-height:250px">
    <canvas id="chart-sezioni"></canvas>
  </div>
</div>

<?php
$labels  = []; $vals = [];
foreach ($dati['sezioni'] as $sez => $sd) {
    if ($sd['tot_entrate'] > 0) {
        $labels[] = $sez . ') ' . $sezLabel[$sez];
        $vals[]   = round($sd['tot_entrate'],2);
    }
}
$scripts = '<script>
new Chart(document.getElementById("chart-sezioni"),{
  type:"doughnut",
  data:{labels:'.json_encode($labels).',datasets:[{data:'.json_encode($vals).',backgroundColor:["#2563eb","#16a34a","#d97706","#7c3aed","#0891b2"]}]},
  options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{position:"right"}}}
});
</script>';
