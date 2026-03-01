<?php
$isEdit = $movimento !== null;
$tipoConti = ['cassa'=>'Cassa','banca'=>'Banca c/c','paypal'=>'PayPal','stripe'=>'Stripe','carta_credito'=>'Carta di credito','altro'=>'Altro'];
// Raggruppa causali per area
$causaliGrouped = [];
foreach ($causali as $c) {
    $area = $c['sezione'] ?? 'Altro';
    $causaliGrouped[$area][] = $c;
}
$sezioneLabel = [
    'A'=>'A - Interesse Generale (Entrate)','B'=>'B - Attività Diverse (Entrate)',
    'C'=>'C - Raccolta Fondi (Entrate)','D'=>'D - Finanziaria/Patrimoniale (Entrate)',
    'E'=>'E - Supporto Generale (Entrate)','DIS'=>'Disinvestimenti (Entrate)',
    'INV'=>'Investimenti (Uscite)','FIG'=>'Figurativi','IMP'=>'Imposte','GC'=>'Giroconto',
];
?>
<div class="row justify-content-center">
  <div class="col-lg-8">
    <?php if ($error): ?>
    <div class="alert alert-danger py-2 mb-3"><i class="bi bi-exclamation-triangle me-2"></i><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <div class="card">
      <div class="card-header">
        <i class="bi bi-journal-plus me-2 text-primary"></i>
        <?= $isEdit ? 'Modifica Movimento' : 'Nuovo Movimento Prima Nota' ?>
      </div>
      <div class="card-body">
        <form method="post" id="form-prima-nota">
          <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">

          <div class="row g-3">
            <!-- Data -->
            <div class="col-md-4">
              <label class="form-label">Data Movimento <span class="text-danger">*</span></label>
              <input type="date" name="data_movimento" class="form-control"
                value="<?= htmlspecialchars($movimento['data_movimento'] ?? date('Y-m-d')) ?>" required>
            </div>
            <div class="col-md-4">
              <label class="form-label">Data Valuta</label>
              <input type="date" name="data_valuta" class="form-control"
                value="<?= htmlspecialchars($movimento['data_valuta'] ?? '') ?>">
            </div>

            <!-- Tipo -->
            <div class="col-md-4">
              <label class="form-label">Tipo <span class="text-danger">*</span></label>
              <div class="btn-group w-100" role="group">
                <input type="radio" class="btn-check" name="tipo" id="tipo-entrata" value="entrata"
                  <?= ($movimento['tipo'] ?? 'entrata') === 'entrata' ? 'checked' : '' ?> required>
                <label class="btn btn-outline-success" for="tipo-entrata"><i class="bi bi-arrow-down-circle me-1"></i>Entrata</label>
                <input type="radio" class="btn-check" name="tipo" id="tipo-uscita" value="uscita"
                  <?= ($movimento['tipo'] ?? '') === 'uscita' ? 'checked' : '' ?>>
                <label class="btn btn-outline-danger" for="tipo-uscita"><i class="bi bi-arrow-up-circle me-1"></i>Uscita</label>
              </div>
            </div>

            <!-- Causale -->
            <div class="col-md-8">
              <label class="form-label">Causale <span class="text-danger">*</span></label>
              <select name="causale_id" id="causale-select" class="form-select" required>
                <option value="">-- Seleziona causale --</option>
                <?php foreach ($causaliGrouped as $sez => $items): ?>
                <optgroup label="<?= htmlspecialchars($sezioneLabel[$sez] ?? $sez) ?>">
                  <?php foreach ($items as $c): ?>
                  <option value="<?= $c['id'] ?>"
                    data-tipo="<?= $c['tipo'] ?>"
                    data-cod="<?= htmlspecialchars($c['codice_bilancio']) ?>"
                    <?= ($movimento['causale_id'] ?? 0) == $c['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($c['codice_numero'] . ' - ' . $c['descrizione']) ?>
                  </option>
                  <?php endforeach; ?>
                </optgroup>
                <?php endforeach; ?>
              </select>
              <div class="form-text" id="causale-info"></div>
            </div>

            <!-- Importo -->
            <div class="col-md-4">
              <label class="form-label">Importo € <span class="text-danger">*</span></label>
              <div class="input-group">
                <span class="input-group-text">€</span>
                <input type="number" name="importo" class="form-control" min="0.01" step="0.01"
                  value="<?= htmlspecialchars($movimento['importo'] ?? '') ?>"
                  placeholder="0,00" required>
              </div>
            </div>

            <!-- Conto -->
            <div class="col-md-6">
              <label class="form-label">Conto <span class="text-danger">*</span></label>
              <select name="account_id" class="form-select" required>
                <option value="">-- Seleziona conto --</option>
                <?php foreach ($conti as $c): ?>
                <option value="<?= $c['id'] ?>" <?= ($movimento['account_id'] ?? 0) == $c['id'] ? 'selected' : '' ?>>
                  <?= htmlspecialchars($c['nome'] . ' (' . ($tipoConti[$c['tipo']] ?? $c['tipo']) . ')') ?>
                </option>
                <?php endforeach; ?>
              </select>
            </div>

            <!-- Descrizione -->
            <div class="col-12">
              <label class="form-label">Descrizione <span class="text-danger">*</span></label>
              <input type="text" name="descrizione" class="form-control"
                value="<?= htmlspecialchars($movimento['descrizione'] ?? '') ?>"
                placeholder="Descrizione del movimento" required maxlength="500">
            </div>

            <!-- Fornitore / Beneficiario -->
            <div class="col-md-6">
              <label class="form-label">Fornitore / Beneficiario</label>
              <input type="text" name="fornitore_beneficiario" class="form-control"
                value="<?= htmlspecialchars($movimento['fornitore_beneficiario'] ?? '') ?>"
                placeholder="Nome fornitore o beneficiario">
            </div>

            <!-- N. Documento -->
            <div class="col-md-6">
              <label class="form-label">N. Documento / Fattura</label>
              <input type="text" name="numero_documento" class="form-control"
                value="<?= htmlspecialchars($movimento['numero_documento'] ?? '') ?>"
                placeholder="N. fattura, ricevuta...">
            </div>

            <!-- Note -->
            <div class="col-12">
              <label class="form-label">Note</label>
              <textarea name="note" class="form-control" rows="2"
                placeholder="Note aggiuntive..."><?= htmlspecialchars($movimento['note'] ?? '') ?></textarea>
            </div>
          </div>

          <div class="d-flex gap-2 mt-4">
            <button type="submit" class="btn btn-primary">
              <i class="bi bi-check2-circle me-2"></i><?= $isEdit ? 'Salva Modifiche' : 'Registra Movimento' ?>
            </button>
            <?php if (!$isEdit): ?>
            <button type="submit" name="altro" value="1" class="btn btn-outline-primary">
              <i class="bi bi-plus-circle me-2"></i>Salva e nuovo
            </button>
            <?php endif; ?>
            <a href="/prima-nota" class="btn btn-outline-secondary ms-auto">
              <i class="bi bi-x me-1"></i>Annulla
            </a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<?php
$scripts = <<<'JS'
<script>
// Auto-set tipo dal causale selezionato
const causaleSelect = document.getElementById('causale-select');
const causaleInfo = document.getElementById('causale-info');
causaleSelect?.addEventListener('change', function() {
  const opt = this.options[this.selectedIndex];
  const tipo = opt.dataset.tipo;
  const cod  = opt.dataset.cod;
  if (tipo === 'entrata') { document.getElementById('tipo-entrata').checked = true; }
  else if (tipo === 'uscita') { document.getElementById('tipo-uscita').checked = true; }
  causaleInfo.innerHTML = cod ? `<span class="badge bg-secondary">${cod}</span> - ${opt.text}` : '';
});
// Trigger on load (per modifica)
if (causaleSelect?.value) causaleSelect.dispatchEvent(new Event('change'));
</script>
JS;
