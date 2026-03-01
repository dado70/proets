<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>ProETS — Reimposta Password</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>body{background:linear-gradient(135deg,#0f2744,#1e3a8a);min-height:100vh;font-family:'Segoe UI',system-ui,sans-serif;}.auth-card{max-width:420px;margin:0 auto;border:none;border-radius:20px;box-shadow:0 20px 60px rgba(0,0,0,.35);overflow:hidden;}.auth-header{background:linear-gradient(135deg,#1a3a5c,#2563eb);padding:2rem;text-align:center;}.auth-logo{font-size:2rem;font-weight:900;color:#fff;}.auth-body{padding:2rem;background:#fff;}</style>
</head>
<body class="d-flex align-items-center" style="padding:2rem 1rem;">
<div class="auth-card card w-100">
  <div class="auth-header">
    <div class="auth-logo"><i class="bi bi-building-check me-2" style="color:#60a5fa"></i>ProETS</div>
    <div style="color:rgba(255,255,255,.6);font-size:.82rem;margin-top:.25rem;">Reimposta Password</div>
  </div>
  <div class="auth-body">
    <?php if ($success): ?>
    <div class="text-center py-3">
      <i class="bi bi-check-circle-fill text-success" style="font-size:3rem;"></i>
      <h6 class="fw-bold mt-3">Password reimpostata!</h6>
      <p class="text-muted small">Puoi ora accedere con la nuova password.</p>
      <a href="/auth/login" class="btn btn-primary mt-2"><i class="bi bi-box-arrow-in-right me-2"></i>Accedi</a>
    </div>
    <?php elseif (!$tokenValid): ?>
    <div class="text-center py-3">
      <i class="bi bi-x-circle-fill text-danger" style="font-size:3rem;"></i>
      <h6 class="fw-bold mt-3">Link non valido</h6>
      <p class="text-muted small"><?= htmlspecialchars($error ?? 'Il link è scaduto o già utilizzato.') ?></p>
      <a href="/auth/forgot-password" class="btn btn-outline-primary mt-2">Richiedi nuovo link</a>
    </div>
    <?php else: ?>
    <h6 class="fw-bold mb-3" style="color:#1e293b">Scegli la nuova password</h6>
    <?php if ($error): ?>
    <div class="alert alert-danger py-2 small"><i class="bi bi-exclamation-triangle me-2"></i><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="post">
      <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">
      <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
      <div class="mb-3">
        <label class="form-label fw-semibold small">Nuova Password (min. 8 caratteri)</label>
        <input type="password" name="password" class="form-control" required minlength="8" autocomplete="new-password" id="pass1">
      </div>
      <div class="mb-3">
        <label class="form-label fw-semibold small">Conferma Password</label>
        <input type="password" name="password2" class="form-control" required minlength="8" autocomplete="new-password" id="pass2">
        <div id="pass-match" class="form-text"></div>
      </div>
      <div class="d-grid">
        <button type="submit" class="btn btn-primary fw-bold"><i class="bi bi-shield-check me-2"></i>Reimposta Password</button>
      </div>
    </form>
    <script>
    document.getElementById('pass2').addEventListener('input', function() {
      const match = document.getElementById('pass-match');
      if (this.value === document.getElementById('pass1').value) {
        match.innerHTML = '<span class="text-success"><i class="bi bi-check-circle me-1"></i>Le password coincidono</span>';
      } else {
        match.innerHTML = '<span class="text-danger"><i class="bi bi-x-circle me-1"></i>Le password non coincidono</span>';
      }
    });
    </script>
    <?php endif; ?>
  </div>
</div>
</body>
</html>
