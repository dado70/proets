<?php
$tipoBadge = ['ordinario'=>'primary','fondatore'=>'dark','onorario'=>'info','sostenitore'=>'warning'];
$quotaBadge = ['pagata'=>'success','parziale'=>'warning','attesa'=>'danger','esonerata'=>'secondary'];
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <div class="text-muted small"><?= $totale ?> soci trovati</div>
  <div class="d-flex gap-2">
    <a href="/soci/export" class="btn btn-sm btn-outline-secondary"><i class="bi bi-download me-1"></i>CSV</a>
    <a href="/soci/nuovo" class="btn btn-sm btn-primary"><i class="bi bi-person-plus me-1"></i>Nuovo Socio</a>
  </div>
</div>

<!-- Filtri -->
<div class="card mb-3">
  <div class="card-body py-2">
    <form method="get" class="row g-2 align-items-end">
      <div class="col-auto">
        <select name="stato" class="form-select form-select-sm" onchange="this.form.submit()">
          <option value="tutti" <?= $stato==='tutti'?'selected':'' ?>>Tutti</option>
          <option value="attivi" <?= $stato==='attivi'?'selected':'' ?>>Attivi</option>
          <option value="cessati" <?= $stato==='cessati'?'selected':'' ?>>Cessati</option>
        </select>
      </div>
      <div class="col-auto">
        <select name="tipo_socio" class="form-select form-select-sm" onchange="this.form.submit()">
          <option value="">Tutti i tipi</option>
          <option value="ordinario" <?= $tipo==='ordinario'?'selected':'' ?>>Ordinario</option>
          <option value="fondatore" <?= $tipo==='fondatore'?'selected':'' ?>>Fondatore</option>
          <option value="onorario"  <?= $tipo==='onorario'?'selected':'' ?>>Onorario</option>
          <option value="sostenitore" <?= $tipo==='sostenitore'?'selected':'' ?>>Sostenitore</option>
        </select>
      </div>
      <div class="col">
        <input type="text" name="cerca" class="form-control form-control-sm" placeholder="Cerca nome, email, CF, tessera..." value="<?= htmlspecialchars($cerca) ?>">
      </div>
      <div class="col-auto">
        <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-search"></i></button>
        <a href="/soci" class="btn btn-sm btn-outline-secondary ms-1"><i class="bi bi-x"></i></a>
      </div>
    </form>
  </div>
</div>

<div class="card">
  <div class="table-responsive">
    <table class="table table-hover table-sm mb-0">
      <thead>
        <tr>
          <th style="width:80px">Tessera</th>
          <th>Cognome e Nome</th>
          <th>Email</th>
          <th>Tipo</th>
          <th>Iscrizione</th>
          <th class="text-center">Quota Anno</th>
          <th style="width:100px" class="text-center">Azioni</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($soci)): ?>
        <tr><td colspan="7" class="text-center py-5 text-muted">
          <i class="bi bi-people display-5 d-block mb-2"></i>
          Nessun socio trovato.
          <a href="/soci/nuovo" class="d-block mt-2">Registra il primo socio</a>
        </td></tr>
        <?php else: ?>
        <?php foreach ($soci as $s): ?>
        <tr class="<?= !$s['attivo']?'text-muted':'' ?>">
          <td><code class="small"><?= htmlspecialchars($s['numero_tessera'] ?? '-') ?></code></td>
          <td>
            <a href="/soci/<?= $s['id'] ?>" class="fw-semibold text-decoration-none">
              <?= htmlspecialchars($s['cognome'] . ' ' . $s['nome']) ?>
            </a>
            <?php if (!$s['attivo']): ?><span class="badge bg-secondary ms-1" style="font-size:.6rem">Cessato</span><?php endif; ?>
          </td>
          <td class="small text-muted"><?= htmlspecialchars($s['email'] ?? '') ?></td>
          <td><span class="badge bg-<?= $tipoBadge[$s['tipo_socio']] ?? 'secondary' ?>-subtle text-<?= $tipoBadge[$s['tipo_socio']] ?? 'secondary' ?>"><?= ucfirst($s['tipo_socio']) ?></span></td>
          <td class="small"><?= $s['data_iscrizione'] ? date('d/m/Y', strtotime($s['data_iscrizione'])) : '-' ?></td>
          <td class="text-center">
            <?php if ($s['stato_quota']): ?>
            <span class="badge bg-<?= $quotaBadge[$s['stato_quota']] ?? 'secondary' ?>-subtle text-<?= $quotaBadge[$s['stato_quota']] ?? 'secondary' ?>" style="font-size:.7rem">
              <?= ucfirst($s['stato_quota']) ?>
            </span>
            <?php else: ?>
            <span class="text-muted" style="font-size:.75rem">-</span>
            <?php endif; ?>
          </td>
          <td class="text-center">
            <a href="/soci/<?= $s['id'] ?>" class="btn btn-sm btn-outline-secondary py-0 px-1" title="Dettaglio"><i class="bi bi-eye"></i></a>
            <a href="/soci/<?= $s['id'] ?>/modifica" class="btn btn-sm btn-outline-primary py-0 px-1" title="Modifica"><i class="bi bi-pencil"></i></a>
            <a href="/soci/<?= $s['id'] ?>/tessera" class="btn btn-sm btn-outline-secondary py-0 px-1" title="Tessera PDF"><i class="bi bi-card-text"></i></a>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
