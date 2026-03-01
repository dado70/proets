<?php
$tipoIcon = ['locale'=>'hdd','ftp'=>'server','sftp'=>'shield-lock','webdav'=>'cloud','nextcloud'=>'cloud-check','googledrive'=>'google','dropbox'=>'dropbox'];
$statoBadge = ['ok'=>'success','errore'=>'danger','mai_eseguito'=>'secondary'];
?>
<div class="d-flex justify-content-end mb-3">
  <a href="/backup/configurazione" class="btn btn-sm btn-primary"><i class="bi bi-plus me-1"></i>Nuova Configurazione</a>
</div>

<!-- Configurazioni -->
<div class="row g-3 mb-4">
  <?php if (empty($configs)): ?>
  <div class="col-12">
    <div class="alert alert-info">
      <i class="bi bi-info-circle me-2"></i>
      Nessuna configurazione di backup. <a href="/backup/configurazione">Configura il primo backup</a>.
    </div>
  </div>
  <?php else: ?>
  <?php foreach ($configs as $c):
    $ico = $tipoIcon[$c['tipo']] ?? 'cloud';
    $statoCls = $statoBadge[$c['ultimo_stato']] ?? 'secondary';
  ?>
  <div class="col-md-6 col-xl-4">
    <div class="card h-100">
      <div class="card-header d-flex align-items-center justify-content-between">
        <span><i class="bi bi-<?= $ico ?> me-2 text-primary"></i><?= htmlspecialchars($c['nome']) ?></span>
        <span class="badge bg-<?= $c['attivo']?'success':'secondary' ?>-subtle text-<?= $c['attivo']?'success':'secondary' ?>"><?= $c['attivo']?'Attivo':'Disabilitato' ?></span>
      </div>
      <div class="card-body small">
        <div class="mb-1"><strong>Tipo:</strong> <?= strtoupper($c['tipo']) ?></div>
        <div class="mb-1"><strong>Frequenza:</strong> <?= ucfirst($c['frequenza']) ?> alle <?= substr($c['ora_esecuzione'],0,5) ?></div>
        <div class="mb-1"><strong>Rotazione:</strong> <?= $c['rotazione_giorni'] ?> giorni</div>
        <div class="mb-1">
          <strong>Ultimo backup:</strong>
          <?= $c['ultimo_backup'] ? date('d/m/Y H:i', strtotime($c['ultimo_backup'])) : 'Mai eseguito' ?>
          <span class="badge bg-<?= $statoCls ?>-subtle text-<?= $statoCls ?>" style="font-size:.65rem"><?= strtoupper($c['ultimo_stato']) ?></span>
        </div>
        <?php if ($c['ultimo_messaggio']): ?>
        <div class="text-muted" style="font-size:.72rem"><?= htmlspecialchars($c['ultimo_messaggio']) ?></div>
        <?php endif; ?>
      </div>
      <div class="card-footer py-1 d-flex gap-1">
        <form method="post" action="/backup/esegui" class="flex-fill">
          <input type="hidden" name="_csrf" value="<?= \ProETS\Core\Session::csrf() ?>">
          <input type="hidden" name="config_id" value="<?= $c['id'] ?>">
          <button type="submit" class="btn btn-sm btn-success w-100" <?= !$c['attivo']?'disabled':'' ?>>
            <i class="bi bi-play me-1"></i>Esegui ora
          </button>
        </form>
        <a href="/backup/configurazione?edit=<?= $c['id'] ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-gear"></i></a>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
  <?php endif; ?>
</div>

<!-- Storico -->
<div class="card">
  <div class="card-header"><i class="bi bi-clock-history me-2"></i>Storico Backup</div>
  <div class="table-responsive">
    <table class="table table-sm table-hover mb-0">
      <thead><tr><th>Data</th><th>File</th><th>Destinazione</th><th class="text-end">Dimensione</th><th class="text-center">Stato</th><th class="text-center">Azioni</th></tr></thead>
      <tbody>
        <?php if (empty($history)): ?>
        <tr><td colspan="6" class="text-center text-muted py-4">Nessun backup nello storico.</td></tr>
        <?php else: ?>
        <?php foreach ($history as $h): ?>
        <tr>
          <td class="small text-nowrap"><?= date('d/m/Y H:i', strtotime($h['created_at'])) ?></td>
          <td class="small"><code><?= htmlspecialchars($h['filename']) ?></code></td>
          <td class="small text-muted"><?= htmlspecialchars($h['config_nome']) ?> (<?= strtoupper($h['config_tipo']) ?>)</td>
          <td class="text-end small"><?= $h['dimensione'] ? number_format($h['dimensione']/1024,1) . ' KB' : '-' ?></td>
          <td class="text-center">
            <span class="badge bg-<?= $h['stato']==='ok'?'success':'danger' ?>-subtle text-<?= $h['stato']==='ok'?'success':'danger' ?>"><?= strtoupper($h['stato']) ?></span>
          </td>
          <td class="text-center d-flex gap-1 justify-content-center">
            <?php if ($h['stato']==='ok'): ?>
            <a href="/backup/download/<?= $h['id'] ?>" class="btn btn-xs btn-sm btn-outline-secondary py-0 px-1" title="Scarica"><i class="bi bi-download"></i></a>
            <form method="post" action="/backup/ripristina" class="d-inline" onsubmit="return confirm('ATTENZIONE: Il ripristino sovrascriverà TUTTI i dati correnti. Continuare?')">
              <input type="hidden" name="_csrf" value="<?= \ProETS\Core\Session::csrf() ?>">
              <input type="hidden" name="history_id" value="<?= $h['id'] ?>">
              <button type="submit" class="btn btn-xs btn-sm btn-outline-warning py-0 px-1" title="Ripristina"><i class="bi bi-arrow-counterclockwise"></i></button>
            </form>
            <?php endif; ?>
            <form method="post" action="/backup/elimina/<?= $h['id'] ?>" class="d-inline" onsubmit="return confirm('Eliminare questo backup?')">
              <input type="hidden" name="_csrf" value="<?= \ProETS\Core\Session::csrf() ?>">
              <button type="submit" class="btn btn-xs btn-sm btn-outline-danger py-0 px-1" title="Elimina"><i class="bi bi-trash3"></i></button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
