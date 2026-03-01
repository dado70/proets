<!-- Import Movimenti -->
<div class="row justify-content-center">
  <div class="col-lg-8">
    <?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
      <strong>Errori durante l'import:</strong>
      <ul class="mb-0 mt-1">
        <?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
      </ul>
    </div>
    <?php endif; ?>

    <div class="card mb-4">
      <div class="card-header"><i class="bi bi-cloud-upload me-2 text-primary"></i>Importa Movimenti</div>
      <div class="card-body">
        <form method="post" enctype="multipart/form-data">
          <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">
          <div class="mb-3">
            <label class="form-label fw-semibold">Tipo di Import</label>
            <select name="tipo_import" class="form-select" id="tipo-select" required>
              <option value="csv">CSV ProETS (export dal sistema)</option>
              <option value="bancario">Estratto Conto Bancario (CSV CBI/ABI)</option>
              <option value="paypal">PayPal CSV</option>
              <option value="stripe">Stripe CSV</option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">File da importare</label>
            <input type="file" name="file_import" class="form-control" required accept=".csv,.xls,.xlsx,.txt">
            <div class="form-text">Dimensione massima: <?= ini_get('upload_max_filesize') ?>. Formati accettati: CSV, XLS, XLSX.</div>
          </div>
          <div id="istruzioni" class="alert alert-info py-2 small">
            <strong>Formato CSV ProETS:</strong> Colonne separate da punto e virgola (;).<br>
            Data;Tipo;Importo;Descrizione;N.Caus;Causale;Cod.Bilancio;Conto;Fornitore/Beneficiario;N.Documento;Note
          </div>
          <button type="submit" class="btn btn-primary">
            <i class="bi bi-cloud-upload me-2"></i>Importa
          </button>
          <a href="/prima-nota" class="btn btn-outline-secondary ms-2">Annulla</a>
        </form>
      </div>
    </div>

    <!-- Guida formati -->
    <div class="card">
      <div class="card-header"><i class="bi bi-info-circle me-2"></i>Guida Formati di Import</div>
      <div class="card-body">
        <div class="accordion" id="acc-import">
          <div class="accordion-item border-0 mb-2">
            <h2 class="accordion-header"><button class="accordion-button collapsed py-2" type="button" data-bs-toggle="collapse" data-bs-target="#acc-csv">CSV Estratto Conto Bancario</button></h2>
            <div id="acc-csv" class="accordion-collapse collapse" data-bs-parent="#acc-import">
              <div class="accordion-body small py-2">Formato: <code>Data;DataValuta;Descrizione;Accredito;Addebito</code><br>Il sistema riconosce automaticamente entrate (accrediti) e uscite (addebiti).</div>
            </div>
          </div>
          <div class="accordion-item border-0 mb-2">
            <h2 class="accordion-header"><button class="accordion-button collapsed py-2" type="button" data-bs-toggle="collapse" data-bs-target="#acc-paypal">PayPal CSV</button></h2>
            <div id="acc-paypal" class="accordion-collapse collapse" data-bs-parent="#acc-import">
              <div class="accordion-body small py-2">Scarica l'estratto conto da PayPal → Report → Attività → Scarica CSV. I duplicati (stesso ID transazione) vengono automaticamente ignorati.</div>
            </div>
          </div>
          <div class="accordion-item border-0">
            <h2 class="accordion-header"><button class="accordion-button collapsed py-2" type="button" data-bs-toggle="collapse" data-bs-target="#acc-stripe">Stripe CSV</button></h2>
            <div id="acc-stripe" class="accordion-collapse collapse" data-bs-parent="#acc-import">
              <div class="accordion-body small py-2">Da Stripe Dashboard → Report → Balanced → Scarica CSV. Solo i pagamenti con stato "paid" vengono importati.</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php $scripts = <<<'JS'
<script>
const istruzioni = {
  csv: '<strong>Formato CSV ProETS:</strong> Data;Tipo;Importo;Descrizione;...',
  bancario: '<strong>Estratto Conto Bancario:</strong> Data;DataValuta;Descrizione;Accredito;Addebito',
  paypal: '<strong>PayPal CSV:</strong> Scarica da PayPal → Report → Attività → Scarica CSV',
  stripe: '<strong>Stripe CSV:</strong> Scarica da Stripe Dashboard → Report → Balanced'
};
document.getElementById('tipo-select').addEventListener('change', function() {
  document.getElementById('istruzioni').innerHTML = istruzioni[this.value] || '';
});
</script>
JS;
