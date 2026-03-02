<?php
$e    = fn($v) => htmlspecialchars((string)$v);
$fmt  = fn($v) => number_format(abs((float)$v),2,',','.');
$sezLabel = ['A'=>'Interesse Generale','B'=>'Attività Diverse','C'=>'Raccolta Fondi','D'=>'Finanziarie e Patrimoniali','E'=>'Supporto Generale'];
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <div class="d-flex gap-2 align-items-center">
    <select class="form-select form-select-sm" style="width:100px" onchange="location.href='/rendiconto/scostamenti?anno='+this.value">
      <?php foreach ($anni as $a): ?><option value="<?= $a ?>" <?= $a==$esercizio?'selected':'' ?>><?= $a ?></option><?php endforeach; ?>
    </select>
    <?php if (!$hasBudget): ?>
    <div class="alert alert-warning py-1 px-2 mb-0 small">
      <i class="bi bi-exclamation-triangle me-1"></i>Nessun preventivo approvato per <?= $esercizio ?>.
      <a href="/budget/nuovo" class="ms-1">Crea preventivo</a>
    </div>
    <?php endif; ?>
  </div>
  <div class="d-flex gap-2">
    <a href="/rendiconto/annuale?anno=<?= $esercizio ?>" class="btn btn-sm btn-outline-primary">Rendiconto</a>
  </div>
</div>

<div class="card">
  <div class="table-responsive">
    <table class="table table-sm table-bordered mb-0" style="font-size:.82rem">
      <thead class="table-dark">
        <tr>
          <th>Cod.</th>
          <th>Voce</th>
          <th class="text-end">Preventivo</th>
          <th class="text-end">Consuntivo</th>
          <th class="text-end">Scostamento</th>
          <th class="text-end">% Scost.</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($consuntivo['sezioni'] as $sez => $sd): ?>
        <!-- Sezione Uscite -->
        <tr class="table-primary fw-bold"><td colspan="6"><?= $sez ?>) Uscite da attività di <?= strtolower($sezLabel[$sez]) ?></td></tr>
        <?php foreach ($sd['uscite'] as $cod => $item):
          $prev = $preventivo[$cod] ?? 0;
          $cons = $item['importo'];
          $sc   = $cons - $prev;
          $perc = $prev > 0 ? ($sc/$prev*100) : ($cons > 0 ? 100 : 0);
        ?>
        <tr>
          <td class="text-muted small"><?= $e($cod) ?></td>
          <td><?= $e($item['label']) ?></td>
          <td class="text-end"><?= $prev > 0 ? '€ '.$fmt($prev) : '-' ?></td>
          <td class="text-end <?= $cons>0?'text-uscita':'' ?>"><?= $cons > 0 ? '€ '.$fmt($cons) : '-' ?></td>
          <td class="text-end <?= $sc > 0 ? 'text-danger' : ($sc < 0 ? 'text-success' : '') ?>">
            <?= $sc != 0 ? ($sc > 0 ? '+' : '') . '€ ' . $fmt($sc) : '-' ?>
          </td>
          <td class="text-end <?= abs($perc) > 20 ? ($sc > 0 ? 'text-danger' : 'text-success') : '' ?>">
            <?= $prev > 0 ? number_format($perc,1,',','.').'%' : '-' ?>
          </td>
        </tr>
        <?php endforeach; ?>

        <!-- Sezione Entrate -->
        <tr class="table-success fw-bold"><td colspan="6"><?= $sez ?>) Entrate da attività di <?= strtolower($sezLabel[$sez]) ?></td></tr>
        <?php foreach ($sd['entrate'] as $cod => $item):
          $prev = $preventivo[$cod] ?? 0;
          $cons = $item['importo'];
          $sc   = $cons - $prev;
          $perc = $prev > 0 ? ($sc/$prev*100) : 0;
        ?>
        <tr>
          <td class="text-muted small"><?= $e($cod) ?></td>
          <td><?= $e($item['label']) ?></td>
          <td class="text-end"><?= $prev > 0 ? '€ '.$fmt($prev) : '-' ?></td>
          <td class="text-end <?= $cons>0?'text-entrata':'' ?>"><?= $cons > 0 ? '€ '.$fmt($cons) : '-' ?></td>
          <td class="text-end <?= $sc < 0 ? 'text-danger' : ($sc > 0 ? 'text-success' : '') ?>">
            <?= $sc != 0 ? ($sc > 0 ? '+' : '') . '€ ' . $fmt($sc) : '-' ?>
          </td>
          <td class="text-end"><?= $prev > 0 ? number_format($perc,1,',','.').'%' : '-' ?></td>
        </tr>
        <?php endforeach; ?>
        <?php endforeach; ?>

        <!-- Totali -->
        <tr class="table-dark fw-bold">
          <td colspan="2">TOTALI GESTIONE</td>
          <td class="text-end">€ <?= $fmt(array_sum(array_map(fn($s)=>$s['tot_uscite'],$consuntivo['sezioni']))) ?> | € <?= $fmt(array_sum(array_map(fn($s)=>$s['tot_entrate'],$consuntivo['sezioni']))) ?></td>
          <td class="text-end">€ <?= $fmt($consuntivo['tot_uscite_gest']) ?> | € <?= $fmt($consuntivo['tot_entrate_gest']) ?></td>
          <td colspan="2"></td>
        </tr>
        <tr class="<?= $consuntivo['avanzo_complessivo']>=0?'table-success':'table-danger' ?> fw-bold">
          <td colspan="2">Avanzo/Disavanzo complessivo</td>
          <td class="text-end"><?php
            $prevAvanzo = array_sum($preventivo) > 0 ? (array_sum(array_filter($preventivo, fn($v) => $v > 0)) - array_sum(array_filter($preventivo, fn($v) => $v < 0))) : 0;
            echo '-';
          ?></td>
          <td class="text-end">€ <?= $fmt($consuntivo['avanzo_complessivo']) ?></td>
          <td colspan="2"></td>
        </tr>
      </tbody>
    </table>
  </div>
</div>
