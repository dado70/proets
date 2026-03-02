<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>ProETS — Recupero Password</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
body{background:linear-gradient(135deg,#0f2744 0%,#1e3a8a 100%);min-height:100vh;font-family:'Segoe UI',system-ui,sans-serif;}
.auth-card{max-width:420px;margin:0 auto;border:none;border-radius:20px;box-shadow:0 20px 60px rgba(0,0,0,.35);overflow:hidden;}
.auth-header{background:linear-gradient(135deg,#1a3a5c,#2563eb);padding:2rem;text-align:center;}
.auth-logo{font-size:2rem;font-weight:900;color:#fff;}
.auth-body{padding:2rem;background:#fff;}
</style>
</head>
<body class="d-flex align-items-center" style="padding:2rem 1rem;">
<div class="auth-card card w-100">
  <div class="auth-header">
    <div class="auth-logo"><i class="bi bi-building-check me-2" style="color:#60a5fa"></i>ProETS</div>
    <div style="color:rgba(255,255,255,.6);font-size:.82rem;margin-top:.25rem;">Recupero Password</div>
  </div>
  <div class="auth-body">
    <?php if ($sent): ?>
    <div class="text-center py-3">
      <i class="bi bi-envelope-check text-success" style="font-size:3rem;"></i>
      <h6 class="fw-bold mt-3">Email inviata!</h6>
      <p class="text-muted small">Se l'indirizzo è associato a un account attivo, riceverai le istruzioni per reimpostare la password entro pochi minuti.</p>
      <a href="/auth/login" class="btn btn-primary btn-sm mt-2">
        <i class="bi bi-arrow-left me-1"></i>Torna al login
      </a>
    </div>
    <?php else: ?>
    <h6 class="fw-bold mb-1" style="color:#1e293b">Password dimenticata?</h6>
    <p class="text-muted small mb-3">Inserisci la tua email e ti invieremo un link per reimpostare la password (valido 1 ora).</p>
    <?php if ($error): ?>
    <div class="alert alert-danger py-2 small"><i class="bi bi-exclamation-triangle me-2"></i><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="post">
      <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">
      <div class="mb-3">
        <label class="form-label fw-semibold small">Indirizzo Email</label>
        <div class="input-group">
          <span class="input-group-text bg-light border-end-0"><i class="bi bi-envelope text-muted"></i></span>
          <input type="email" name="email" class="form-control border-start-0" placeholder="tua@email.it" required autocomplete="email">
        </div>
      </div>
      <div class="d-grid">
        <button type="submit" class="btn btn-primary fw-bold">
          <i class="bi bi-send me-2"></i>Invia link di recupero
        </button>
      </div>
      <div class="text-center mt-3">
        <a href="/auth/login" class="small text-muted"><i class="bi bi-arrow-left me-1"></i>Torna al login</a>
      </div>
    </form>
    <?php endif; ?>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
