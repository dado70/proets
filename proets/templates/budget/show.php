<?php
$e   = fn($v) => htmlspecialchars((string)$v);
$fmt = fn($v) => number_format(abs((float)$v),2,',','.');
$fmtS = fn($v) => ((float)$v >= 0 ? '' : '-') . '€ ' . number_format(abs((float)$v),2,',','.');
$statoBadge = ['bozza'=>'secondary','approvato'=>'success','archiviato'=>'dark'];
$sezLabel = ['A'=>'Interesse Generale','B'=>'Attività Diverse','C'=>'Raccolta Fondi','D'=>'Finanziarie e Patrimoniali','E'=>'Supporto Generale'];
$sCls = $statoBadge[$budget['stato']] ?? 'secondary';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <div class="d-flex align-items-center gap-2">
    <a href="/budget" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <h5 class="mb-0"><?= $e($budget['nome']) ?></h5>
    <span class="badge bg-<?= $sCls ?>-subtle text-<?= $sCls ?>"><?= ucfirst($budget['stato']) ?></span>
  </div>
  <div class="d-flex gap-2">
    <a href="/budget/<?= $budget['id'] ?>/pdf" class="btn btn-sm btn-danger"><i class="bi bi-file-earmark-pdf me-1"></i>PDF</a>
    <?php if ($budget['stato'] === 'bozza'): ?>
    <a href="/budget/<?= $budget['id'] ?>/modifica" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil me-1"></i>Modifica</a>
    <form method="post" action="/budget/<?= $budget['id'] ?>/approva"
      onsubmit="return confirm('Approvare il preventivo? Non sarà più modificabile.')">
      <input type="hidden" name="_csrf" value="<?= \ProETS\Core\Session::csrf() ?>">
      <button type="submit" class="btn btn-sm btn-success"><i class="bi bi-check2-circle me-1"></i>Approva</button>
    </form>
    <?php endif; ?>
    <?php if ($budget['stato'] === 'approvato'): ?>
    <a href="/rendiconto/scostamenti?anno=<?= $budget['anno'] ?>" class="btn btn-sm btn-outline-primary">
      <i class="bi bi-bar-chart me-1"></i>Scostamenti
    </a>
    <?php endif; ?>
  </div>
</div>

<!-- Riepilogo -->
<div class="row g-3 mb-3">
  <div class="col-md-3">
    <div class="card text-center py-2">
      <div class="text-muted small">Anno</div>
      <div class="h5 mb-0"><?= $budget['anno'] ?></div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card text-center py-2">
      <div class="text-muted small">Entrate previste</div>
      <div class="h5 mb-0 text-success">€ <?= $fmt($budget['tot_entrate']) ?></div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card text-center py-2">
      <div class="text-muted small">Uscite previste</div>
      <div class="h5 mb-0 text-danger">€ <?= $fmt($budget['tot_uscite']) ?></div>
    </div>
  </div>
  <div class="col-md-3">
    <?php $avanzo = $budget['tot_entrate'] - $budget['tot_uscite']; ?>
    <div class="card text-center py-2 <?= $avanzo>=0?'border-success':'border-danger' ?>">
      <div class="text-muted small">Avanzo previsto</div>
      <div class="h5 mb-0 <?= $avanzo>=0?'text-success':'text-danger' ?>"><?= $fmtS($avanzo) ?></div>
    </div>
  </div>
</div>

<!-- Dettaglio voci -->
<div class="card">
  <div class="table-responsive">
    <table class="table table-sm table-bordered mb-0" style="font-size:.85rem">
      <thead class="table-dark">
        <tr>
          <th>Cod.</th>
          <th>Voce</th>
          <th class="text-end">Importo Preventivato</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $sezioniOrg = [];
        foreach ($voci as $v) {
            $key = $v['sezione'] ?? 'altro';
            $sezioniOrg[$key][] = $v;
        }
        foreach ($sezioniOrg as $sez => $items):
            $isSez = strlen($sez) === 1 && ctype_alpha($sez);
        ?>
        <tr class="table-secondary">
          <td colspan="3" class="fw-bold">
            <?php if ($isSez): ?>
            Sezione <?= $sez ?> — <?= $sezLabel[$sez] ?? '' ?>
            <?php else: ?>
            <?= $e($sez) ?>
            <?php endif; ?>
          </td>
        </tr>
        <?php foreach ($items as $v): ?>
        <tr>
          <td class="text-muted small"><code><?= $e($v['codice_bilancio']) ?></code></td>
          <td><?= $e($v['voce_label']) ?></td>
          <td class="text-end fw-semibold <?= str_starts_with($v['codice_bilancio'],'E')||str_starts_with($v['codice_bilancio'],'DIS')?'text-success':'text-danger' ?>">
            € <?= $fmt($v['importo']) ?>
          </td>
        </tr>
        <?php endforeach; endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php if ($budget['note']): ?>
<div class="card mt-3">
  <div class="card-body small text-muted"><strong>Note:</strong> <?= $e($budget['note']) ?></div>
</div>
<?php endif; ?>
