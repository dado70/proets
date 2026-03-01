<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Pagamento Quota — <?= htmlspecialchars($company['ragione_sociale'] ?? '') ?></title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <style>
    body { background: #f0f4f8; }
    .pay-card { max-width: 500px; margin: 60px auto; }
  </style>
</head>
<body>
<div class="pay-card">
  <?php if (!empty($company['logo_path'])): ?>
  <div class="text-center mb-3">
    <img src="/<?= htmlspecialchars($company['logo_path']) ?>" alt="Logo" style="max-height:80px">
  </div>
  <?php endif; ?>

  <div class="card shadow-sm">
    <div class="card-header bg-primary text-white text-center py-3">
      <h5 class="mb-1"><?= htmlspecialchars($company['ragione_sociale'] ?? '') ?></h5>
      <div class="small opacity-75">Pagamento Quota Associativa</div>
    </div>
    <div class="card-body p-4">
      <?php if (!empty($success)): ?>
      <!-- Pagamento confermato -->
      <div class="text-center py-3">
        <i class="bi bi-check-circle-fill text-success" style="font-size:3rem"></i>
        <h5 class="mt-3">Pagamento registrato!</h5>
        <p class="text-muted">Grazie <?= htmlspecialchars($socio['nome']) ?>, la tua quota per l'anno <strong><?= $quota['anno'] ?></strong> è stata registrata con successo.</p>
        <p class="small text-muted">Riceverai una conferma via email a <strong><?= htmlspecialchars($socio['email']) ?></strong>.</p>
      </div>
      <?php else: ?>
      <!-- Form pagamento -->
      <h6 class="mb-3">Ciao, <strong><?= htmlspecialchars($socio['nome'].' '.$socio['cognome']) ?></strong>!</h6>
      <div class="alert alert-info py-2 small">
        <strong>Quota <?= $quota['anno'] ?>:</strong> € <?= number_format($quota['importo'],2,',','.') ?>
        <?php if ($quota['importo_pagato'] > 0): ?>
        &nbsp;|&nbsp; Già versato: € <?= number_format($quota['importo_pagato'],2,',','.') ?>
        <?php endif; ?>
      </div>

      <form method="post">
        <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
        <div class="mb-3">
          <label class="form-label">Importo da versare (€)</label>
          <input type="number" name="importo_pagato" class="form-control" step="0.01" min="0.01"
            value="<?= $quota['importo'] - $quota['importo_pagato'] ?>"
            max="<?= $quota['importo'] - $quota['importo_pagato'] ?>" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Metodo di pagamento</label>
          <select name="metodo_pagamento" class="form-select">
            <option value="bonifico">Bonifico bancario</option>
            <option value="contanti">Contanti (da consegnare in sede)</option>
            <option value="paypal">PayPal</option>
            <option value="carta">Carta di credito</option>
          </select>
        </div>
        <?php if (!empty($company['iban'])): ?>
        <div class="alert alert-light border py-2 small mb-3">
          <strong>IBAN per bonifico:</strong><br>
          <code><?= htmlspecialchars($company['iban']) ?></code><br>
          Causale: Quota associativa <?= $quota['anno'] ?> — <?= htmlspecialchars($socio['cognome'].' '.$socio['nome']) ?>
        </div>
        <?php endif; ?>
        <button type="submit" class="btn btn-primary w-100">
          <i class="bi bi-check2-circle me-2"></i>Conferma dichiarazione pagamento
        </button>
      </form>
      <p class="small text-muted text-center mt-3 mb-0">
        Il pagamento verrà verificato dalla segreteria dell'associazione.
      </p>
      <?php endif; ?>
    </div>
  </div>

  <p class="text-center text-muted small mt-3">
    &copy; <?= date('Y') ?> <?= htmlspecialchars($company['ragione_sociale'] ?? '') ?>
    <?php if ($company['pec'] ?? ''): ?>· <a href="mailto:<?= htmlspecialchars($company['pec']) ?>"><?= htmlspecialchars($company['pec']) ?></a><?php endif; ?>
  </p>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
