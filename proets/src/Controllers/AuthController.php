<?php
declare(strict_types=1);

namespace ProETS\Controllers;

use ProETS\Core\Auth;
use ProETS\Core\Controller;
use ProETS\Core\Database;
use ProETS\Core\Session;
use ProETS\Core\View;
use ProETS\Services\MailerService;

class AuthController extends Controller
{
    public function login(array $params = []): void
    {
        if (Auth::check()) {
            $this->redirect('/dashboard');
        }

        $error = null;

        if ($this->isPost()) {
            $this->csrfCheck();
            $username = $this->inputString('username');
            $password = $this->input('password', '');

            if (empty($username) || empty($password)) {
                $error = 'Username e password obbligatori.';
            } elseif (Auth::attempt($username, $password)) {
                $redirect = Session::flash('redirect_after_login') ?? '/dashboard';
                $this->redirect($redirect);
            } else {
                $error = Session::flash('error') ?? 'Credenziali non valide o account bloccato.';
            }
        }

        View::renderRaw('auth/login', [
            'error'     => $error,
            'csrf'      => Session::csrf(),
            'pageTitle' => 'Accedi a ProETS',
        ]);
    }

    public function logout(array $params = []): void
    {
        Auth::logout();
        $this->redirect('/auth/login');
    }

    public function forgotPassword(array $params = []): void
    {
        if (Auth::check()) $this->redirect('/dashboard');

        $sent  = false;
        $error = null;

        if ($this->isPost()) {
            $this->csrfCheck();
            $email = filter_var($this->inputString('email'), FILTER_VALIDATE_EMAIL);
            if (!$email) {
                $error = 'Inserisci un indirizzo email valido.';
            } else {
                $user = Database::fetchOne("SELECT id, nome FROM users WHERE email = ? AND attivo = 1", [$email]);
                if ($user) {
                    // Genera token
                    $token     = bin2hex(random_bytes(32));
                    $expiresAt = date('Y-m-d H:i:s', time() + 3600); // 1 ora
                    // Invalida token precedenti
                    Database::query("UPDATE password_resets SET usato = 1 WHERE email = ?", [$email]);
                    Database::insert('password_resets', [
                        'email'      => $email,
                        'token'      => $token,
                        'expires_at' => $expiresAt,
                    ]);
                    // Invia email
                    $baseUrl  = rtrim($_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'], '/');
                    $resetUrl = $baseUrl . '/auth/reset-password?token=' . $token;
                    try {
                        MailerService::sendPasswordReset($email, $user['nome'], $resetUrl);
                    } catch (\Throwable $e) {
                        error_log('[ProETS] Email reset error: ' . $e->getMessage());
                    }
                }
                // Risposta generica (no user enumeration)
                $sent = true;
            }
        }

        View::renderRaw('auth/forgot-password', [
            'sent'      => $sent,
            'error'     => $error,
            'csrf'      => Session::csrf(),
            'pageTitle' => 'Recupero Password',
        ]);
    }

    public function resetPassword(array $params = []): void
    {
        if (Auth::check()) $this->redirect('/dashboard');

        $token    = $this->inputString('token');
        $error    = null;
        $success  = false;

        // Valida token
        $reset = Database::fetchOne(
            "SELECT * FROM password_resets WHERE token = ? AND usato = 0 AND expires_at > NOW() ORDER BY id DESC LIMIT 1",
            [$token]
        );
        if (!$reset) {
            $error = 'Link non valido o scaduto. Richiedine uno nuovo.';
        }

        if (!$error && $this->isPost()) {
            $this->csrfCheck();
            $pass  = $this->input('password', '');
            $pass2 = $this->input('password2', '');

            if (strlen($pass) < 8) {
                $error = 'La password deve essere di almeno 8 caratteri.';
            } elseif ($pass !== $pass2) {
                $error = 'Le password non coincidono.';
            } else {
                $hash = password_hash($pass, PASSWORD_BCRYPT, ['cost' => 12]);
                $user = Database::fetchOne("SELECT id FROM users WHERE email = ?", [$reset['email']]);
                if ($user) {
                    Database::update('users', ['password' => $hash, 'tentativi_login' => 0, 'bloccato_fino' => null], 'id = ?', [$user['id']]);
                    Database::update('password_resets', ['usato' => 1], 'token = ?', [$token]);
                    $success = true;
                }
            }
        }

        View::renderRaw('auth/reset-password', [
            'token'     => $token,
            'error'     => $error,
            'success'   => $success,
            'tokenValid' => ($reset !== false && !$error),
            'csrf'      => Session::csrf(),
            'pageTitle' => 'Reimposta Password',
        ]);
    }
}
