<?php
declare(strict_types=1);

namespace ProETS\Controllers;

use ProETS\Core\Controller;
use ProETS\Core\Database;
use ProETS\Core\Session;
use ProETS\Core\View;

class ConfigController extends Controller
{
    private function requireAdmin(): void { $this->requireAuth('config'); }

    public function index(array $params = []): void
    {
        $this->redirect('/configurazione/azienda');
    }

    public function azienda(array $params = []): void
    {
        $this->requireAdmin();
        $cid     = $this->companyId();
        $company = $this->company();
        $error   = null;

        if ($this->isPost()) {
            $this->csrfCheck();
            $data = [
                'ragione_sociale'      => $this->inputString('ragione_sociale'),
                'forma_giuridica'      => $this->inputString('forma_giuridica', 'APS'),
                'codice_fiscale'       => $this->inputString('codice_fiscale') ?: null,
                'partita_iva'          => $this->inputString('partita_iva') ?: null,
                'nr_iscrizione_runts'  => $this->inputString('nr_iscrizione_runts') ?: null,
                'indirizzo'            => $this->inputString('indirizzo') ?: null,
                'cap'                  => $this->inputString('cap') ?: null,
                'citta'                => $this->inputString('citta') ?: null,
                'provincia'            => strtoupper($this->inputString('provincia')) ?: null,
                'telefono'             => $this->inputString('telefono') ?: null,
                'email'                => $this->inputString('email') ?: null,
                'pec'                  => $this->inputString('pec') ?: null,
                'sito_web'             => $this->inputString('sito_web') ?: null,
                'esercizio_corrente'   => $this->inputInt('esercizio_corrente', (int)date('Y')),
                'data_costituzione'    => $this->inputString('data_costituzione') ?: null,
                'note'                 => $this->inputString('note') ?: null,
            ];

            // Upload logo
            if (!empty($_FILES['logo']['name']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
                $allowed = ['image/jpeg','image/png','image/gif','image/webp'];
                if (in_array($_FILES['logo']['type'], $allowed)) {
                    $ext     = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
                    $logoPath = 'uploads/loghi/' . $cid . '_logo.' . $ext;
                    $fullPath = PROETS_ROOT . '/' . $logoPath;
                    if (!is_dir(dirname($fullPath))) mkdir(dirname($fullPath), 0750, true);
                    move_uploaded_file($_FILES['logo']['tmp_name'], $fullPath);
                    $data['logo_path'] = $logoPath;
                }
            }

            Database::update('companies', $data, 'id = ?', [$cid]);
            // Aggiorna sessione
            $updatedCompany = Database::fetchOne("SELECT * FROM companies WHERE id = ?", [$cid]);
            Session::set('company', $updatedCompany);
            $this->flash('success', 'Dati associazione aggiornati.');
            $this->redirect('/configurazione/azienda');
        }

        // Conti
        $conti = Database::fetchAll("SELECT * FROM accounts WHERE company_id = ? ORDER BY ordine, nome", [$cid]);

        $esercizio = (int)($company['esercizio_corrente'] ?? date('Y'));
        View::render('config/azienda', [
            'pageTitle'    => 'Configurazione Associazione',
            'esercizioAttivo' => $esercizio,
            'company'      => $company,
            'conti'        => $conti,
            'csrf'         => Session::csrf(),
            'error'        => $error,
        ]);
    }

    public function email(array $params = []): void
    {
        $this->requireAdmin();
        $cid     = $this->companyId();
        $company = $this->company();
        $error   = null;

        if ($this->isPost()) {
            $this->csrfCheck();
            $settings = [
                'smtp_enabled'   => !empty($_POST['smtp_enabled']) ? '1' : '0',
                'smtp_host'      => $this->inputString('smtp_host'),
                'smtp_port'      => $this->inputString('smtp_port', '587'),
                'smtp_user'      => $this->inputString('smtp_user'),
                'smtp_secure'    => $this->inputString('smtp_secure', 'tls'),
                'smtp_from'      => $this->inputString('smtp_from'),
                'smtp_from_name' => $this->inputString('smtp_from_name', 'ProETS'),
                'smtp_reply_to'  => $this->inputString('smtp_reply_to'),
            ];
            if (!empty($_POST['smtp_pass'])) {
                $settings['smtp_pass'] = $_POST['smtp_pass'];
            }

            foreach ($settings as $key => $value) {
                $exists = Database::fetchColumn("SELECT COUNT(*) FROM app_settings WHERE chiave = ? AND company_id IS NULL", [$key]);
                if ($exists) {
                    Database::update('app_settings', ['valore' => $value], 'chiave = ? AND company_id IS NULL', [$key]);
                } else {
                    Database::insert('app_settings', ['chiave' => $key, 'valore' => $value, 'tipo' => 'string', 'gruppo' => 'email']);
                }
            }
            $this->flash('success', 'Configurazione email salvata.');
        }

        $smtpSettings = [];
        $rows = Database::fetchAll("SELECT chiave, valore FROM app_settings WHERE gruppo = 'email' AND company_id IS NULL");
        foreach ($rows as $r) $smtpSettings[$r['chiave']] = $r['valore'];

        $templates = Database::fetchAll(
            "SELECT * FROM email_templates WHERE company_id IS NULL OR company_id = ? ORDER BY nome",
            [$cid]
        );

        $esercizio = (int)($company['esercizio_corrente'] ?? date('Y'));
        View::render('config/email', [
            'pageTitle'      => 'Configurazione Email SMTP',
            'esercizioAttivo' => $esercizio,
            'company'        => $company,
            'settings'       => $smtpSettings,
            'templates'      => $templates,
            'csrf'           => Session::csrf(),
            'error'          => $error,
        ]);
    }

    public function utenti(array $params = []): void
    {
        $this->requireAdmin();
        $cid = $this->companyId();

        if ($this->isPost()) {
            $this->csrfCheck();
            $action = $this->inputString('action');

            if ($action === 'crea') {
                $username = $this->inputString('username');
                $email    = $this->inputString('email');
                $pass     = $this->input('password', '');
                $ruolo    = $this->inputString('ruolo', 'operator');
                $nome     = $this->inputString('nome');
                $cognome  = $this->inputString('cognome');

                if (strlen($pass) < 8) { $this->flash('error', 'Password troppo corta.'); }
                elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $this->flash('error', 'Email non valida.'); }
                else {
                    $hash = password_hash($pass, PASSWORD_BCRYPT, ['cost' => 12]);
                    $uid  = Database::insert('users', compact('username','email','nome','cognome','ruolo') + ['password'=>$hash,'attivo'=>1,'email_verificata'=>1,'gdpr_consenso'=>1,'gdpr_data'=>date('Y-m-d H:i:s')]);
                    Database::insert('user_companies', ['user_id'=>$uid,'company_id'=>$cid,'ruolo_azienda'=>$ruolo]);
                    $this->flash('success', 'Utente creato.');
                }
            } elseif ($action === 'disabilita') {
                Database::update('users', ['attivo' => 0], 'id = ?', [$this->inputInt('user_id')]);
                $this->flash('success', 'Utente disabilitato.');
            }
        }

        $utenti = Database::fetchAll(
            "SELECT u.*, uc.ruolo_azienda FROM users u
             LEFT JOIN user_companies uc ON uc.user_id = u.id AND uc.company_id = ?
             ORDER BY u.cognome, u.nome",
            [$cid]
        );

        $esercizio = (int)($this->company()['esercizio_corrente'] ?? date('Y'));
        View::render('config/utenti', [
            'pageTitle'    => 'Gestione Utenti',
            'esercizioAttivo' => $esercizio,
            'utenti'       => $utenti,
            'csrf'         => Session::csrf(),
        ]);
    }

    public function gdpr(array $params = []): void
    {
        $this->requireAdmin();
        $cid = $this->companyId();

        if ($this->isPost()) {
            $this->csrfCheck();
            $keys = ['gdpr_dpo_email', 'gdpr_data_retention_years'];
            foreach ($keys as $key) {
                $val = $this->inputString($key);
                $exists = Database::fetchColumn("SELECT COUNT(*) FROM app_settings WHERE chiave = ? AND company_id IS NULL", [$key]);
                if ($exists) Database::update('app_settings', ['valore' => $val], 'chiave = ? AND company_id IS NULL', [$key]);
                else Database::insert('app_settings', ['chiave'=>$key,'valore'=>$val,'tipo'=>'string','gruppo'=>'gdpr']);
            }
            $this->flash('success', 'Impostazioni GDPR salvate.');
        }

        $gdprSettings = [];
        $rows = Database::fetchAll("SELECT chiave, valore FROM app_settings WHERE gruppo = 'gdpr' AND company_id IS NULL");
        foreach ($rows as $r) $gdprSettings[$r['chiave']] = $r['valore'];

        // Audit log recente
        $audit = Database::fetchAll(
            "SELECT al.*, u.username FROM audit_log al LEFT JOIN users u ON u.id = al.user_id ORDER BY al.created_at DESC LIMIT 50"
        );

        $esercizio = (int)($this->company()['esercizio_corrente'] ?? date('Y'));
        View::render('config/gdpr', [
            'pageTitle'    => 'Impostazioni GDPR',
            'esercizioAttivo' => $esercizio,
            'settings'     => $gdprSettings,
            'audit'        => $audit,
            'csrf'         => Session::csrf(),
        ]);
    }

    public function quoteAnnuali(array $params = []): void
    {
        $this->requireAdmin();
        $cid = $this->companyId();

        if ($this->isPost()) {
            $this->csrfCheck();
            $anno  = $this->inputInt('anno', (int)date('Y'));
            $tipi  = ['ordinario','fondatore','onorario','sostenitore'];
            foreach ($tipi as $tipo) {
                $importo = $this->inputFloat('quota_' . $tipo, 0);
                if ($importo <= 0) continue;
                $exists = Database::fetchColumn("SELECT COUNT(*) FROM quote_annuali WHERE company_id = ? AND anno = ? AND tipo_socio = ?", [$cid, $anno, $tipo]);
                if ($exists) Database::update('quote_annuali', ['importo'=>$importo], 'company_id=? AND anno=? AND tipo_socio=?', [$cid,$anno,$tipo]);
                else Database::insert('quote_annuali', ['company_id'=>$cid,'anno'=>$anno,'tipo_socio'=>$tipo,'importo'=>$importo]);
            }
            $this->flash('success', 'Quote annuali salvate.');
        }

        $anno   = $this->inputInt('anno', (int)date('Y'));
        $quote  = Database::fetchAll("SELECT * FROM quote_annuali WHERE company_id = ? AND anno = ?", [$cid, $anno]);
        $quoteMap = [];
        foreach ($quote as $q) $quoteMap[$q['tipo_socio']] = $q['importo'];

        $esercizio = (int)($this->company()['esercizio_corrente'] ?? date('Y'));
        View::render('config/quote_annuali', [
            'pageTitle'    => 'Quote Annuali',
            'esercizioAttivo' => $esercizio,
            'anno'         => $anno,
            'quote'        => $quoteMap,
            'anni'         => range(date('Y') + 1, 2020),
            'csrf'         => Session::csrf(),
        ]);
    }
}
