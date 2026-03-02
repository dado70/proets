<?php
$tipoBadge = ['entrata'=>'success','uscita'=>'danger'];
$mesi = [''=>'Tutti i mesi','1'=>'Gennaio','2'=>'Febbraio','3'=>'Marzo','4'=>'Aprile','5'=>'Maggio','6'=>'Giugno','7'=>'Luglio','8'=>'Agosto','9'=>'Settembre','10'=>'Ottobre','11'=>'Novembre','12'=>'Dicembre'];
$anni = range(date('Y'), 2020);
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <div class="text-muted small">
    <?= $total ?> movimenti trovati &nbsp;|&nbsp;
    <span class="text-entrata fw-semibold">Entrate: € <?= number_format($totali['tot_entrate'],2,',','.') ?></span>
    &nbsp;|&nbsp;
    <span class="text-uscita fw-semibold">Uscite: € <?= number_format($totali['tot_uscite'],2,',','.') ?></span>
    &nbsp;|&nbsp;
    <strong>Saldo: € <?= number_format($totali['tot_entrate']-$totali['tot_uscite'],2,',','.') ?></strong>
  </div>
  <div class="d-flex gap-2">
    <a href="/prima-nota/import" class="btn btn-sm btn-outline-secondary"><i class="bi bi-cloud-upload me-1"></i>Importa</a>
    <a href="/prima-nota/export?anno=<?= $filtro['anno'] ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-download me-1"></i>CSV</a>
    <a href="/prima-nota/nuovo" class="btn btn-sm btn-primary"><i class="bi bi-plus me-1"></i>Nuovo Movimento</a>
  </div>
</div>

<!-- Filtri -->
<div class="card mb-3">
  <div class="card-body py-2">
    <form method="get" action="/prima-nota" class="row g-2 align-items-end">
      <div class="col-auto">
        <select name="anno" class="form-select form-select-sm" onchange="this.form.submit()">
          <?php foreach ($anni as $a): ?>
          <option value="<?= $a ?>" <?= $filtro['anno']==$a?'selected':'' ?>><?= $a ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-auto">
        <select name="mese" class="form-select form-select-sm" onchange="this.form.submit()">
          <?php foreach ($mesi as $v => $l): ?>
          <option value="<?= $v ?>" <?= $filtro['mese']==$v?'selected':'' ?>><?= $l ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-auto">
        <select name="account_id" class="form-select form-select-sm" onchange="this.form.submit()">
          <option value="">Tutti i conti</option>
          <?php foreach ($conti as $c): ?>
          <option value="<?= $c['id'] ?>" <?= $filtro['account_id']==$c['id']?'selected':'' ?>><?= htmlspecialchars($c['nome']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-auto">
        <select name="tipo" class="form-select form-select-sm" onchange="this.form.submit()">
          <option value="">Entrate e Uscite</option>
          <option value="entrata" <?= $filtro['tipo']==='entrata'?'selected':'' ?>>Solo Entrate</option>
          <option value="uscita"  <?= $filtro['tipo']==='uscita'?'selected':'' ?>>Solo Uscite</option>
        </select>
      </div>
      <div class="col">
        <input type="text" name="cerca" class="form-control form-control-sm" placeholder="Cerca descrizione / fornitore..." value="<?= htmlspecialchars($filtro['cerca']) ?>">
      </div>
      <div class="col-auto">
        <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-search"></i></button>
        <a href="/prima-nota" class="btn btn-sm btn-outline-secondary ms-1"><i class="bi bi-x"></i></a>
      </div>
    </form>
  </div>
</div>

<!-- Tabella -->
<div class="card">
  <div class="table-responsive">
    <table class="table table-hover table-sm mb-0">
      <thead>
        <tr>
          <th style="width:90px">Data</th>
          <th style="width:70px">Tipo</th>
          <th>Descrizione</th>
          <th style="width:100px">Causale</th>
          <th style="width:120px">Conto</th>
          <th style="width:110px" class="text-end">Importo</th>
          <th style="width:80px" class="text-center">Ric.</th>
          <th style="width:90px" class="text-center">Azioni</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($movimenti)): ?>
        <tr><td colspan="8" class="text-center text-muted py-5">
          <i class="bi bi-inbox display-6 d-block mb-2 text-muted"></i>
          Nessun movimento trovato.
          <a href="/prima-nota/nuovo" class="d-block mt-2">Registra il primo movimento</a>
        </td></tr>
        <?php else: ?>
        <?php foreach ($movimenti as $m): ?>
        <tr>
          <td class="text-nowrap small"><?= date('d/m/Y', strtotime($m['data_movimento'])) ?></td>
          <td>
            <span class="badge bg-<?= $tipoBadge[$m['tipo']] ?>-subtle text-<?= $tipoBadge[$m['tipo']] ?>" style="font-size:.7rem">
              <?= $m['tipo']==='entrata'?'Entrata':'Uscita' ?>
            </span>
          </td>
          <td>
            <div class="fw-semibold small"><?= htmlspecialchars($m['descrizione']) ?></div>
            <?php if ($m['fornitore_beneficiario']): ?>
            <div class="text-muted" style="font-size:.72rem"><?= htmlspecialchars($m['fornitore_beneficiario']) ?></div>
            <?php endif; ?>
          </td>
          <td>
            <span class="badge badge-causale bg-secondary-subtle text-secondary"><?= htmlspecialchars($m['codice_bilancio']) ?></span>
            <div style="font-size:.68rem;color:#888;margin-top:1px"><?= htmlspecialchars(mb_substr($m['causale_desc'],0,30)) ?></div>
          </td>
          <td class="small text-muted"><?= htmlspecialchars($m['conto_nome']) ?></td>
          <td class="text-end fw-bold <?= $m['tipo']==='entrata'?'text-entrata':'text-uscita' ?>">
            <?= $m['tipo']==='entrata'?'+':'-' ?>&nbsp;€&nbsp;<?= number_format($m['importo'],2,',','.') ?>
          </td>
          <td class="text-center">
            <?php if ($m['riconciliata']): ?>
            <i class="bi bi-check-circle-fill text-success" title="Riconciliata"></i>
            <?php else: ?>
            <i class="bi bi-circle text-muted" title="Non riconciliata"></i>
            <?php endif; ?>
          </td>
          <td class="text-center">
            <a href="/prima-nota/<?= $m['id'] ?>/modifica" class="btn btn-xs btn-outline-secondary btn-sm py-0 px-1" title="Modifica"><i class="bi bi-pencil"></i></a>
            <form method="post" action="/prima-nota/<?= $m['id'] ?>/annulla" class="d-inline" onsubmit="return confirm('Annullare questo movimento?')">
              <input type="hidden" name="_csrf" value="<?= \ProETS\Core\Session::csrf() ?>">
              <button type="submit" class="btn btn-xs btn-outline-danger btn-sm py-0 px-1" title="Annulla"><i class="bi bi-trash3"></i></button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  <!-- Paginazione -->
  <?php if ($pages > 1): ?>
  <div class="card-footer d-flex justify-content-between align-items-center py-2">
    <div class="text-muted small">Pagina <?= $page ?> di <?= $pages ?></div>
    <nav><ul class="pagination pagination-sm mb-0">
      <?php for ($p=max(1,$page-3); $p<=min($pages,$page+3); $p++): ?>
      <li class="page-item <?= $p==$page?'active':'' ?>">
        <a class="page-link" href="?<?= http_build_query(array_merge($filtro,['page'=>$p])) ?>"><?= $p ?></a>
      </li>
      <?php endfor; ?>
    </ul></nav>
  </div>
  <?php endif; ?>
</div>
