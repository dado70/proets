<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Link non valido</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <style>body{background:#f0f4f8;}</style>
</head>
<body>
<div class="container" style="max-width:480px;margin:80px auto">
  <div class="card shadow-sm text-center p-5">
    <i class="bi bi-exclamation-triangle-fill text-warning" style="font-size:3rem"></i>
    <h4 class="mt-3">Link non valido o scaduto</h4>
    <p class="text-muted">Il link che hai seguito non è valido, è già stato utilizzato oppure è scaduto.</p>
    <p class="small text-muted">Contatta la segreteria dell'associazione per ricevere un nuovo link.</p>
    <?php if (!empty($company['email'])): ?>
    <a href="mailto:<?= htmlspecialchars($company['email']) ?>" class="btn btn-primary mt-2">
      <i class="bi bi-envelope me-2"></i>Contatta la segreteria
    </a>
    <?php endif; ?>
  </div>
</div>
</body>
</html>
