<?php
$mesiNomi = ['', 'Gennaio','Febbraio','Marzo','Aprile','Maggio','Giugno','Luglio','Agosto','Settembre','Ottobre','Novembre','Dicembre'];
$tipoBadge = ['entrata' => 'success', 'uscita' => 'danger'];
$tipoIcon  = ['entrata' => 'arrow-down-circle-fill', 'uscita' => 'arrow-up-circle-fill'];
?>
<!-- Stat Cards -->
<div class="row g-3 mb-4">
  <div class="col-xl-3 col-sm-6">
    <div class="stat-card entrate h-100">
      <div class="stat-label">Totale Entrate <?= $esercizioAttivo ?></div>
      <div class="stat-value">€ <?= number_format($totEntrate,2,',','.') ?></div>
      <div class="stat-icon"><i class="bi bi-arrow-down-circle-fill"></i></div>
    </div>
  </div>
  <div class="col-xl-3 col-sm-6">
    <div class="stat-card uscite h-100">
      <div class="stat-label">Totale Uscite <?= $esercizioAttivo ?></div>
      <div class="stat-value">€ <?= number_format($totUscite,2,',','.') ?></div>
      <div class="stat-icon"><i class="bi bi-arrow-up-circle-fill"></i></div>
    </div>
  </div>
  <div class="col-xl-3 col-sm-6">
    <div class="stat-card saldo h-100">
      <div class="stat-label">Saldo Totale Conti</div>
      <div class="stat-value">€ <?= number_format($totSaldo,2,',','.') ?></div>
      <div class="stat-icon"><i class="bi bi-wallet2"></i></div>
    </div>
  </div>
  <div class="col-xl-3 col-sm-6">
    <div class="stat-card soci h-100">
      <div class="stat-label">Soci Attivi</div>
      <div class="stat-value"><?= $totSoci ?></div>
      <?php if ($quoteAttesa > 0): ?>
      <div style="font-size:.75rem;opacity:.85;margin-top:.25rem;">Quote da incassare: € <?= number_format($quoteAttesa,2,',','.') ?></div>
      <?php endif; ?>
      <div class="stat-icon"><i class="bi bi-people-fill"></i></div>
    </div>
  </div>
</div>

<div class="row g-3 mb-4">
  <!-- Grafico Andamento Mensile -->
  <div class="col-lg-8">
    <div class="card h-100">
      <div class="card-header d-flex align-items-center justify-content-between">
        <span><i class="bi bi-bar-chart-line me-2 text-primary"></i>Andamento Mensile <?= $esercizioAttivo ?></span>
        <a href="/rendiconto/annuale" class="btn btn-sm btn-outline-primary">Rendiconto</a>
      </div>
      <div class="card-body">
        <canvas id="chartMensile" style="max-height:280px;"></canvas>
      </div>
    </div>
  </div>

  <!-- Saldi conti -->
  <div class="col-lg-4">
    <div class="card h-100">
      <div class="card-header d-flex align-items-center justify-content-between">
        <span><i class="bi bi-wallet2 me-2 text-primary"></i>Saldi Conti</span>
        <a href="/prima-nota/saldi" class="btn btn-sm btn-outline-primary">Dettaglio</a>
      </div>
      <div class="card-body p-0">
        <?php if (empty($saldi)): ?>
        <div class="p-3 text-muted text-center small">Nessun conto configurato.</div>
        <?php else: ?>
        <ul class="list-group list-group-flush">
          <?php foreach ($saldi as $s):
            $tipoIco = ['cassa'=>'cash','banca'=>'bank','paypal'=>'paypal','stripe'=>'credit-card','carta_credito'=>'credit-card-2-front','altro'=>'wallet'];
            $ico = $tipoIco[$s['tipo']] ?? 'wallet';
          ?>
          <li class="list-group-item d-flex align-items-center justify-content-between px-3 py-2">
            <span>
              <i class="bi bi-<?= $ico ?> me-2 text-muted"></i>
              <span class="fw-semibold small"><?= htmlspecialchars($s['nome']) ?></span>
            </span>
            <span class="fw-bold <?= $s['saldo'] >= 0 ? 'text-success' : 'text-danger' ?>">
              € <?= number_format($s['saldo'],2,',','.') ?>
            </span>
          </li>
          <?php endforeach; ?>
        </ul>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<div class="row g-3">
  <!-- Ultimi movimenti -->
  <div class="col-lg-7">
    <div class="card">
      <div class="card-header d-flex align-items-center justify-content-between">
        <span><i class="bi bi-journal-text me-2 text-primary"></i>Ultimi Movimenti</span>
        <div class="d-flex gap-2">
          <a href="/prima-nota/nuovo" class="btn btn-sm btn-primary"><i class="bi bi-plus me-1"></i>Nuovo</a>
          <a href="/prima-nota" class="btn btn-sm btn-outline-secondary">Tutti</a>
        </div>
      </div>
      <div class="table-responsive">
        <table class="table table-hover table-sm mb-0">
          <thead>
            <tr>
              <th>Data</th>
              <th>Causale</th>
              <th>Conto</th>
              <th class="text-end">Importo</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($ultimiMovimenti)): ?>
            <tr><td colspan="4" class="text-center text-muted py-4">Nessun movimento registrato.</td></tr>
            <?php else: ?>
            <?php foreach ($ultimiMovimenti as $m): ?>
            <tr>
              <td class="text-nowrap"><?= date('d/m/Y', strtotime($m['data_movimento'])) ?></td>
              <td>
                <span class="badge badge-causale bg-<?= $m['tipo']==='entrata'?'success-subtle text-success':'danger-subtle text-danger' ?>">
                  <?= htmlspecialchars($m['codice_bilancio']) ?>
                </span>
                <small class="ms-1 text-truncate d-inline-block" style="max-width:160px" title="<?= htmlspecialchars($m['causale_desc']) ?>">
                  <?= htmlspecialchars($m['causale_desc']) ?>
                </small>
              </td>
              <td class="small text-muted"><?= htmlspecialchars($m['conto_nome']) ?></td>
              <td class="text-end fw-semibold <?= $m['tipo']==='entrata'?'text-entrata':'text-uscita' ?>">
                <?= $m['tipo']==='entrata'?'+':'-' ?> € <?= number_format($m['importo'],2,',','.') ?>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Quote in attesa -->
  <div class="col-lg-5">
    <div class="card">
      <div class="card-header d-flex align-items-center justify-content-between">
        <span><i class="bi bi-clock-history me-2 text-warning"></i>Quote in Attesa <?= $esercizioAttivo ?></span>
        <a href="/soci/quote" class="btn btn-sm btn-outline-secondary">Gestisci</a>
      </div>
      <div class="card-body p-0">
        <?php if (empty($sociQuoteAttesa)): ?>
        <div class="p-3 text-center text-muted small"><i class="bi bi-check-circle text-success me-2"></i>Tutte le quote sono in regola.</div>
        <?php else: ?>
        <ul class="list-group list-group-flush">
          <?php foreach ($sociQuoteAttesa as $q): ?>
          <li class="list-group-item d-flex align-items-center justify-content-between px-3 py-2">
            <div>
              <div class="fw-semibold small"><?= htmlspecialchars($q['cognome'] . ' ' . $q['nome']) ?></div>
              <?php if ($q['email']): ?>
              <div class="text-muted" style="font-size:.72rem"><?= htmlspecialchars($q['email']) ?></div>
              <?php endif; ?>
            </div>
            <div class="text-end">
              <div class="text-danger fw-bold small">€ <?= number_format($q['importo_dovuto'] - $q['importo_versato'],2,',','.') ?></div>
              <span class="badge bg-<?= $q['stato']==='parziale'?'warning':'danger' ?>-subtle text-<?= $q['stato']==='parziale'?'warning':'danger' ?>" style="font-size:.65rem">
                <?= $q['stato']==='parziale'?'Parziale':'Da pagare' ?>
              </span>
            </div>
          </li>
          <?php endforeach; ?>
        </ul>
        <?php if ($quoteAttesa > 0): ?>
        <div class="px-3 py-2 bg-light border-top">
          <a href="/soci/comunicazioni" class="btn btn-sm btn-warning w-100">
            <i class="bi bi-envelope-at me-1"></i>Invia richiesta quote massiva
          </a>
        </div>
        <?php endif; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php
$scripts = <<<JS
<script>
(function(){
  const labels  = <?= $graficoLabels ?>;
  const entrate = <?= $graficoEntrate ?>;
  const uscite  = <?= $graficoUscite ?>;
  if (!labels.length) return;
  new Chart(document.getElementById('chartMensile'), {
    type: 'bar',
    data: {
      labels,
      datasets: [
        { label: 'Entrate', data: entrate, backgroundColor: 'rgba(22,163,74,.7)', borderRadius: 6 },
        { label: 'Uscite',  data: uscite,  backgroundColor: 'rgba(220,38,38,.7)', borderRadius: 6 },
      ]
    },
    options: {
      responsive: true,
      plugins: { legend: { position: 'top' } },
      scales: {
        y: { ticks: { callback: v => '€ ' + v.toLocaleString('it-IT') } }
      }
    }
  });
})();
</script>
JS;
