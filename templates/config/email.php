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
  <!-- Menu verticale -->
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

  <!-- Form Email -->
  <div class="col-lg-10 col-md-9">
    <?php if ($error ?? ''): ?><div class="alert alert-danger py-2"><?= $e($error) ?></div><?php endif; ?>
    <?php if ($success ?? ''): ?><div class="alert alert-success py-2"><?= $e($success) ?></div><?php endif; ?>

    <div class="card mb-4">
      <div class="card-header fw-bold"><i class="bi bi-envelope-at me-2 text-primary"></i>Configurazione Email SMTP</div>
      <div class="card-body">
        <form method="post" action="/configurazione/email">
          <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">

          <div class="row g-3">
            <div class="col-12">
              <div class="form-check form-switch">
                <input type="checkbox" name="smtp_enabled" id="smtp_enabled" class="form-check-input" value="1"
                  <?= !empty($settings['smtp_enabled'])?'checked':'' ?>
                  onchange="toggleSmtp(this.checked)">
                <label for="smtp_enabled" class="form-check-label fw-semibold">Usa SMTP (altrimenti usa mail() di PHP)</label>
              </div>
            </div>
          </div>

          <div id="smtp-fields" class="row g-3 mt-2" style="<?= empty($settings['smtp_enabled'])?'display:none':'' ?>">
            <div class="col-md-6">
              <label class="form-label">Host SMTP <span class="text-danger">*</span></label>
              <input type="text" name="smtp_host" class="form-control" value="<?= $e($settings['smtp_host'] ?? '') ?>" placeholder="smtp.gmail.com">
            </div>
            <div class="col-md-2">
              <label class="form-label">Porta</label>
              <input type="number" name="smtp_port" class="form-control" value="<?= $e($settings['smtp_port'] ?? 587) ?>">
            </div>
            <div class="col-md-4">
              <label class="form-label">Sicurezza</label>
              <select name="smtp_secure" class="form-select">
                <option value="tls" <?= ($settings['smtp_secure']??'tls')==='tls'?'selected':'' ?>>STARTTLS (porta 587)</option>
                <option value="ssl" <?= ($settings['smtp_secure']??'')==='ssl'?'selected':'' ?>>SSL (porta 465)</option>
                <option value=""    <?= ($settings['smtp_secure']??'')=== ''?'selected':'' ?>>Nessuna</option>
              </select>
            </div>
            <div class="col-md-5">
              <label class="form-label">Utente SMTP</label>
              <input type="text" name="smtp_user" class="form-control" value="<?= $e($settings['smtp_user'] ?? '') ?>" placeholder="user@example.com">
            </div>
            <div class="col-md-4">
              <label class="form-label">Password SMTP</label>
              <input type="password" name="smtp_pass" class="form-control" placeholder="Lascia vuoto per non cambiare">
            </div>
          </div>

          <hr class="my-3">
          <div class="row g-3">
            <div class="col-md-5">
              <label class="form-label">Nome mittente</label>
              <input type="text" name="smtp_from_name" class="form-control" value="<?= $e($settings['smtp_from_name'] ?? $company['ragione_sociale'] ?? '') ?>">
            </div>
            <div class="col-md-5">
              <label class="form-label">Email mittente</label>
              <input type="email" name="smtp_from" class="form-control" value="<?= $e($settings['smtp_from'] ?? $company['email'] ?? '') ?>">
            </div>
            <div class="col-md-5">
              <label class="form-label">Email risposta (Reply-To)</label>
              <input type="email" name="smtp_reply_to" class="form-control" value="<?= $e($settings['smtp_reply_to'] ?? '') ?>" placeholder="Lascia vuoto per usare mittente">
            </div>
          </div>

          <hr class="my-3">
          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary"><i class="bi bi-check2-circle me-2"></i>Salva configurazione</button>
            <button type="submit" name="test" value="1" class="btn btn-outline-secondary">
              <i class="bi bi-send me-2"></i>Invia email di test
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Template email -->
    <div class="card">
      <div class="card-header fw-bold"><i class="bi bi-file-text me-2 text-primary"></i>Template Email</div>
      <div class="table-responsive">
        <table class="table table-sm mb-0">
          <thead><tr><th>Template</th><th>Oggetto</th><th class="text-center">Azioni</th></tr></thead>
          <tbody>
            <?php foreach ($templates as $t): ?>
            <tr>
              <td><code><?= $e($t['codice']) ?></code></td>
              <td class="small"><?= $e($t['oggetto']) ?></td>
              <td class="text-center">
                <a href="/configurazione/email/template/<?= $e($t['codice']) ?>" class="btn btn-sm btn-outline-secondary py-0 px-2">
                  <i class="bi bi-pencil"></i>
                </a>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<script>
function toggleSmtp(enabled) {
  document.getElementById('smtp-fields').style.display = enabled ? '' : 'none';
}
</script>
