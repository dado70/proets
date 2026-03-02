<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>ProETS — Accedi</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
body { background: linear-gradient(135deg, #0f2744 0%, #1e3a8a 100%); min-height: 100vh; font-family: 'Segoe UI', system-ui, sans-serif; }
.login-card { max-width: 420px; margin: 0 auto; border: none; border-radius: 20px; box-shadow: 0 20px 60px rgba(0,0,0,.35); overflow: hidden; }
.login-header { background: linear-gradient(135deg, #1a3a5c, #2563eb); padding: 2.5rem 2rem 2rem; text-align: center; }
.login-logo { font-size: 2.5rem; font-weight: 900; color: #fff; letter-spacing: -1px; }
.login-subtitle { color: rgba(255,255,255,.65); font-size: .82rem; margin-top: .25rem; }
.login-body { padding: 2rem; background: #fff; }
.form-control { border-radius: 10px; padding: .6rem .9rem; }
.form-floating label { color: #94a3b8; }
.btn-login { background: linear-gradient(135deg, #1a3a5c, #2563eb); border: none; border-radius: 10px; padding: .75rem; font-weight: 700; font-size: .95rem; letter-spacing: .02em; }
.btn-login:hover { background: linear-gradient(135deg, #162f4a, #1d4ed8); transform: translateY(-1px); box-shadow: 0 4px 15px rgba(37,99,235,.3); }
.login-footer { background: #f8fafc; padding: 1rem 2rem; text-align: center; border-top: 1px solid #f1f5f9; font-size: .8rem; color: #94a3b8; }
</style>
</head>
<body class="d-flex align-items-center" style="padding: 2rem 1rem;">
<div class="login-card card w-100">
  <div class="login-header">
    <div class="login-logo"><i class="bi bi-building-check me-2" style="color:#60a5fa"></i>ProETS</div>
    <div class="login-subtitle">Sistema Gestionale per Associazioni di Promozione Sociale</div>
  </div>
  <div class="login-body">
    <h6 class="fw-bold mb-3 text-center" style="color:#1e293b">Accedi al tuo account</h6>
    <?php if ($error): ?>
    <div class="alert alert-danger py-2 small"><i class="bi bi-exclamation-triangle me-2"></i><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="post" action="/auth/login" novalidate>
      <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">
      <div class="mb-3">
        <label class="form-label fw-semibold small" for="username">Username o Email</label>
        <div class="input-group">
          <span class="input-group-text bg-light border-end-0"><i class="bi bi-person text-muted"></i></span>
          <input type="text" id="username" name="username" class="form-control border-start-0"
            value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
            placeholder="username o email" required autocomplete="username">
        </div>
      </div>
      <div class="mb-3">
        <label class="form-label fw-semibold small" for="password">Password</label>
        <div class="input-group">
          <span class="input-group-text bg-light border-end-0"><i class="bi bi-lock text-muted"></i></span>
          <input type="password" id="password" name="password" class="form-control border-start-0"
            placeholder="password" required autocomplete="current-password">
          <button type="button" class="input-group-text btn btn-light border" id="toggle-pass" title="Mostra/nascondi">
            <i class="bi bi-eye" id="pass-eye"></i>
          </button>
        </div>
      </div>
      <div class="d-grid mt-4">
        <button type="submit" class="btn btn-primary btn-login text-white">
          <i class="bi bi-box-arrow-in-right me-2"></i>Accedi
        </button>
      </div>
      <div class="text-center mt-3">
        <a href="/auth/forgot-password" class="small text-muted">
          <i class="bi bi-question-circle me-1"></i>Password dimenticata?
        </a>
      </div>
    </form>
  </div>
  <div class="login-footer">
    ProETS v1.0.0 &mdash; <a href="https://www.gnu.org/licenses/gpl-3.0.html" target="_blank" class="text-muted">GNU GPL v3</a>
    &mdash; <a href="https://www.proets.it" target="_blank" class="text-muted">www.proets.it</a>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('toggle-pass')?.addEventListener('click', function() {
  const inp = document.getElementById('password');
  const eye = document.getElementById('pass-eye');
  if (inp.type === 'password') { inp.type = 'text'; eye.className = 'bi bi-eye-slash'; }
  else { inp.type = 'password'; eye.className = 'bi bi-eye'; }
});
</script>
</body>
</html>
