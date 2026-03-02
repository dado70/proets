<?php
$e = fn($v) => htmlspecialchars((string)$v);
$sezioniMenu = [
  'azienda'  => ['icon'=>'building','label'=>'Associazione'],
  'utenti'   => ['icon'=>'people','label'=>'Utenti'],
  'email'    => ['icon'=>'envelope-at','label'=>'Email SMTP'],
  'quote-annuali'=>['icon'=>'credit-card-2-front','label'=>'Quote Annuali'],
  'gdpr'     => ['icon'=>'shield-check','label'=>'GDPR & Privacy'],
];
?>
<div class="row g-3">
  <div class="col-lg-2 col-md-3">
    <div class="card">
      <div class="card-body p-2">
        <?php foreach ($sezioniMenu as $slug => $item): ?>
        <a href="/configurazione/<?= $slug ?>" class="d-flex align-items-center gap-2 px-2 py-2 rounded text-decoration-none mb-1 <?= str_ends_with($_SERVER['REQUEST_URI']??'', $slug)?'bg-primary text-white':'text-dark' ?>">
          <i class="bi bi-<?= $item['icon'] ?>"></i><span class="small"><?= $item['label'] ?></span>
        </a>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <div class="col-lg-10 col-md-9">
    <?php if ($success ?? ''): ?><div class="alert alert-success py-2"><?= $e($success) ?></div><?php endif; ?>

    <!-- Impostazioni GDPR -->
    <div class="card mb-4">
      <div class="card-header fw-bold"><i class="bi bi-shield-check me-2 text-primary"></i>Impostazioni Privacy & GDPR</div>
      <div class="card-body">
        <form method="post" action="/configurazione/gdpr">
          <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label">Titolare del trattamento</label>
              <input type="text" name="gdpr_titolare" class="form-control" value="<?= $e($settings['gdpr_titolare'] ?? $company['ragione_sociale'] ?? '') ?>">
            </div>
            <div class="col-md-4">
              <label class="form-label">Email DPO / Privacy</label>
              <input type="email" name="gdpr_email_dpo" class="form-control" value="<?= $e($settings['gdpr_email_dpo'] ?? '') ?>" placeholder="privacy@example.com">
            </div>
            <div class="col-md-4">
              <label class="form-label">Conservazione dati soci (anni)</label>
              <input type="number" name="gdpr_retention_soci" class="form-control" value="<?= $e($settings['gdpr_retention_soci'] ?? 10) ?>" min="1" max="30">
              <div class="form-text">Dopo cessazione del rapporto associativo.</div>
            </div>
            <div class="col-md-4">
              <label class="form-label">Conservazione log audit (giorni)</label>
              <input type="number" name="gdpr_retention_log" class="form-control" value="<?= $e($settings['gdpr_retention_log'] ?? 365) ?>" min="30">
            </div>
            <div class="col-12">
              <label class="form-label">Informativa Privacy (testo breve)</label>
              <textarea name="gdpr_informativa" class="form-control" rows="4"><?= $e($settings['gdpr_informativa'] ?? '') ?></textarea>
              <div class="form-text">Mostrata nelle form di registrazione soci.</div>
            </div>
          </div>
          <button type="submit" class="btn btn-primary mt-3"><i class="bi bi-check2-circle me-2"></i>Salva</button>
        </form>
      </div>
    </div>

    <!-- Audit Log -->
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center fw-bold">
        <span><i class="bi bi-clock-history me-2 text-primary"></i>Registro Accessi (Audit Log)</span>
        <form method="post" action="/configurazione/gdpr" class="d-inline"
          onsubmit="return confirm('Eliminare i log più vecchi di '+document.getElementById(\'retention-days\').value+\' giorni?')">
          <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
          <input type="hidden" name="action" value="purge_log">
          <div class="input-group input-group-sm">
            <input type="number" name="giorni" id="retention-days" class="form-control" style="width:70px" value="<?= $e($settings['gdpr_retention_log'] ?? 365) ?>">
            <span class="input-group-text">gg</span>
            <button type="submit" class="btn btn-outline-danger">Pulisci</button>
          </div>
        </form>
      </div>
      <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
          <thead><tr><th>Data/Ora</th><th>Utente</th><th>Azione</th><th>Dettaglio</th><th>IP</th></tr></thead>
          <tbody>
            <?php if (empty($auditLog)): ?>
            <tr><td colspan="5" class="text-center text-muted py-4">Nessun evento nel log.</td></tr>
            <?php else: foreach ($auditLog as $log): ?>
            <tr>
              <td class="small text-nowrap text-muted"><?= date('d/m/Y H:i:s', strtotime($log['created_at'])) ?></td>
              <td class="small"><code><?= $e($log['username'] ?? 'sistema') ?></code></td>
              <td>
                <span class="badge bg-<?= match(true){
                  str_contains($log['azione'],'login')  => 'success',
                  str_contains($log['azione'],'delete') => 'danger',
                  str_contains($log['azione'],'error')  => 'warning',
                  default => 'secondary'
                } ?>-subtle text-<?= match(true){
                  str_contains($log['azione'],'login')  => 'success',
                  str_contains($log['azione'],'delete') => 'danger',
                  str_contains($log['azione'],'error')  => 'warning',
                  default => 'secondary'
                } ?>" style="font-size:.65rem"><?= $e($log['azione']) ?></span>
              </td>
              <td class="small text-muted"><?= $e($log['dettaglio'] ?? '') ?></td>
              <td class="small text-muted font-monospace"><?= $e($log['ip_address'] ?? '') ?></td>
            </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
      <?php if (!empty($auditLog) && count($auditLog) >= 100): ?>
      <div class="card-footer text-center text-muted small">Mostrati gli ultimi 100 eventi.</div>
      <?php endif; ?>
    </div>
  </div>
</div>
