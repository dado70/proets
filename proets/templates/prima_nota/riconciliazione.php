<div class="row g-4">
  <div class="col-lg-5">
    <div class="card">
      <div class="card-header"><i class="bi bi-arrow-left-right me-2 text-primary"></i>Nuova Riconciliazione</div>
      <div class="card-body">
        <?php if ($error): ?><div class="alert alert-danger py-2"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <form method="post">
          <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">
          <div class="mb-3">
            <label class="form-label">Conto Bancario</label>
            <select name="account_id" class="form-select" required>
              <option value="">-- Seleziona --</option>
              <?php foreach ($conti as $c): ?>
              <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nome']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Data Riconciliazione</label>
            <input type="date" name="data_riconciliazione" class="form-control" value="<?= date('Y-m-d') ?>" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Saldo Estratto Conto (€)</label>
            <div class="input-group">
              <span class="input-group-text">€</span>
              <input type="number" name="saldo_estratto_conto" class="form-control" step="0.01" placeholder="0,00" required>
            </div>
            <div class="form-text">Inserisci il saldo riportato sul tuo estratto conto bancario.</div>
          </div>
          <div class="mb-3">
            <label class="form-label">Note</label>
            <textarea name="note" class="form-control" rows="2" placeholder="Note opzionali..."></textarea>
          </div>
          <button type="submit" class="btn btn-primary w-100"><i class="bi bi-check2-circle me-2"></i>Esegui Riconciliazione</button>
        </form>
      </div>
    </div>
  </div>

  <div class="col-lg-7">
    <div class="card">
      <div class="card-header"><i class="bi bi-clock-history me-2"></i>Storico Riconciliazioni</div>
      <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
          <thead>
            <tr><th>Data</th><th>Conto</th><th class="text-end">Saldo E/C</th><th class="text-end">Saldo Sistema</th><th class="text-end">Differenza</th><th>Stato</th></tr>
          </thead>
          <tbody>
            <?php if (empty($storici)): ?>
            <tr><td colspan="6" class="text-center text-muted py-4">Nessuna riconciliazione effettuata.</td></tr>
            <?php else: ?>
            <?php foreach ($storici as $r): $diff = $r['differenza']; ?>
            <tr>
              <td class="small"><?= date('d/m/Y', strtotime($r['data_riconciliazione'])) ?></td>
              <td class="small"><?= htmlspecialchars($r['conto_nome']) ?></td>
              <td class="text-end">€ <?= number_format($r['saldo_estratto_conto'],2,',','.') ?></td>
              <td class="text-end">€ <?= number_format($r['saldo_calcolato'],2,',','.') ?></td>
              <td class="text-end fw-semibold <?= abs($diff) < 0.01 ? 'text-success' : 'text-danger' ?>">
                <?= abs($diff) < 0.01 ? '✓' : ('€ ' . number_format(abs($diff),2,',','.')) ?>
              </td>
              <td>
                <?php if (abs($diff) < 0.01): ?>
                <span class="badge bg-success-subtle text-success">OK</span>
                <?php else: ?>
                <span class="badge bg-warning-subtle text-warning">Differenza</span>
                <?php endif; ?>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
