<?php
$sezioniMenu = [
  'azienda'  => ['icon'=>'building','label'=>'Associazione'],
  'utenti'   => ['icon'=>'people','label'=>'Utenti'],
  'email'    => ['icon'=>'envelope-at','label'=>'Email SMTP'],
  'quote-annuali'=>['icon'=>'credit-card-2-front','label'=>'Quote Annuali'],
  'gdpr'     => ['icon'=>'shield-check','label'=>'GDPR & Privacy'],
];
$currentSection = basename($_SERVER['REQUEST_URI'] ?? '', '?'.http_build_query($_GET));
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

  <!-- Form principale -->
  <div class="col-lg-10 col-md-9">
    <?php if ($error): ?><div class="alert alert-danger py-2"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <div class="card mb-4">
      <div class="card-header fw-bold"><i class="bi bi-building me-2 text-primary"></i>Dati Associazione</div>
      <div class="card-body">
        <form method="post" enctype="multipart/form-data">
          <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">
          <div class="row g-3">
            <div class="col-md-8">
              <label class="form-label">Ragione Sociale / Nome Associazione <span class="text-danger">*</span></label>
              <input type="text" name="ragione_sociale" class="form-control" value="<?= htmlspecialchars($company['ragione_sociale'] ?? '') ?>" required>
            </div>
            <div class="col-md-4">
              <label class="form-label">Forma Giuridica</label>
              <select name="forma_giuridica" class="form-select">
                <?php foreach (['APS','ODV','ETS','ONLUS','Altro'] as $fg): ?>
                <option <?= ($company['forma_giuridica']??'')===$fg?'selected':'' ?>><?= $fg ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label">Codice Fiscale</label>
              <input type="text" name="codice_fiscale" class="form-control" value="<?= htmlspecialchars($company['codice_fiscale'] ?? '') ?>" maxlength="16">
            </div>
            <div class="col-md-4">
              <label class="form-label">P.IVA (se presente)</label>
              <input type="text" name="partita_iva" class="form-control" value="<?= htmlspecialchars($company['partita_iva'] ?? '') ?>" maxlength="11">
            </div>
            <div class="col-md-4">
              <label class="form-label">N. Iscrizione RUNTS</label>
              <input type="text" name="nr_iscrizione_runts" class="form-control" value="<?= htmlspecialchars($company['nr_iscrizione_runts'] ?? '') ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label">Indirizzo</label>
              <input type="text" name="indirizzo" class="form-control" value="<?= htmlspecialchars($company['indirizzo'] ?? '') ?>">
            </div>
            <div class="col-md-2">
              <label class="form-label">CAP</label>
              <input type="text" name="cap" class="form-control" value="<?= htmlspecialchars($company['cap'] ?? '') ?>" maxlength="5">
            </div>
            <div class="col-md-3">
              <label class="form-label">Città</label>
              <input type="text" name="citta" class="form-control" value="<?= htmlspecialchars($company['citta'] ?? '') ?>">
            </div>
            <div class="col-md-1">
              <label class="form-label">Prov.</label>
              <input type="text" name="provincia" class="form-control" value="<?= htmlspecialchars($company['provincia'] ?? '') ?>" maxlength="2">
            </div>
            <div class="col-md-3">
              <label class="form-label">Telefono</label>
              <input type="text" name="telefono" class="form-control" value="<?= htmlspecialchars($company['telefono'] ?? '') ?>">
            </div>
            <div class="col-md-4">
              <label class="form-label">Email</label>
              <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($company['email'] ?? '') ?>">
            </div>
            <div class="col-md-4">
              <label class="form-label">PEC</label>
              <input type="email" name="pec" class="form-control" value="<?= htmlspecialchars($company['pec'] ?? '') ?>">
            </div>
            <div class="col-md-4">
              <label class="form-label">Sito Web</label>
              <input type="url" name="sito_web" class="form-control" value="<?= htmlspecialchars($company['sito_web'] ?? '') ?>" placeholder="https://...">
            </div>
            <div class="col-md-3">
              <label class="form-label">Data Costituzione</label>
              <input type="date" name="data_costituzione" class="form-control" value="<?= htmlspecialchars($company['data_costituzione'] ?? '') ?>">
            </div>
            <div class="col-md-3">
              <label class="form-label">Esercizio Corrente</label>
              <select name="esercizio_corrente" class="form-select">
                <?php foreach (range(date('Y')+1, 2020) as $y): ?>
                <option value="<?= $y ?>" <?= ($company['esercizio_corrente']??date('Y'))==$y?'selected':'' ?>><?= $y ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Logo Associazione</label>
              <input type="file" name="logo" class="form-control" accept="image/*">
              <?php if (!empty($company['logo_path'])): ?>
              <img src="/<?= htmlspecialchars($company['logo_path']) ?>" alt="Logo" class="mt-2" style="max-height:60px">
              <?php endif; ?>
            </div>
            <div class="col-12">
              <label class="form-label">Note</label>
              <textarea name="note" class="form-control" rows="2"><?= htmlspecialchars($company['note'] ?? '') ?></textarea>
            </div>
          </div>
          <button type="submit" class="btn btn-primary mt-3"><i class="bi bi-check2-circle me-2"></i>Salva Modifiche</button>
        </form>
      </div>
    </div>

    <!-- Conti -->
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <span class="fw-bold"><i class="bi bi-wallet2 me-2 text-primary"></i>Conti (Cassa, Banca, PayPal...)</span>
        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modal-conto">
          <i class="bi bi-plus me-1"></i>Aggiungi Conto
        </button>
      </div>
      <div class="table-responsive">
        <table class="table table-sm mb-0">
          <thead><tr><th>Codice</th><th>Nome</th><th>Tipo</th><th>IBAN</th><th class="text-end">Saldo Iniziale</th><th>Attivo</th></tr></thead>
          <tbody>
            <?php if (empty($conti)): ?>
            <tr><td colspan="6" class="text-center text-muted py-3">Nessun conto configurato.</td></tr>
            <?php else: ?>
            <?php foreach ($conti as $c): ?>
            <tr>
              <td><code><?= htmlspecialchars($c['codice']) ?></code></td>
              <td class="fw-semibold"><?= htmlspecialchars($c['nome']) ?></td>
              <td><span class="badge bg-secondary-subtle text-secondary"><?= $c['tipo'] ?></span></td>
              <td class="small text-muted"><?= htmlspecialchars($c['iban'] ?? '-') ?></td>
              <td class="text-end">€ <?= number_format($c['saldo_iniziale'],2,',','.') ?></td>
              <td><span class="badge bg-<?= $c['attivo']?'success':'secondary' ?>-subtle text-<?= $c['attivo']?'success':'secondary' ?>"><?= $c['attivo']?'Sì':'No' ?></span></td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- Modal Nuovo Conto -->
<div class="modal fade" id="modal-conto" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Aggiungi Conto</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <form method="post" action="/configurazione/azienda">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">
        <input type="hidden" name="action" value="add_conto">
        <div class="modal-body row g-3">
          <div class="col-md-4"><label class="form-label">Codice</label><input type="text" name="conto_codice" class="form-control" required placeholder="CASSA1"></div>
          <div class="col-md-8"><label class="form-label">Nome</label><input type="text" name="conto_nome" class="form-control" required placeholder="Cassa principale"></div>
          <div class="col-md-6"><label class="form-label">Tipo</label>
            <select name="conto_tipo" class="form-select">
              <option value="cassa">Cassa</option><option value="banca">Banca c/c</option>
              <option value="paypal">PayPal</option><option value="stripe">Stripe</option>
              <option value="carta_credito">Carta di credito</option><option value="altro">Altro</option>
            </select>
          </div>
          <div class="col-md-6"><label class="form-label">Saldo Iniziale (€)</label><input type="number" name="conto_saldo" class="form-control" step="0.01" value="0" min="0"></div>
          <div class="col-12"><label class="form-label">IBAN (opzionale)</label><input type="text" name="conto_iban" class="form-control" placeholder="IT60..."></div>
          <div class="col-12"><label class="form-label">Data Saldo Iniziale</label><input type="date" name="conto_data_saldo" class="form-control" value="<?= date('Y-01-01') ?>"></div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annulla</button><button type="submit" class="btn btn-primary">Aggiungi</button></div>
      </form>
    </div>
  </div>
</div>
