<?php
declare(strict_types=1);

namespace ProETS\Controllers;

use ProETS\Core\Controller;
use ProETS\Core\Database;
use ProETS\Core\Session;
use ProETS\Core\View;
use ProETS\Services\ImportService;

class PrimaNotaController extends Controller
{
    private function getCausali(): array
    {
        return Database::fetchAll(
            "SELECT * FROM causali WHERE (company_id IS NULL OR company_id = ?) AND attivo = 1 ORDER BY ordine",
            [$this->companyId()]
        );
    }

    private function getConti(): array
    {
        return Database::fetchAll(
            "SELECT * FROM accounts WHERE company_id = ? AND attivo = 1 ORDER BY ordine, nome",
            [$this->companyId()]
        );
    }

    public function index(array $params = []): void
    {
        $this->requireAuth();
        $cid       = $this->companyId();
        $esercizio = (int)($this->company()['esercizio_corrente'] ?? date('Y'));

        // Filtri
        $filtro = [
            'anno'       => $this->inputInt('anno', $esercizio),
            'mese'       => $this->inputInt('mese', 0),
            'account_id' => $this->inputInt('account_id', 0),
            'causale_id' => $this->inputInt('causale_id', 0),
            'tipo'       => $this->inputString('tipo', ''),
            'cerca'      => $this->inputString('cerca', ''),
        ];

        $where  = ['pn.company_id = ?', 'pn.annullato = 0'];
        $wparams = [$cid];

        if ($filtro['anno']) { $where[] = 'YEAR(pn.data_movimento) = ?'; $wparams[] = $filtro['anno']; }
        if ($filtro['mese']) { $where[] = 'MONTH(pn.data_movimento) = ?'; $wparams[] = $filtro['mese']; }
        if ($filtro['account_id']) { $where[] = 'pn.account_id = ?'; $wparams[] = $filtro['account_id']; }
        if ($filtro['causale_id']) { $where[] = 'pn.causale_id = ?'; $wparams[] = $filtro['causale_id']; }
        if ($filtro['tipo']) { $where[] = 'pn.tipo = ?'; $wparams[] = $filtro['tipo']; }
        if ($filtro['cerca']) { $where[] = '(pn.descrizione LIKE ? OR pn.fornitore_beneficiario LIKE ?)'; $wparams[] = "%{$filtro['cerca']}%"; $wparams[] = "%{$filtro['cerca']}%"; }

        $whereStr = implode(' AND ', $where);

        // Paginazione
        $page    = max(1, $this->inputInt('page', 1));
        $perPage = 50;
        $total   = (int)Database::fetchColumn("SELECT COUNT(*) FROM prima_nota pn WHERE {$whereStr}", $wparams);
        $offset  = ($page - 1) * $perPage;

        $movimenti = Database::fetchAll(
            "SELECT pn.*, c.descrizione AS causale_desc, c.codice_bilancio, c.tipo AS causale_tipo,
             a.nome AS conto_nome, u.username AS operatore
             FROM prima_nota pn
             JOIN causali c ON c.id = pn.causale_id
             JOIN accounts a ON a.id = pn.account_id
             JOIN users u ON u.id = pn.user_id
             WHERE {$whereStr}
             ORDER BY pn.data_movimento DESC, pn.id DESC
             LIMIT {$perPage} OFFSET {$offset}",
            $wparams
        );

        // Totali filtrati
        $totali = Database::fetchOne(
            "SELECT COALESCE(SUM(CASE WHEN tipo='entrata' THEN importo ELSE 0 END),0) AS tot_entrate,
             COALESCE(SUM(CASE WHEN tipo='uscita' THEN importo ELSE 0 END),0) AS tot_uscite
             FROM prima_nota pn WHERE {$whereStr}",
            $wparams
        );

        View::render('prima_nota/index', [
            'pageTitle'    => 'Prima Nota',
            'esercizioAttivo' => $esercizio,
            'movimenti'    => $movimenti,
            'conti'        => $this->getConti(),
            'causali'      => $this->getCausali(),
            'filtro'       => $filtro,
            'totali'       => $totali,
            'total'        => $total,
            'page'         => $page,
            'perPage'      => $perPage,
            'pages'        => (int)ceil($total / $perPage),
        ]);
    }

    public function create(array $params = []): void
    {
        $this->requireAuth('write');
        $cid = $this->companyId();
        $error = null;

        if ($this->isPost()) {
            $this->csrfCheck();
            $data = [
                'company_id'           => $cid,
                'account_id'           => $this->inputInt('account_id'),
                'causale_id'           => $this->inputInt('causale_id'),
                'data_movimento'       => $this->inputString('data_movimento'),
                'data_valuta'          => $this->inputString('data_valuta') ?: null,
                'descrizione'          => $this->inputString('descrizione'),
                'importo'              => $this->inputFloat('importo'),
                'tipo'                 => $this->inputString('tipo'),
                'numero_documento'     => $this->inputString('numero_documento') ?: null,
                'fornitore_beneficiario' => $this->inputString('fornitore_beneficiario') ?: null,
                'note'                 => $this->inputString('note') ?: null,
                'fonte_import'         => 'manuale',
                'esercizio'            => (int)date('Y', strtotime($this->inputString('data_movimento'))),
                'user_id'              => \ProETS\Core\Auth::id(),
            ];

            if (!$data['account_id'] || !$data['causale_id'] || !$data['data_movimento'] || empty($data['descrizione'])) {
                $error = 'Conto, causale, data e descrizione sono obbligatori.';
            } elseif ($data['importo'] <= 0) {
                $error = 'L\'importo deve essere maggiore di zero.';
            } elseif (!in_array($data['tipo'], ['entrata','uscita'])) {
                $error = 'Tipo non valido.';
            } else {
                $id = Database::insert('prima_nota', $data);
                $this->flash('success', 'Movimento registrato con successo.');
                $this->redirect('/prima-nota');
            }
        }

        $esercizio = (int)($this->company()['esercizio_corrente'] ?? date('Y'));
        View::render('prima_nota/form', [
            'pageTitle'    => 'Nuovo Movimento',
            'esercizioAttivo' => $esercizio,
            'error'        => $error,
            'movimento'    => null,
            'conti'        => $this->getConti(),
            'causali'      => $this->getCausali(),
            'csrf'         => Session::csrf(),
        ]);
    }

    public function edit(array $params = []): void
    {
        $this->requireAuth('write');
        $cid       = $this->companyId();
        $id        = (int)($params['id'] ?? 0);
        $movimento = Database::fetchOne("SELECT * FROM prima_nota WHERE id = ? AND company_id = ?", [$id, $cid]);
        if (!$movimento) \ProETS\Core\Router::abort(404);

        $error = null;
        if ($this->isPost()) {
            $this->csrfCheck();
            $data = [
                'account_id'           => $this->inputInt('account_id'),
                'causale_id'           => $this->inputInt('causale_id'),
                'data_movimento'       => $this->inputString('data_movimento'),
                'data_valuta'          => $this->inputString('data_valuta') ?: null,
                'descrizione'          => $this->inputString('descrizione'),
                'importo'              => $this->inputFloat('importo'),
                'tipo'                 => $this->inputString('tipo'),
                'numero_documento'     => $this->inputString('numero_documento') ?: null,
                'fornitore_beneficiario' => $this->inputString('fornitore_beneficiario') ?: null,
                'note'                 => $this->inputString('note') ?: null,
                'esercizio'            => (int)date('Y', strtotime($this->inputString('data_movimento'))),
            ];

            if (!$data['account_id'] || !$data['causale_id'] || !$data['data_movimento']) {
                $error = 'Conto, causale e data sono obbligatori.';
            } else {
                Database::update('prima_nota', $data, 'id = ?', [$id]);
                $this->flash('success', 'Movimento aggiornato.');
                $this->redirect('/prima-nota');
            }
        }

        $esercizio = (int)($this->company()['esercizio_corrente'] ?? date('Y'));
        View::render('prima_nota/form', [
            'pageTitle'    => 'Modifica Movimento',
            'esercizioAttivo' => $esercizio,
            'error'        => $error,
            'movimento'    => $movimento,
            'conti'        => $this->getConti(),
            'causali'      => $this->getCausali(),
            'csrf'         => Session::csrf(),
        ]);
    }

    public function cancel(array $params = []): void
    {
        $this->requireAuth('delete');
        $this->csrfCheck();
        $cid = $this->companyId();
        $id  = (int)($params['id'] ?? 0);
        Database::update('prima_nota', ['annullato' => 1], 'id = ? AND company_id = ?', [$id, $cid]);
        $this->flash('success', 'Movimento annullato.');
        $this->redirect('/prima-nota');
    }

    public function import(array $params = []): void
    {
        $this->requireAuth('import');
        $cid    = $this->companyId();
        $errors = [];
        $result = null;

        if ($this->isPost()) {
            $this->csrfCheck();
            $tipo   = $this->inputString('tipo_import');
            $file   = $_FILES['file_import'] ?? null;

            if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
                $errors[] = 'Nessun file caricato o errore nel caricamento.';
            } else {
                try {
                    $service = new ImportService($cid, \ProETS\Core\Auth::id());
                    $result  = match($tipo) {
                        'csv'     => $service->importCsv($file['tmp_name']),
                        'paypal'  => $service->importPaypal($file['tmp_name']),
                        'stripe'  => $service->importStripe($file['tmp_name']),
                        'bancario'=> $service->importBancario($file['tmp_name']),
                        default   => throw new \InvalidArgumentException("Tipo import non supportato: {$tipo}")
                    };
                    $this->flash('success', "Importati {$result['imported']} movimenti. Saltati: {$result['skipped']}.");
                } catch (\Throwable $e) {
                    $errors[] = 'Errore import: ' . $e->getMessage();
                }
            }
        }

        $esercizio = (int)($this->company()['esercizio_corrente'] ?? date('Y'));
        View::render('prima_nota/import', [
            'pageTitle'    => 'Importa Movimenti',
            'esercizioAttivo' => $esercizio,
            'errors'       => $errors,
            'result'       => $result,
            'conti'        => $this->getConti(),
            'causali'      => $this->getCausali(),
            'csrf'         => Session::csrf(),
        ]);
    }

    public function saldi(array $params = []): void
    {
        $this->requireAuth();
        $cid  = $this->companyId();
        $anno = $this->inputInt('anno', (int)($this->company()['esercizio_corrente'] ?? date('Y')));

        $saldi = Database::fetchAll(
            "SELECT a.id, a.nome, a.tipo, a.iban, a.banca, a.saldo_iniziale, a.data_saldo_iniziale,
             COALESCE(SUM(CASE WHEN pn.tipo='entrata' AND pn.annullato=0 AND YEAR(pn.data_movimento)=? THEN pn.importo ELSE 0 END),0) AS entrate_anno,
             COALESCE(SUM(CASE WHEN pn.tipo='uscita'  AND pn.annullato=0 AND YEAR(pn.data_movimento)=? THEN pn.importo ELSE 0 END),0) AS uscite_anno,
             COALESCE(SUM(CASE WHEN pn.tipo='entrata' AND pn.annullato=0 THEN pn.importo ELSE 0 END),0) AS tot_entrate,
             COALESCE(SUM(CASE WHEN pn.tipo='uscita'  AND pn.annullato=0 THEN pn.importo ELSE 0 END),0) AS tot_uscite
             FROM accounts a
             LEFT JOIN prima_nota pn ON pn.account_id = a.id
             WHERE a.company_id = ? AND a.attivo = 1
             GROUP BY a.id ORDER BY a.ordine, a.nome",
            [$anno, $anno, $cid]
        );

        foreach ($saldi as &$s) {
            $s['saldo_attuale'] = $s['saldo_iniziale'] + $s['tot_entrate'] - $s['tot_uscite'];
        }
        unset($s);

        View::render('prima_nota/saldi', [
            'pageTitle'    => 'Saldi Conti',
            'esercizioAttivo' => $anno,
            'saldi'        => $saldi,
            'anno'         => $anno,
            'csrf'         => Session::csrf(),
        ]);
    }

    public function riconciliazione(array $params = []): void
    {
        $this->requireAuth('write');
        $cid    = $this->companyId();
        $error  = null;

        if ($this->isPost()) {
            $this->csrfCheck();
            $accountId    = $this->inputInt('account_id');
            $data         = $this->inputString('data_riconciliazione');
            $saldoEC      = $this->inputFloat('saldo_estratto_conto');
            $note         = $this->inputString('note');

            // Calcola saldo contabile al giorno
            $saldoCont = Database::fetchOne(
                "SELECT a.saldo_iniziale,
                 COALESCE(SUM(CASE WHEN pn.tipo='entrata' AND pn.annullato=0 AND pn.data_movimento<=? THEN pn.importo ELSE 0 END),0) AS entrate,
                 COALESCE(SUM(CASE WHEN pn.tipo='uscita'  AND pn.annullato=0 AND pn.data_movimento<=? THEN pn.importo ELSE 0 END),0) AS uscite
                 FROM accounts a LEFT JOIN prima_nota pn ON pn.account_id=a.id
                 WHERE a.id=? AND a.company_id=?",
                [$data, $data, $accountId, $cid]
            );
            $saldoCalc = ($saldoCont['saldo_iniziale'] ?? 0) + ($saldoCont['entrate'] ?? 0) - ($saldoCont['uscite'] ?? 0);

            Database::insert('riconciliazione', [
                'company_id'          => $cid,
                'account_id'          => $accountId,
                'data_riconciliazione'=> $data,
                'saldo_estratto_conto'=> $saldoEC,
                'saldo_calcolato'     => $saldoCalc,
                'note'                => $note ?: null,
                'user_id'             => \ProETS\Core\Auth::id(),
            ]);
            $diff = $saldoEC - $saldoCalc;
            $msg  = abs($diff) < 0.01
                ? 'Riconciliazione completata: conti quadrano.'
                : sprintf('Riconciliazione registrata. Differenza: € %s', number_format($diff, 2, ',', '.'));
            $this->flash(abs($diff) < 0.01 ? 'success' : 'warning', $msg);
        }

        $storici = Database::fetchAll(
            "SELECT r.*, a.nome AS conto_nome, u.username AS operatore
             FROM riconciliazione r
             JOIN accounts a ON a.id = r.account_id
             JOIN users u ON u.id = r.user_id
             WHERE r.company_id = ?
             ORDER BY r.data_riconciliazione DESC, r.id DESC LIMIT 30",
            [$cid]
        );

        $esercizio = (int)($this->company()['esercizio_corrente'] ?? date('Y'));
        View::render('prima_nota/riconciliazione', [
            'pageTitle'    => 'Riconciliazione Bancaria',
            'esercizioAttivo' => $esercizio,
            'conti'        => $this->getConti(),
            'storici'      => $storici,
            'error'        => $error,
            'csrf'         => Session::csrf(),
        ]);
    }

    public function export(array $params = []): void
    {
        $this->requireAuth('export');
        $cid       = $this->companyId();
        $esercizio = $this->inputInt('anno', (int)($this->company()['esercizio_corrente'] ?? date('Y')));

        $movimenti = Database::fetchAll(
            "SELECT pn.data_movimento, pn.tipo, pn.importo, pn.descrizione,
             c.codice_numero, c.descrizione AS causale, c.codice_bilancio,
             a.nome AS conto, pn.fornitore_beneficiario, pn.numero_documento, pn.note
             FROM prima_nota pn
             JOIN causali c ON c.id = pn.causale_id
             JOIN accounts a ON a.id = pn.account_id
             WHERE pn.company_id = ? AND YEAR(pn.data_movimento) = ? AND pn.annullato = 0
             ORDER BY pn.data_movimento, pn.id",
            [$cid, $esercizio]
        );

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="prima_nota_' . $esercizio . '.csv"');
        $out = fopen('php://output', 'w');
        fputs($out, "\xEF\xBB\xBF"); // BOM UTF-8 per Excel
        fputcsv($out, ['Data','Tipo','Importo','Descrizione','N.Caus','Causale','Cod.Bilancio','Conto','Fornitore/Beneficiario','N.Documento','Note'], ';');
        foreach ($movimenti as $m) {
            fputcsv($out, [
                date('d/m/Y', strtotime($m['data_movimento'])),
                $m['tipo'], number_format($m['importo'],2,',','.'),
                $m['descrizione'], $m['codice_numero'], $m['causale'],
                $m['codice_bilancio'], $m['conto'],
                $m['fornitore_beneficiario'] ?? '', $m['numero_documento'] ?? '', $m['note'] ?? ''
            ], ';');
        }
        fclose($out);
        exit;
    }
}
