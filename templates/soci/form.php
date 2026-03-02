<?php
$e   = fn($v) => htmlspecialchars((string)$v);
$isEdit = isset($socio['id']);
$title  = $isEdit ? 'Modifica Socio' : 'Nuovo Socio';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h5 class="mb-0"><?= $title ?></h5>
  <a href="/soci" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Torna alla lista</a>
</div>

<form method="post" action="<?= $isEdit ? '/soci/'.$socio['id'].'/modifica' : '/soci/nuovo' ?>">
  <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">

  <!-- Dati Anagrafici -->
  <div class="card mb-3">
    <div class="card-header fw-bold"><i class="bi bi-person me-2 text-primary"></i>Dati Anagrafici</div>
    <div class="card-body">
      <div class="row g-3">
        <div class="col-md-4">
          <label class="form-label">Cognome <span class="text-danger">*</span></label>
          <input type="text" name="cognome" class="form-control" value="<?= $e($socio['cognome'] ?? '') ?>" required>
        </div>
        <div class="col-md-4">
          <label class="form-label">Nome <span class="text-danger">*</span></label>
          <input type="text" name="nome" class="form-control" value="<?= $e($socio['nome'] ?? '') ?>" required>
        </div>
        <div class="col-md-4">
          <label class="form-label">Codice Fiscale</label>
          <input type="text" name="codice_fiscale" class="form-control text-uppercase" value="<?= $e($socio['codice_fiscale'] ?? '') ?>" maxlength="16" pattern="[A-Za-z0-9]{16}">
        </div>
        <div class="col-md-3">
          <label class="form-label">Data di Nascita</label>
          <input type="date" name="data_nascita" class="form-control" value="<?= $e($socio['data_nascita'] ?? '') ?>">
        </div>
        <div class="col-md-5">
          <label class="form-label">Luogo di Nascita</label>
          <input type="text" name="luogo_nascita" class="form-control" value="<?= $e($socio['luogo_nascita'] ?? '') ?>">
        </div>
        <div class="col-md-2">
          <label class="form-label">Sesso</label>
          <select name="sesso" class="form-select">
            <option value="">-</option>
            <option value="M" <?= ($socio['sesso'] ?? '')==='M'?'selected':'' ?>>M</option>
            <option value="F" <?= ($socio['sesso'] ?? '')==='F'?'selected':'' ?>>F</option>
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label">Nazionalità</label>
          <input type="text" name="nazionalita" class="form-control" value="<?= $e($socio['nazionalita'] ?? 'IT') ?>" maxlength="2">
        </div>
      </div>
    </div>
  </div>

  <!-- Contatti -->
  <div class="card mb-3">
    <div class="card-header fw-bold"><i class="bi bi-envelope me-2 text-primary"></i>Contatti</div>
    <div class="card-body">
      <div class="row g-3">
        <div class="col-md-5">
          <label class="form-label">Email</label>
          <input type="email" name="email" class="form-control" value="<?= $e($socio['email'] ?? '') ?>">
        </div>
        <div class="col-md-3">
          <label class="form-label">Telefono</label>
          <input type="text" name="telefono" class="form-control" value="<?= $e($socio['telefono'] ?? '') ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label">Indirizzo</label>
          <input type="text" name="indirizzo" class="form-control" value="<?= $e($socio['indirizzo'] ?? '') ?>">
        </div>
        <div class="col-md-2">
          <label class="form-label">CAP</label>
          <input type="text" name="cap" class="form-control" value="<?= $e($socio['cap'] ?? '') ?>" maxlength="5">
        </div>
        <div class="col-md-4">
          <label class="form-label">Città</label>
          <input type="text" name="citta" class="form-control" value="<?= $e($socio['citta'] ?? '') ?>">
        </div>
        <div class="col-md-2">
          <label class="form-label">Prov.</label>
          <input type="text" name="provincia" class="form-control" value="<?= $e($socio['provincia'] ?? '') ?>" maxlength="2">
        </div>
      </div>
    </div>
  </div>

  <!-- Dati Associativi -->
  <div class="card mb-3">
    <div class="card-header fw-bold"><i class="bi bi-card-text me-2 text-primary"></i>Dati Associativi</div>
    <div class="card-body">
      <div class="row g-3">
        <div class="col-md-3">
          <label class="form-label">Tipo Socio</label>
          <select name="tipo_socio" class="form-select">
            <option value="ordinario" <?= ($socio['tipo_socio'] ?? 'ordinario')==='ordinario'?'selected':'' ?>>Ordinario</option>
            <option value="fondatore" <?= ($socio['tipo_socio'] ?? '')==='fondatore'?'selected':'' ?>>Fondatore</option>
            <option value="onorario"  <?= ($socio['tipo_socio'] ?? '')==='onorario'?'selected':'' ?>>Onorario</option>
            <option value="sostenitore" <?= ($socio['tipo_socio'] ?? '')==='sostenitore'?'selected':'' ?>>Sostenitore</option>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label">Data Iscrizione</label>
          <input type="date" name="data_iscrizione" class="form-control" value="<?= $e($socio['data_iscrizione'] ?? date('Y-m-d')) ?>">
        </div>
        <div class="col-md-3">
          <label class="form-label">N. Tessera</label>
          <input type="text" name="numero_tessera" class="form-control" value="<?= $e($socio['numero_tessera'] ?? '') ?>" placeholder="Generato automaticamente">
        </div>
        <div class="col-md-3">
          <label class="form-label">Quota Esonerata</label>
          <div class="form-check mt-2">
            <input type="checkbox" name="quota_esonerata" id="quota_esonerata" class="form-check-input" value="1" <?= !empty($socio['quota_esonerata'])?'checked':'' ?>>
            <label for="quota_esonerata" class="form-check-label">Esonerato dal pagamento quota</label>
          </div>
        </div>
        <?php if ($isEdit): ?>
        <div class="col-md-3">
          <label class="form-label">Stato</label>
          <div class="form-check mt-2">
            <input type="checkbox" name="attivo" id="attivo" class="form-check-input" value="1" <?= !empty($socio['attivo'])?'checked':'' ?>>
            <label for="attivo" class="form-check-label">Socio attivo</label>
          </div>
        </div>
        <div class="col-md-3">
          <label class="form-label">Data Cessazione</label>
          <input type="date" name="data_cessazione" class="form-control" value="<?= $e($socio['data_cessazione'] ?? '') ?>">
        </div>
        <?php endif; ?>
        <div class="col-12">
          <label class="form-label">Note</label>
          <textarea name="note" class="form-control" rows="2"><?= $e($socio['note'] ?? '') ?></textarea>
        </div>
      </div>
    </div>
  </div>

  <!-- GDPR -->
  <div class="card mb-3">
    <div class="card-header fw-bold"><i class="bi bi-shield-check me-2 text-primary"></i>Consensi GDPR</div>
    <div class="card-body">
      <div class="row g-3">
        <div class="col-md-4">
          <div class="form-check">
            <input type="checkbox" name="consenso_privacy" id="consenso_privacy" class="form-check-input" value="1" <?= !empty($socio['consenso_privacy'])?'checked':'' ?>>
            <label for="consenso_privacy" class="form-check-label">Consenso trattamento dati (obbligatorio)</label>
          </div>
        </div>
        <div class="col-md-4">
          <div class="form-check">
            <input type="checkbox" name="consenso_comunicazioni" id="consenso_comunicazioni" class="form-check-input" value="1" <?= !empty($socio['consenso_comunicazioni'])?'checked':'' ?>>
            <label for="consenso_comunicazioni" class="form-check-label">Consenso comunicazioni</label>
          </div>
        </div>
        <div class="col-md-4">
          <div class="form-check">
            <input type="checkbox" name="consenso_foto" id="consenso_foto" class="form-check-input" value="1" <?= !empty($socio['consenso_foto'])?'checked':'' ?>>
            <label for="consenso_foto" class="form-check-label">Consenso foto/video</label>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="d-flex gap-2">
    <button type="submit" class="btn btn-primary"><i class="bi bi-check2-circle me-2"></i><?= $isEdit ? 'Salva Modifiche' : 'Registra Socio' ?></button>
    <a href="/soci" class="btn btn-outline-secondary">Annulla</a>
  </div>
</form>
