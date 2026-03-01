<?php
$e   = fn($v) => htmlspecialchars((string)$v);
$fmt = fn($v) => number_format((float)$v,2,',','.');
$statoBadge = ['bozza'=>'secondary','approvato'=>'success','archiviato'=>'dark'];
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <div class="d-flex gap-2 align-items-center">
    <select class="form-select form-select-sm" style="width:100px" onchange="location.href='/budget?anno='+this.value">
      <?php foreach ($anni as $a): ?>
      <option value="<?= $a ?>" <?= $a==$esercizio?'selected':'' ?>><?= $a ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <a href="/budget/nuovo" class="btn btn-sm btn-primary"><i class="bi bi-plus me-1"></i>Nuovo Preventivo</a>
</div>

<?php if (empty($budgets)): ?>
<div class="card">
  <div class="card-body text-center py-5 text-muted">
    <i class="bi bi-calculator display-5 d-block mb-2"></i>
    Nessun preventivo per <?= $esercizio ?>.<br>
    <a href="/budget/nuovo" class="mt-2 d-inline-block">Crea il preventivo <?= $esercizio ?></a>
  </div>
</div>
<?php else: ?>
<div class="row g-3">
  <?php foreach ($budgets as $b):
    $sCls = $statoBadge[$b['stato']] ?? 'secondary';
    $totPrev = $b['tot_entrate'] - $b['tot_uscite'];
  ?>
  <div class="col-md-6 col-xl-4">
    <div class="card h-100">
      <div class="card-header d-flex align-items-center justify-content-between">
        <span class="fw-bold"><?= $e($b['nome']) ?></span>
        <span class="badge bg-<?= $sCls ?>-subtle text-<?= $sCls ?>"><?= ucfirst($b['stato']) ?></span>
      </div>
      <div class="card-body small">
        <div class="row text-center mb-2">
          <div class="col">
            <div class="text-success fw-bold">€ <?= $fmt($b['tot_entrate']) ?></div>
            <div class="text-muted">Entrate prev.</div>
          </div>
          <div class="col">
            <div class="text-danger fw-bold">€ <?= $fmt($b['tot_uscite']) ?></div>
            <div class="text-muted">Uscite prev.</div>
          </div>
          <div class="col">
            <div class="fw-bold <?= $totPrev>=0?'text-success':'text-danger' ?>">
              <?= ($totPrev>=0?'+':'').'' ?>€ <?= $fmt(abs($totPrev)) ?>
            </div>
            <div class="text-muted">Avanzo prev.</div>
          </div>
        </div>
        <div class="mb-1"><strong>Approvato il:</strong> <?= $b['data_approvazione'] ? date('d/m/Y', strtotime($b['data_approvazione'])) : '-' ?></div>
        <?php if ($b['note']): ?><div class="text-muted"><?= $e($b['note']) ?></div><?php endif; ?>
      </div>
      <div class="card-footer py-1 d-flex gap-1">
        <a href="/budget/<?= $b['id'] ?>" class="btn btn-sm btn-outline-secondary flex-fill"><i class="bi bi-eye me-1"></i>Vedi</a>
        <?php if ($b['stato'] === 'bozza'): ?>
        <a href="/budget/<?= $b['id'] ?>/modifica" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
        <form method="post" action="/budget/<?= $b['id'] ?>/approva" class="d-inline"
          onsubmit="return confirm('Approvare il preventivo? Non sarà più modificabile.')">
          <input type="hidden" name="_csrf" value="<?= \ProETS\Core\Session::csrf() ?>">
          <button type="submit" class="btn btn-sm btn-success"><i class="bi bi-check2-circle me-1"></i>Approva</button>
        </form>
        <?php endif; ?>
        <a href="/budget/<?= $b['id'] ?>/pdf" class="btn btn-sm btn-outline-danger" title="PDF"><i class="bi bi-file-earmark-pdf"></i></a>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>
