<?php
declare(strict_types=1);

namespace ProETS\Controllers;

use ProETS\Core\Auth;
use ProETS\Core\Controller;
use ProETS\Core\Database;
use ProETS\Core\Router;
use ProETS\Core\Session;
use ProETS\Core\View;

class SuperadminController extends Controller
{
    private function requireSuperadmin(): void
    {
        $this->requireAuth();
        if ((Auth::user()['ruolo'] ?? '') !== 'superadmin') {
            Router::abort(403, 'Accesso riservato al superadmin.');
        }
    }

    /* ── Lista aziende + form creazione ────────────────────────────── */
    public function index(array $params = []): void
    {
        $this->requireSuperadmin();

        if ($this->isPost()) {
            $this->csrfCheck();
            $this->handleCreaAzienda();
        }

        $companies = Database::fetchAll(
            "SELECT c.*,
                COUNT(DISTINCT uc.user_id) AS n_utenti,
                COUNT(DISTINCT CASE WHEN uc.ruolo_azienda = 'admin' THEN uc.user_id END) AS n_admin
             FROM companies c
             LEFT JOIN user_companies uc ON uc.company_id = c.id AND uc.attivo = 1
             GROUP BY c.id
             ORDER BY c.attivo DESC, c.ragione_sociale"
        );

        $esercizio = (int)(Auth::company()['esercizio_corrente'] ?? date('Y'));
        View::render('superadmin/index', [
            'pageTitle'       => 'Pannello Superadmin — Associazioni',
            'esercizioAttivo' => $esercizio,
            'company'         => Auth::company(),
            'companies'       => $companies,
            'csrf'            => Session::csrf(),
            'currentUser'     => Auth::user(),
        ]);
    }

    /* ── Toggle attivo/disabilitato ────────────────────────────────── */
    public function toggleAzienda(array $params = []): void
    {
        $this->requireSuperadmin();
        $this->csrfCheck();

        $id      = (int)($params['id'] ?? 0);
        $company = Database::fetchOne("SELECT id, ragione_sociale, attivo FROM companies WHERE id = ?", [$id]);
        if (!$company) {
            $this->flash('error', 'Associazione non trovata.');
            $this->redirect('/superadmin');
        }

        // Non permettere di disabilitare l'azienda attiva corrente
        if ($id === Auth::companyId() && $company['attivo']) {
            $this->flash('error', 'Non puoi disabilitare l\'associazione attualmente attiva. Passa prima a un\'altra.');
            $this->redirect('/superadmin');
        }

        $newState = $company['attivo'] ? 0 : 1;
        Database::update('companies', ['attivo' => $newState], 'id = ?', [$id]);
        $label = $newState ? 'riabilitata' : 'disabilitata';
        $this->flash('success', "Associazione \"{$company['ragione_sociale']}\" {$label}.");
        $this->redirect('/superadmin');
    }

    /* ── Lista utenti globale + creazione admin ─────────────────────── */
    public function utenti(array $params = []): void
    {
        $this->requireSuperadmin();

        if ($this->isPost()) {
            $this->csrfCheck();
            $action = $this->inputString('action');

            if ($action === 'crea_admin') {
                $this->handleCreaAdmin();
            } elseif ($action === 'toggle') {
                $uid  = $this->inputInt('user_id');
                $me   = Auth::id();
                if ($uid === $me) {
                    $this->flash('error', 'Non puoi disabilitare te stesso.');
                } else {
                    $u = Database::fetchOne("SELECT attivo FROM users WHERE id = ?", [$uid]);
                    if ($u) {
                        Database::update('users', ['attivo' => $u['attivo'] ? 0 : 1], 'id = ?', [$uid]);
                        $this->flash('success', 'Stato utente aggiornato.');
                    }
                }
            } elseif ($action === 'reset_password') {
                $this->handleResetPassword($this->inputInt('user_id'));
            }
            $this->redirect('/superadmin/utenti');
        }

        $companies = Database::fetchAll(
            "SELECT id, ragione_sociale FROM companies WHERE attivo = 1 ORDER BY ragione_sociale"
        );

        $utenti = Database::fetchAll(
            "SELECT u.*,
                CONCAT(u.nome, ' ', u.cognome) AS nome_completo,
                GROUP_CONCAT(c.ragione_sociale ORDER BY c.ragione_sociale SEPARATOR ' | ') AS aziende
             FROM users u
             LEFT JOIN user_companies uc ON uc.user_id = u.id
             LEFT JOIN companies c ON c.id = uc.company_id
             GROUP BY u.id
             ORDER BY FIELD(u.ruolo,'superadmin','admin','operator','readonly'), u.cognome, u.nome"
        );

        $esercizio = (int)(Auth::company()['esercizio_corrente'] ?? date('Y'));
        View::render('superadmin/utenti', [
            'pageTitle'       => 'Pannello Superadmin — Utenti',
            'esercizioAttivo' => $esercizio,
            'company'         => Auth::company(),
            'utenti'          => $utenti,
            'companies'       => $companies,
            'csrf'            => Session::csrf(),
            'currentUser'     => Auth::user(),
        ]);
    }

    /* ── Private helpers ────────────────────────────────────────────── */

    private function handleCreaAzienda(): void
    {
        $ragSoc    = $this->inputString('ragione_sociale');
        $formaGiur = $this->inputString('forma_giuridica', 'APS');
        $esercizio = $this->inputInt('esercizio_corrente', (int)date('Y'));
        $cf        = $this->inputString('codice_fiscale') ?: null;
        $email     = $this->inputString('email') ?: null;
        $citta     = $this->inputString('citta') ?: null;

        if (!$ragSoc) {
            $this->flash('error', 'Ragione sociale obbligatoria.');
            return;
        }

        // Genera codice univoco
        $base = strtoupper(preg_replace('/[^A-Z0-9]/', '', $ragSoc));
        $base = substr($base ?: 'ASS', 0, 6);
        $i = 1;
        do {
            $codice = $base . str_pad((string)$i, 3, '0', STR_PAD_LEFT);
            $exists = Database::fetchColumn("SELECT COUNT(*) FROM companies WHERE codice = ?", [$codice]);
            $i++;
        } while ($exists);

        $companyId = Database::insert('companies', [
            'codice'            => $codice,
            'ragione_sociale'   => $ragSoc,
            'forma_giuridica'   => $formaGiur,
            'codice_fiscale'    => $cf,
            'email'             => $email,
            'citta'             => $citta,
            'esercizio_corrente'=> $esercizio,
        ]);

        // Conti di default
        Database::insert('accounts', [
            'company_id' => $companyId, 'codice' => 'CASSA01',
            'nome' => 'Cassa Contanti', 'tipo' => 'cassa', 'ordine' => 1,
        ]);
        Database::insert('accounts', [
            'company_id' => $companyId, 'codice' => 'BANCA01',
            'nome' => 'Conto Bancario', 'tipo' => 'banca', 'ordine' => 2,
        ]);

        $this->flash('success', "Associazione \"{$ragSoc}\" creata (codice: {$codice}). Ora crea l'amministratore dalla sezione Utenti.");
    }

    private function handleCreaAdmin(): void
    {
        $companyId = $this->inputInt('company_id');
        $username  = $this->inputString('username');
        $email     = $this->inputString('email');
        $pass      = $this->input('password', '');
        $nome      = $this->inputString('nome');
        $cognome   = $this->inputString('cognome');
        $ruolo     = $this->inputString('ruolo', 'admin');

        // Superadmin può assegnare admin, operator, readonly (non superadmin)
        if (!in_array($ruolo, ['admin', 'operator', 'readonly'])) {
            $ruolo = 'admin';
        }

        if (!$companyId) {
            $this->flash('error', 'Seleziona un\'associazione.');
            return;
        }
        if (strlen((string)$pass) < 8) {
            $this->flash('error', 'Password troppo corta (minimo 8 caratteri).');
            return;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->flash('error', 'Email non valida.');
            return;
        }
        if (!$username) {
            $this->flash('error', 'Username obbligatorio.');
            return;
        }

        // Verifica unicità
        $existing = Database::fetchColumn(
            "SELECT COUNT(*) FROM users WHERE username = ? OR email = ?",
            [$username, $email]
        );
        if ($existing) {
            $this->flash('error', 'Username o email già in uso.');
            return;
        }

        $hash = password_hash((string)$pass, PASSWORD_BCRYPT, ['cost' => 12]);
        $uid  = Database::insert('users', [
            'username'        => $username,
            'email'           => $email,
            'password'        => $hash,
            'nome'            => $nome,
            'cognome'         => $cognome,
            'ruolo'           => $ruolo,
            'attivo'          => 1,
            'email_verificata'=> 1,
            'gdpr_consenso'   => 1,
            'gdpr_data'       => date('Y-m-d H:i:s'),
        ]);

        // Collega all'azienda
        Database::insert('user_companies', [
            'user_id'      => $uid,
            'company_id'   => $companyId,
            'ruolo_azienda'=> $ruolo,
        ]);

        $company = Database::fetchOne("SELECT ragione_sociale FROM companies WHERE id = ?", [$companyId]);
        $this->flash('success', "Utente \"{$username}\" ({$ruolo}) creato e associato a \"{$company['ragione_sociale']}\".");
    }

    private function handleResetPassword(int $uid): void
    {
        if (!$uid) return;
        $newPass = bin2hex(random_bytes(6)); // 12 char hex
        $hash    = password_hash($newPass, PASSWORD_BCRYPT, ['cost' => 12]);
        Database::update('users', [
            'password'        => $hash,
            'tentativi_login' => 0,
            'bloccato_fino'   => null,
        ], 'id = ?', [$uid]);
        $this->flash('success', "Password reimpostata. Nuova password temporanea: <strong>{$newPass}</strong> — comunicala all'utente.");
    }
}
