<?php
declare(strict_types=1);

namespace ProETS\Controllers;

use ProETS\Core\Controller;
use ProETS\Core\Database;
use ProETS\Core\Session;
use ProETS\Core\View;
use ProETS\Services\MailerService;

class SociController extends Controller
{
    public function index(array $params = []): void
    {
        $this->requireAuth();
        $cid = $this->companyId();

        $cerca  = $this->inputString('cerca');
        $stato  = $this->inputString('stato', 'attivi');
        $tipo   = $this->inputString('tipo_socio', '');

        $where  = ['s.company_id = ?'];
        $wpar   = [$cid];

        if ($stato === 'attivi')    { $where[] = 's.attivo = 1'; }
        elseif ($stato === 'cessati') { $where[] = 's.attivo = 0'; }

        if ($tipo) { $where[] = 's.tipo_socio = ?'; $wpar[] = $tipo; }
        if ($cerca) { $where[] = '(s.cognome LIKE ? OR s.nome LIKE ? OR s.email LIKE ? OR s.codice_fiscale LIKE ? OR s.numero_tessera LIKE ?)'; for ($i=0;$i<5;$i++) $wpar[] = "%{$cerca}%"; }

        $ws  = implode(' AND ', $where);
        $soci = Database::fetchAll(
            "SELECT s.*,
             (SELECT qs.stato FROM quote_socio qs WHERE qs.socio_id=s.id AND qs.anno=YEAR(NOW()) LIMIT 1) AS stato_quota
             FROM soci s WHERE {$ws} ORDER BY s.cognome, s.nome",
            $wpar
        );

        $esercizio = (int)($this->company()['esercizio_corrente'] ?? date('Y'));
        View::render('soci/index', [
            'pageTitle'    => 'Anagrafica Soci',
            'esercizioAttivo' => $esercizio,
            'soci'         => $soci,
            'cerca'        => $cerca,
            'stato'        => $stato,
            'tipo'         => $tipo,
            'totale'       => count($soci),
        ]);
    }

    public function create(array $params = []): void
    {
        $this->requireAuth('write');
        $cid   = $this->companyId();
        $error = null;

        if ($this->isPost()) {
            $this->csrfCheck();
            $data = $this->sociDataFromPost();
            $data['company_id'] = $cid;

            if (empty($data['cognome']) || empty($data['nome'])) {
                $error = 'Nome e cognome sono obbligatori.';
            } else {
                // Genera numero tessera automatico se non inserito
                if (empty($data['numero_tessera'])) {
                    $last = Database::fetchColumn("SELECT MAX(CAST(numero_tessera AS UNSIGNED)) FROM soci WHERE company_id = ?", [$cid]);
                    $data['numero_tessera'] = str_pad((int)$last + 1, 4, '0', STR_PAD_LEFT);
                }
                $id = Database::insert('soci', $data);
                // Invia email di benvenuto se ha email e privacy consenso
                if (!empty($data['email']) && $data['privacy_consenso']) {
                    MailerService::sendWelcome(
                        array_merge($data, ['id' => $id]),
                        $this->company()
                    );
                }
                $this->flash('success', 'Socio registrato con successo. Tessera: ' . $data['numero_tessera']);
                $this->redirect('/soci');
            }
        }

        $esercizio = (int)($this->company()['esercizio_corrente'] ?? date('Y'));
        View::render('soci/form', [
            'pageTitle'    => 'Nuovo Socio',
            'esercizioAttivo' => $esercizio,
            'error'        => $error,
            'socio'        => null,
            'csrf'         => Session::csrf(),
        ]);
    }

    public function edit(array $params = []): void
    {
        $this->requireAuth('write');
        $cid   = $this->companyId();
        $id    = (int)($params['id'] ?? 0);
        $socio = Database::fetchOne("SELECT * FROM soci WHERE id = ? AND company_id = ?", [$id, $cid]);
        if (!$socio) \ProETS\Core\Router::abort(404);

        $error = null;
        if ($this->isPost()) {
            $this->csrfCheck();
            $data = $this->sociDataFromPost();
            unset($data['company_id']); // Non aggiornare company
            Database::update('soci', $data, 'id = ?', [$id]);
            $this->flash('success', 'Dati socio aggiornati.');
            $this->redirect('/soci/' . $id);
        }

        $esercizio = (int)($this->company()['esercizio_corrente'] ?? date('Y'));
        View::render('soci/form', [
            'pageTitle'    => 'Modifica Socio',
            'esercizioAttivo' => $esercizio,
            'error'        => $error,
            'socio'        => $socio,
            'csrf'         => Session::csrf(),
        ]);
    }

    public function show(array $params = []): void
    {
        $this->requireAuth();
        $cid   = $this->companyId();
        $id    = (int)($params['id'] ?? 0);
        $socio = Database::fetchOne("SELECT * FROM soci WHERE id = ? AND company_id = ?", [$id, $cid]);
        if (!$socio) \ProETS\Core\Router::abort(404);

        $quote = Database::fetchAll(
            "SELECT qs.*, pn.data_movimento AS data_pn FROM quote_socio qs
             LEFT JOIN prima_nota pn ON pn.id = qs.prima_nota_id
             WHERE qs.socio_id = ? ORDER BY qs.anno DESC",
            [$id]
        );

        $esercizio = (int)($this->company()['esercizio_corrente'] ?? date('Y'));
        View::render('soci/show', [
            'pageTitle'    => $socio['cognome'] . ' ' . $socio['nome'],
            'esercizioAttivo' => $esercizio,
            'socio'        => $socio,
            'quote'        => $quote,
            'csrf'         => Session::csrf(),
        ]);
    }

    public function delete(array $params = []): void
    {
        $this->requireAuth('delete');
        $this->csrfCheck();
        $cid = $this->companyId();
        $id  = (int)($params['id'] ?? 0);
        // Soft delete: imposta attivo=0 e data cessazione
        Database::update('soci', ['attivo' => 0, 'data_cessazione' => date('Y-m-d')], 'id = ? AND company_id = ?', [$id, $cid]);
        $this->flash('success', 'Socio cessato.');
        $this->redirect('/soci');
    }

    public function quote(array $params = []): void
    {
        $this->requireAuth();
        $cid       = $this->companyId();
        $esercizio = $this->inputInt('anno', (int)($this->company()['esercizio_corrente'] ?? date('Y')));

        $quote = Database::fetchAll(
            "SELECT qs.*, s.cognome, s.nome, s.email, s.tipo_socio
             FROM quote_socio qs JOIN soci s ON s.id = qs.socio_id
             WHERE qs.company_id = ? AND qs.anno = ? AND s.attivo = 1
             ORDER BY qs.stato, s.cognome",
            [$cid, $esercizio]
        );

        // Statistiche
        $stats = ['attesa'=>0,'parziale'=>0,'pagata'=>0,'esonerata'=>0,'importo_dovuto'=>0,'importo_versato'=>0];
        foreach ($quote as $q) {
            $stats[$q['stato']] = ($stats[$q['stato']] ?? 0) + 1;
            $stats['importo_dovuto']  += $q['importo_dovuto'];
            $stats['importo_versato'] += $q['importo_versato'];
        }

        View::render('soci/quote', [
            'pageTitle'    => 'Quote Associative ' . $esercizio,
            'esercizioAttivo' => $esercizio,
            'quote'        => $quote,
            'stats'        => $stats,
            'esercizio'    => $esercizio,
            'anni'         => range(date('Y'), 2020),
            'csrf'         => Session::csrf(),
        ]);
    }

    public function generaQuote(array $params = []): void
    {
        $this->requireAuth('write');
        $this->csrfCheck();
        $cid       = $this->companyId();
        $esercizio = $this->inputInt('anno', (int)($this->company()['esercizio_corrente'] ?? date('Y')));

        $soci = Database::fetchAll(
            "SELECT * FROM soci WHERE company_id = ? AND attivo = 1", [$cid]
        );

        $generated = 0;
        foreach ($soci as $socio) {
            // Controlla se esiste già
            $exists = Database::fetchColumn(
                "SELECT COUNT(*) FROM quote_socio WHERE socio_id = ? AND anno = ?",
                [$socio['id'], $esercizio]
            );
            if ($exists) continue;

            // Quota personalizzata o standard
            $importo = $socio['quota_annuale'];
            if (!$importo) {
                $stdQuota = Database::fetchOne(
                    "SELECT importo FROM quote_annuali WHERE company_id = ? AND anno = ? AND tipo_socio = ?",
                    [$cid, $esercizio, $socio['tipo_socio']]
                );
                $importo = $stdQuota ? (float)$stdQuota['importo'] : 0;
            }
            if ($importo <= 0) continue;

            Database::insert('quote_socio', [
                'company_id'    => $cid,
                'socio_id'      => $socio['id'],
                'anno'          => $esercizio,
                'importo_dovuto' => $importo,
                'stato'         => 'attesa',
            ]);
            $generated++;
        }

        $this->flash('success', "Generate {$generated} quote per l'anno {$esercizio}.");
        $this->redirect('/soci/quote?anno=' . $esercizio);
    }

    public function comunicazioni(array $params = []): void
    {
        $this->requireAuth();
        $cid = $this->companyId();

        $storico = Database::fetchAll(
            "SELECT c.*, u.username AS operatore FROM comunicazioni c JOIN users u ON u.id = c.user_id
             WHERE c.company_id = ? ORDER BY c.created_at DESC LIMIT 20",
            [$cid]
        );

        $esercizio = (int)($this->company()['esercizio_corrente'] ?? date('Y'));
        View::render('soci/comunicazioni', [
            'pageTitle'    => 'Comunicazioni Massive',
            'esercizioAttivo' => $esercizio,
            'storico'      => $storico,
            'csrf'         => Session::csrf(),
        ]);
    }

    public function inviaComunicazione(array $params = []): void
    {
        $this->requireAuth('write');
        $this->csrfCheck();
        $cid  = $this->companyId();
        $tipo = $this->inputString('tipo');
        $oggetto = $this->inputString('oggetto');
        $corpo   = $this->input('corpo', '');
        $filtroStato = $this->inputString('filtro_stato', '');
        $anno   = $this->inputInt('anno', (int)($this->company()['esercizio_corrente'] ?? date('Y')));

        // Trova destinatari
        $where = ['s.company_id = ?', 's.attivo = 1', 's.email IS NOT NULL', "s.email != ''", 's.marketing_consenso = 1'];
        $wpar  = [$cid];
        if ($filtroStato && $tipo === 'richiesta_quota') {
            $where[] = "qs.stato IN ('attesa','parziale')";
            $joinQuote = "JOIN quote_socio qs ON qs.socio_id = s.id AND qs.anno = {$anno}";
        } else {
            $joinQuote = '';
        }

        $destinatari = Database::fetchAll(
            "SELECT s.id, s.nome, s.cognome, s.email, s.tipo_socio
             FROM soci s {$joinQuote}
             WHERE " . implode(' AND ', $where),
            $wpar
        );

        $commId = Database::insert('comunicazioni', [
            'company_id'          => $cid,
            'oggetto'             => $oggetto,
            'corpo'               => $corpo,
            'tipo'                => $tipo,
            'totale_destinatari'  => count($destinatari),
            'stato'               => 'in_corso',
            'user_id'             => \ProETS\Core\Auth::id(),
        ]);

        $inviati = 0; $errori = 0;
        foreach ($destinatari as $dest) {
            $bodyPers = strtr($corpo, [
                '{{nome_socio}}' => $dest['nome'] . ' ' . $dest['cognome'],
                '{{nome}}'       => $dest['nome'],
            ]);
            if (MailerService::send($dest['email'], $dest['nome'] . ' ' . $dest['cognome'], $oggetto, $bodyPers)) {
                $inviati++;
            } else {
                $errori++;
            }
        }

        Database::update('comunicazioni', [
            'inviati'    => $inviati,
            'errori'     => $errori,
            'stato'      => 'completata',
            'data_invio' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$commId]);

        $this->flash('success', "Comunicazione inviata a {$inviati} soci. Errori: {$errori}.");
        $this->redirect('/soci/comunicazioni');
    }

    public function export(array $params = []): void
    {
        $this->requireAuth('export');
        $cid = $this->companyId();
        $soci = Database::fetchAll("SELECT * FROM soci WHERE company_id = ? ORDER BY cognome, nome", [$cid]);

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="soci_'.date('Ymd').'.csv"');
        $out = fopen('php://output', 'w');
        fputs($out, "\xEF\xBB\xBF");
        fputcsv($out, ['N.Tessera','Cognome','Nome','CF','Data Nascita','Email','Telefono','Tipo','Data Iscrizione','Stato'], ';');
        foreach ($soci as $s) {
            fputcsv($out, [
                $s['numero_tessera'],$s['cognome'],$s['nome'],$s['codice_fiscale'],
                $s['data_nascita'],$s['email'],$s['telefono'] ?? $s['cellulare'],
                $s['tipo_socio'],$s['data_iscrizione'],$s['attivo']?'Attivo':'Cessato'
            ], ';');
        }
        fclose($out);
        exit;
    }

    public function tessera(array $params = []): void
    {
        $this->requireAuth('export');
        // Redirect: generato come PDF tramite PdfService
        $id = (int)($params['id'] ?? 0);
        $cid = $this->companyId();
        $socio = Database::fetchOne("SELECT * FROM soci WHERE id = ? AND company_id = ?", [$id, $cid]);
        if (!$socio) \ProETS\Core\Router::abort(404);

        $pdf = new \ProETS\Services\PdfService();
        $pdf->tessera($socio, $this->company());
    }

    public function pagaQuota(array $params = []): void
    {
        // Pagina pubblica per pagamento quota
        $token = $this->inputString('token');
        $quota = Database::fetchOne(
            "SELECT qs.*, s.nome, s.cognome, s.email, c.ragione_sociale, c.iban AS iban_assoc
             FROM quote_socio qs
             JOIN soci s ON s.id = qs.socio_id
             JOIN companies c ON c.id = qs.company_id
             WHERE qs.token_pagamento = ? AND qs.token_scadenza > NOW() AND qs.stato IN ('attesa','parziale')",
            [$token]
        );

        if (!$quota) {
            View::renderRaw('soci/paga_quota_invalid', ['pageTitle' => 'Link non valido']);
            return;
        }

        View::renderRaw('soci/paga_quota', [
            'pageTitle' => 'Pagamento quota associativa',
            'quota'     => $quota,
            'csrf'      => Session::csrf(),
        ]);
    }

    // ---- Helpers ----

    private function sociDataFromPost(): array
    {
        return [
            'numero_tessera'    => $this->inputString('numero_tessera') ?: null,
            'cognome'           => $this->inputString('cognome'),
            'nome'              => $this->inputString('nome'),
            'codice_fiscale'    => strtoupper($this->inputString('codice_fiscale')) ?: null,
            'data_nascita'      => $this->inputString('data_nascita') ?: null,
            'luogo_nascita'     => $this->inputString('luogo_nascita') ?: null,
            'sesso'             => $this->inputString('sesso') ?: null,
            'indirizzo'         => $this->inputString('indirizzo') ?: null,
            'cap'               => $this->inputString('cap') ?: null,
            'citta'             => $this->inputString('citta') ?: null,
            'provincia'         => strtoupper($this->inputString('provincia')) ?: null,
            'email'             => strtolower($this->inputString('email')) ?: null,
            'telefono'          => $this->inputString('telefono') ?: null,
            'cellulare'         => $this->inputString('cellulare') ?: null,
            'tipo_socio'        => $this->inputString('tipo_socio', 'ordinario'),
            'data_iscrizione'   => $this->inputString('data_iscrizione') ?: date('Y-m-d'),
            'quota_annuale'     => $this->inputString('quota_annuale') ? $this->inputFloat('quota_annuale') : null,
            'note'              => $this->inputString('note') ?: null,
            'privacy_consenso'  => !empty($_POST['privacy_consenso']) ? 1 : 0,
            'privacy_data'      => !empty($_POST['privacy_consenso']) ? date('Y-m-d H:i:s') : null,
            'marketing_consenso'=> !empty($_POST['marketing_consenso']) ? 1 : 0,
            'newsletter_consenso'=>!empty($_POST['newsletter_consenso']) ? 1 : 0,
            'attivo'            => 1,
        ];
    }
}
