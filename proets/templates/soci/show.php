<?php
$e   = fn($v) => htmlspecialchars((string)$v);
$tipoBadge  = ['ordinario'=>'primary','fondatore'=>'dark','onorario'=>'info','sostenitore'=>'warning'];
$quotaBadge = ['pagata'=>'success','parziale'=>'warning','attesa'=>'danger','esonerata'=>'secondary'];
$tCls = $tipoBadge[$socio['tipo_socio']] ?? 'secondary';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <div class="d-flex align-items-center gap-2">
    <a href="/soci" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <h5 class="mb-0"><?= $e($socio['cognome'].' '.$socio['nome']) ?></h5>
    <span class="badge bg-<?= $tCls ?>-subtle text-<?= $tCls ?>"><?= ucfirst($socio['tipo_socio']) ?></span>
    <?php if (!$socio['attivo']): ?><span class="badge bg-secondary">Cessato</span><?php endif; ?>
  </div>
  <div class="d-flex gap-2">
    <a href="/soci/<?= $socio['id'] ?>/tessera" class="btn btn-sm btn-outline-secondary"><i class="bi bi-card-text me-1"></i>Tessera PDF</a>
    <a href="/soci/<?= $socio['id'] ?>/modifica" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil me-1"></i>Modifica</a>
  </div>
</div>

<div class="row g-3">
  <!-- Anagrafica -->
  <div class="col-md-6">
    <div class="card h-100">
      <div class="card-header fw-bold"><i class="bi bi-person me-2 text-primary"></i>Anagrafica</div>
      <div class="card-body small">
        <dl class="row mb-0">
          <dt class="col-5">N. Tessera</dt><dd class="col-7"><code><?= $e($socio['numero_tessera'] ?? '-') ?></code></dd>
          <dt class="col-5">Codice Fiscale</dt><dd class="col-7"><?= $e($socio['codice_fiscale'] ?? '-') ?></dd>
          <dt class="col-5">Data Nascita</dt><dd class="col-7"><?= $socio['data_nascita'] ? date('d/m/Y', strtotime($socio['data_nascita'])) : '-' ?></dd>
          <dt class="col-5">Luogo Nascita</dt><dd class="col-7"><?= $e($socio['luogo_nascita'] ?? '-') ?></dd>
          <dt class="col-5">Sesso</dt><dd class="col-7"><?= $e($socio['sesso'] ?? '-') ?></dd>
          <dt class="col-5">Nazionalità</dt><dd class="col-7"><?= $e($socio['nazionalita'] ?? 'IT') ?></dd>
        </dl>
      </div>
    </div>
  </div>

  <!-- Contatti -->
  <div class="col-md-6">
    <div class="card h-100">
      <div class="card-header fw-bold"><i class="bi bi-envelope me-2 text-primary"></i>Contatti</div>
      <div class="card-body small">
        <dl class="row mb-0">
          <dt class="col-4">Email</dt><dd class="col-8"><?= $socio['email'] ? '<a href="mailto:'.$e($socio['email']).'">'.$e($socio['email']).'</a>' : '-' ?></dd>
          <dt class="col-4">Telefono</dt><dd class="col-8"><?= $e($socio['telefono'] ?? '-') ?></dd>
          <dt class="col-4">Indirizzo</dt><dd class="col-8"><?= $e(trim(($socio['indirizzo'] ?? '').' '.($socio['cap'] ?? '').' '.($socio['citta'] ?? '').' '.($socio['provincia'] ?? ''))) ?: '-' ?></dd>
        </dl>
      </div>
    </div>
  </div>

  <!-- Dati Associativi -->
  <div class="col-md-6">
    <div class="card h-100">
      <div class="card-header fw-bold"><i class="bi bi-people me-2 text-primary"></i>Dati Associativi</div>
      <div class="card-body small">
        <dl class="row mb-0">
          <dt class="col-5">Iscrizione</dt><dd class="col-7"><?= $socio['data_iscrizione'] ? date('d/m/Y', strtotime($socio['data_iscrizione'])) : '-' ?></dd>
          <dt class="col-5">Tipo</dt><dd class="col-7"><span class="badge bg-<?= $tCls ?>-subtle text-<?= $tCls ?>"><?= ucfirst($socio['tipo_socio']) ?></span></dd>
          <dt class="col-5">Quota</dt><dd class="col-7"><?= !empty($socio['quota_esonerata']) ? '<span class="badge bg-secondary-subtle text-secondary">Esonerata</span>' : '<span class="text-muted">Normale</span>' ?></dd>
          <?php if (!$socio['attivo']): ?>
          <dt class="col-5">Cessazione</dt><dd class="col-7"><?= $socio['data_cessazione'] ? date('d/m/Y', strtotime($socio['data_cessazione'])) : '-' ?></dd>
          <?php endif; ?>
        </dl>
        <?php if ($socio['note']): ?>
        <hr class="my-2">
        <div class="text-muted"><?= nl2br($e($socio['note'])) ?></div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- GDPR -->
  <div class="col-md-6">
    <div class="card h-100">
      <div class="card-header fw-bold"><i class="bi bi-shield-check me-2 text-primary"></i>Consensi GDPR</div>
      <div class="card-body small">
        <?php
        $consensi = [
            'consenso_privacy'        => 'Trattamento dati',
            'consenso_comunicazioni'  => 'Comunicazioni',
            'consenso_foto'           => 'Foto/Video',
        ];
        foreach ($consensi as $field => $label): ?>
        <div class="d-flex align-items-center gap-2 mb-1">
          <i class="bi bi-<?= !empty($socio[$field]) ? 'check-circle-fill text-success' : 'x-circle text-danger' ?>"></i>
          <span><?= $label ?></span>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <!-- Quote -->
  <div class="col-12">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center fw-bold">
        <span><i class="bi bi-credit-card me-2 text-primary"></i>Storico Quote</span>
        <a href="/soci/<?= $socio['id'] ?>/quote" class="btn btn-sm btn-outline-primary">Gestisci quote</a>
      </div>
      <div class="table-responsive">
        <table class="table table-sm mb-0">
          <thead><tr><th>Anno</th><th class="text-end">Importo</th><th class="text-end">Pagato</th><th>Stato</th><th>Pagato il</th><th>Metodo</th></tr></thead>
          <tbody>
            <?php if (empty($quote)): ?>
            <tr><td colspan="6" class="text-center text-muted py-3">Nessuna quota registrata.</td></tr>
            <?php else: foreach ($quote as $q): $qCls = $quotaBadge[$q['stato']] ?? 'secondary'; ?>
            <tr>
              <td><strong><?= $q['anno'] ?></strong></td>
              <td class="text-end">€ <?= number_format($q['importo'],2,',','.') ?></td>
              <td class="text-end">€ <?= number_format($q['importo_pagato'],2,',','.') ?></td>
              <td><span class="badge bg-<?= $qCls ?>-subtle text-<?= $qCls ?>"><?= ucfirst($q['stato']) ?></span></td>
              <td class="small text-muted"><?= $q['data_pagamento'] ? date('d/m/Y', strtotime($q['data_pagamento'])) : '-' ?></td>
              <td class="small text-muted"><?= $e($q['metodo_pagamento'] ?? '-') ?></td>
            </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Comunicazioni -->
  <?php if (!empty($comunicazioni)): ?>
  <div class="col-12">
    <div class="card">
      <div class="card-header fw-bold"><i class="bi bi-chat-dots me-2 text-primary"></i>Comunicazioni Inviate</div>
      <div class="table-responsive">
        <table class="table table-sm mb-0">
          <thead><tr><th>Data</th><th>Oggetto</th><th>Canale</th><th>Stato</th></tr></thead>
          <tbody>
            <?php foreach ($comunicazioni as $cm): ?>
            <tr>
              <td class="small text-muted text-nowrap"><?= date('d/m/Y H:i', strtotime($cm['created_at'])) ?></td>
              <td class="small"><?= $e($cm['oggetto']) ?></td>
              <td><span class="badge bg-secondary-subtle text-secondary" style="font-size:.65rem"><?= strtoupper($cm['canale']) ?></span></td>
              <td><span class="badge bg-<?= $cm['stato']==='inviata'?'success':'warning' ?>-subtle text-<?= $cm['stato']==='inviata'?'success':'warning' ?>" style="font-size:.65rem"><?= ucfirst($cm['stato']) ?></span></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <?php endif; ?>
</div>
