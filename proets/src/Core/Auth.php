<?php
declare(strict_types=1);

namespace ProETS\Core;

use ProETS\Models\User;

class Auth
{
    private static ?array $user = null;

    public static function attempt(string $username, string $password): bool
    {
        $user = Database::fetchOne(
            "SELECT * FROM users WHERE (username = ? OR email = ?) AND attivo = 1 LIMIT 1",
            [$username, $username]
        );

        if (!$user) return false;

        // Controllo blocco account
        if ($user['bloccato_fino'] && strtotime($user['bloccato_fino']) > time()) {
            Session::flash('error', 'Account temporaneamente bloccato. Riprova tra qualche minuto.');
            return false;
        }

        if (!password_verify($password, $user['password'])) {
            // Incrementa tentativi falliti
            $tentativi = (int)$user['tentativi_login'] + 1;
            $bloccatoFino = null;
            if ($tentativi >= 5) {
                $bloccatoFino = date('Y-m-d H:i:s', time() + 900); // 15 min
                $tentativi = 0;
            }
            Database::update('users',
                ['tentativi_login' => $tentativi, 'bloccato_fino' => $bloccatoFino],
                'id = ?', [$user['id']]
            );
            return false;
        }

        // Login riuscito - reset tentativi
        Database::update('users', [
            'tentativi_login'    => 0,
            'bloccato_fino'      => null,
            'ultimo_accesso'     => date('Y-m-d H:i:s'),
            'ip_ultimo_accesso'  => self::getIp(),
        ], 'id = ?', [$user['id']]);

        // Audit log
        self::auditLog('LOGIN', $user['id']);

        // Salva sessione
        session_regenerate_id(true);
        Session::set('user_id', $user['id']);
        Session::set('user', self::sanitizeUser($user));

        // Carica azienda attiva (prima disponibile)
        self::loadCompany($user['id'], Session::get('company_id'));

        self::$user = Session::get('user');
        return true;
    }

    public static function logout(): void
    {
        if ($uid = Session::get('user_id')) {
            self::auditLog('LOGOUT', $uid);
        }
        Session::destroy();
        self::$user = null;
    }

    public static function check(): bool
    {
        if (self::$user !== null) return true;
        $uid = Session::get('user_id');
        if (!$uid) return false;

        $user = Database::fetchOne("SELECT * FROM users WHERE id = ? AND attivo = 1", [$uid]);
        if (!$user) { Session::destroy(); return false; }

        self::$user = Session::get('user');
        return true;
    }

    public static function user(): ?array
    {
        return self::$user ?? Session::get('user');
    }

    public static function id(): ?int
    {
        return self::user()['id'] ?? null;
    }

    public static function companyId(): ?int
    {
        return Session::get('company_id');
    }

    public static function company(): ?array
    {
        return Session::get('company');
    }

    public static function can(string $permission): bool
    {
        $role = self::user()['ruolo'] ?? 'readonly';
        $permissions = [
            'superadmin' => ['*'],
            'admin'      => ['read','write','delete','import','export','config'],
            'operator'   => ['read','write','import','export'],
            'readonly'   => ['read','export'],
        ];
        $allowed = $permissions[$role] ?? [];
        return in_array('*', $allowed) || in_array($permission, $allowed);
    }

    public static function require(string $permission = 'read'): void
    {
        if (!self::check()) {
            Session::flash('redirect_after_login', $_SERVER['REQUEST_URI'] ?? '/');
            header('Location: /auth/login'); exit;
        }
        if (!self::can($permission)) {
            http_response_code(403);
            include PROETS_ROOT . '/templates/errors/403.php';
            exit;
        }
    }

    public static function switchCompany(int $companyId): bool
    {
        $uid = self::id();
        if (!$uid) return false;
        // Verifica accesso
        $access = Database::fetchOne(
            "SELECT * FROM user_companies WHERE user_id = ? AND company_id = ? AND attivo = 1",
            [$uid, $companyId]
        );
        $isSuperAdmin = (self::user()['ruolo'] ?? '') === 'superadmin';
        if (!$access && !$isSuperAdmin) return false;

        self::loadCompany($uid, $companyId);
        return true;
    }

    // ---------- Private helpers ----------

    private static function loadCompany(int $userId, ?int $preferredId = null): void
    {
        $role = Session::get('user')['ruolo'] ?? 'operator';

        if ($role === 'superadmin') {
            // Superadmin vede tutte le aziende
            if ($preferredId) {
                $company = Database::fetchOne("SELECT * FROM companies WHERE id = ? AND attivo = 1", [$preferredId]);
            }
            if (empty($company)) {
                $company = Database::fetchOne("SELECT * FROM companies WHERE attivo = 1 ORDER BY id LIMIT 1");
            }
        } else {
            $sql = "SELECT c.* FROM companies c
                    JOIN user_companies uc ON uc.company_id = c.id
                    WHERE uc.user_id = ? AND c.attivo = 1";
            $params = [$userId];
            if ($preferredId) {
                $sql .= " AND c.id = ?";
                $params[] = $preferredId;
            }
            $sql .= " ORDER BY c.id LIMIT 1";
            $company = Database::fetchOne($sql, $params);
        }

        if ($company) {
            Session::set('company_id', (int)$company['id']);
            Session::set('company', $company);
        }
    }

    private static function sanitizeUser(array $user): array
    {
        unset($user['password'], $user['token_verifica']);
        return $user;
    }

    private static function getIp(): string
    {
        return $_SERVER['HTTP_X_FORWARDED_FOR']
            ?? $_SERVER['HTTP_X_REAL_IP']
            ?? $_SERVER['REMOTE_ADDR']
            ?? '0.0.0.0';
    }

    private static function auditLog(string $azione, int $userId): void
    {
        try {
            Database::insert('audit_log', [
                'user_id'    => $userId,
                'company_id' => Session::get('company_id'),
                'azione'     => $azione,
                'ip'         => self::getIp(),
                'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500),
            ]);
        } catch (\Throwable) {
            // Non bloccare il login per un errore di audit
        }
    }
}
