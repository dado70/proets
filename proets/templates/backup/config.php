<?php
$e    = fn($v) => htmlspecialchars((string)$v);
$isEdit = isset($config['id']);
$tipo = $config['tipo'] ?? 'locale';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h5 class="mb-0"><?= $isEdit ? 'Modifica Configurazione Backup' : 'Nuova Configurazione Backup' ?></h5>
  <a href="/backup" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Torna ai backup</a>
</div>

<form method="post" action="<?= $isEdit ? '/backup/configurazione?edit='.$config['id'] : '/backup/configurazione' ?>">
  <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">

  <!-- Impostazioni generali -->
  <div class="card mb-3">
    <div class="card-header fw-bold"><i class="bi bi-gear me-2 text-primary"></i>Impostazioni Generali</div>
    <div class="card-body">
      <div class="row g-3">
        <div class="col-md-5">
          <label class="form-label">Nome configurazione <span class="text-danger">*</span></label>
          <input type="text" name="nome" class="form-control" value="<?= $e($config['nome'] ?? '') ?>" required placeholder="es. Backup Notturno">
        </div>
        <div class="col-md-3">
          <label class="form-label">Tipo Destinazione <span class="text-danger">*</span></label>
          <select name="tipo" class="form-select" id="tipo-select" onchange="mostraSezione()">
            <option value="locale" <?= $tipo==='locale'?'selected':'' ?>>Locale (server)</option>
            <option value="ftp"    <?= $tipo==='ftp'?'selected':'' ?>>FTP</option>
            <option value="sftp"   <?= $tipo==='sftp'?'selected':'' ?>>SFTP</option>
            <option value="webdav" <?= $tipo==='webdav'?'selected':'' ?>>WebDAV</option>
            <option value="nextcloud" <?= $tipo==='nextcloud'?'selected':'' ?>>Nextcloud</option>
            <option value="googledrive" <?= $tipo==='googledrive'?'selected':'' ?>>Google Drive</option>
            <option value="dropbox" <?= $tipo==='dropbox'?'selected':'' ?>>Dropbox</option>
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label">Frequenza</label>
          <select name="frequenza" class="form-select">
            <option value="giornaliero" <?= ($config['frequenza']??'')==='giornaliero'?'selected':'' ?>>Giornaliero</option>
            <option value="settimanale" <?= ($config['frequenza']??'')==='settimanale'?'selected':'' ?>>Settimanale</option>
            <option value="mensile"     <?= ($config['frequenza']??'')==='mensile'?'selected':'' ?>>Mensile</option>
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label">Ora esecuzione</label>
          <input type="time" name="ora_esecuzione" class="form-control" value="<?= $e(substr($config['ora_esecuzione'] ?? '02:00',0,5)) ?>">
        </div>
        <div class="col-md-3">
          <label class="form-label">Rotazione (giorni)</label>
          <input type="number" name="rotazione_giorni" class="form-control" value="<?= $e($config['rotazione_giorni'] ?? 30) ?>" min="1" max="365">
        </div>
        <div class="col-md-3">
          <div class="form-check mt-4">
            <input type="checkbox" name="attivo" id="attivo" class="form-check-input" value="1" <?= !empty($config['attivo'])?'checked':'' ?>>
            <label for="attivo" class="form-check-label">Abilitato</label>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Sezione Locale -->
  <div class="card mb-3 sezione-tipo" id="sez-locale" style="<?= $tipo!=='locale'?'display:none':'' ?>">
    <div class="card-header fw-bold"><i class="bi bi-hdd me-2"></i>Percorso Locale</div>
    <div class="card-body">
      <div class="col-md-8">
        <label class="form-label">Cartella di destinazione</label>
        <input type="text" name="locale_path" class="form-control" value="<?= $e($config['parametri']['locale_path'] ?? '../backups/') ?>" placeholder="/var/backups/proets/">
        <div class="form-text">Il percorso deve essere scrivibile dal web server. Relativo a /public/.</div>
      </div>
    </div>
  </div>

  <!-- Sezione FTP -->
  <div class="card mb-3 sezione-tipo" id="sez-ftp" style="<?= $tipo!=='ftp'?'display:none':'' ?>">
    <div class="card-header fw-bold"><i class="bi bi-server me-2"></i>Configurazione FTP</div>
    <div class="card-body">
      <div class="row g-3">
        <div class="col-md-6"><label class="form-label">Host</label><input type="text" name="ftp_host" class="form-control" value="<?= $e($config['parametri']['ftp_host'] ?? '') ?>" placeholder="ftp.example.com"></div>
        <div class="col-md-2"><label class="form-label">Porta</label><input type="number" name="ftp_port" class="form-control" value="<?= $e($config['parametri']['ftp_port'] ?? 21) ?>"></div>
        <div class="col-md-4"><label class="form-label">Percorso remoto</label><input type="text" name="ftp_path" class="form-control" value="<?= $e($config['parametri']['ftp_path'] ?? '/backups/') ?>"></div>
        <div class="col-md-4"><label class="form-label">Utente</label><input type="text" name="ftp_user" class="form-control" value="<?= $e($config['parametri']['ftp_user'] ?? '') ?>"></div>
        <div class="col-md-4"><label class="form-label">Password</label><input type="password" name="ftp_pass" class="form-control" placeholder="<?= $isEdit?'Lascia vuoto per non cambiare':'' ?>"></div>
        <div class="col-md-4"><div class="form-check mt-4"><input type="checkbox" name="ftp_ssl" id="ftp_ssl" class="form-check-input" value="1" <?= !empty($config['parametri']['ftp_ssl'])?'checked':'' ?>><label for="ftp_ssl" class="form-check-label">Usa FTP-SSL</label></div></div>
      </div>
    </div>
  </div>

  <!-- Sezione SFTP -->
  <div class="card mb-3 sezione-tipo" id="sez-sftp" style="<?= $tipo!=='sftp'?'display:none':'' ?>">
    <div class="card-header fw-bold"><i class="bi bi-shield-lock me-2"></i>Configurazione SFTP</div>
    <div class="card-body">
      <div class="row g-3">
        <div class="col-md-6"><label class="form-label">Host</label><input type="text" name="sftp_host" class="form-control" value="<?= $e($config['parametri']['sftp_host'] ?? '') ?>"></div>
        <div class="col-md-2"><label class="form-label">Porta</label><input type="number" name="sftp_port" class="form-control" value="<?= $e($config['parametri']['sftp_port'] ?? 22) ?>"></div>
        <div class="col-md-4"><label class="form-label">Percorso remoto</label><input type="text" name="sftp_path" class="form-control" value="<?= $e($config['parametri']['sftp_path'] ?? '/home/user/backups/') ?>"></div>
        <div class="col-md-4"><label class="form-label">Utente</label><input type="text" name="sftp_user" class="form-control" value="<?= $e($config['parametri']['sftp_user'] ?? '') ?>"></div>
        <div class="col-md-4"><label class="form-label">Password</label><input type="password" name="sftp_pass" class="form-control" placeholder="<?= $isEdit?'Lascia vuoto per non cambiare':'' ?>"></div>
        <div class="col-md-4"><label class="form-label">Chiave privata (path)</label><input type="text" name="sftp_key" class="form-control" value="<?= $e($config['parametri']['sftp_key'] ?? '') ?>" placeholder="/home/user/.ssh/id_rsa"></div>
      </div>
    </div>
  </div>

  <!-- Sezione WebDAV / Nextcloud -->
  <div class="card mb-3 sezione-tipo" id="sez-webdav" style="<?= !in_array($tipo,['webdav','nextcloud'])?'display:none':'' ?>">
    <div class="card-header fw-bold"><i class="bi bi-cloud me-2"></i>Configurazione WebDAV / Nextcloud</div>
    <div class="card-body">
      <div class="row g-3">
        <div class="col-md-8"><label class="form-label">URL WebDAV</label><input type="url" name="webdav_url" class="form-control" value="<?= $e($config['parametri']['webdav_url'] ?? '') ?>" placeholder="https://cloud.example.com/remote.php/dav/files/user/backups/"></div>
        <div class="col-md-4"><label class="form-label">Utente</label><input type="text" name="webdav_user" class="form-control" value="<?= $e($config['parametri']['webdav_user'] ?? '') ?>"></div>
        <div class="col-md-4"><label class="form-label">Password</label><input type="password" name="webdav_pass" class="form-control" placeholder="<?= $isEdit?'Lascia vuoto per non cambiare':'' ?>"></div>
      </div>
    </div>
  </div>

  <!-- Google Drive -->
  <div class="card mb-3 sezione-tipo" id="sez-googledrive" style="<?= $tipo!=='googledrive'?'display:none':'' ?>">
    <div class="card-header fw-bold"><i class="bi bi-google me-2"></i>Google Drive</div>
    <div class="card-body">
      <div class="row g-3">
        <div class="col-md-6"><label class="form-label">ID Cartella Drive</label><input type="text" name="gdrive_folder_id" class="form-control" value="<?= $e($config['parametri']['gdrive_folder_id'] ?? '') ?>" placeholder="1BxiMVs0XRA5nFMdKvBdBZjgmUUqptlbs"></div>
        <div class="col-12"><label class="form-label">JSON Service Account (incolla il contenuto)</label><textarea name="gdrive_credentials" class="form-control font-monospace" rows="5" style="font-size:.75rem"><?= $e($config['parametri']['gdrive_credentials'] ?? '') ?></textarea></div>
      </div>
    </div>
  </div>

  <!-- Dropbox -->
  <div class="card mb-3 sezione-tipo" id="sez-dropbox" style="<?= $tipo!=='dropbox'?'display:none':'' ?>">
    <div class="card-header fw-bold"><i class="bi bi-dropbox me-2"></i>Dropbox</div>
    <div class="card-body">
      <div class="row g-3">
        <div class="col-md-6"><label class="form-label">Access Token</label><input type="text" name="dropbox_token" class="form-control" value="<?= $e($config['parametri']['dropbox_token'] ?? '') ?>" placeholder="sl.xxxxx..."></div>
        <div class="col-md-6"><label class="form-label">Percorso Dropbox</label><input type="text" name="dropbox_path" class="form-control" value="<?= $e($config['parametri']['dropbox_path'] ?? '/backups/') ?>"></div>
      </div>
    </div>
  </div>

  <div class="d-flex gap-2">
    <button type="submit" class="btn btn-primary"><i class="bi bi-check2-circle me-2"></i>Salva Configurazione</button>
    <a href="/backup" class="btn btn-outline-secondary">Annulla</a>
  </div>
</form>

<script>
function mostraSezione() {
  const tipo = document.getElementById('tipo-select').value;
  document.querySelectorAll('.sezione-tipo').forEach(el => el.style.display = 'none');
  if (tipo === 'webdav' || tipo === 'nextcloud') {
    document.getElementById('sez-webdav').style.display = '';
  } else {
    const sez = document.getElementById('sez-' + tipo);
    if (sez) sez.style.display = '';
  }
  if (tipo === 'locale') document.getElementById('sez-locale').style.display = '';
}
</script>
