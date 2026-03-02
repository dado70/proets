<?php
declare(strict_types=1);

namespace ProETS\Controllers;

use ProETS\Core\Auth;
use ProETS\Core\Controller;
use ProETS\Core\Database;
use ProETS\Core\Session;
use ProETS\Core\View;

class DashboardController extends Controller
{
    public function index(array $params = []): void
    {
        $this->requireAuth();
        $cid      = $this->companyId();
        $company  = $this->company();
        $esercizio = (int)($company['esercizio_corrente'] ?? date('Y'));

        // Saldi per conto
        $saldi = Database::fetchAll(
            "SELECT a.id, a.nome, a.tipo, a.saldo_iniziale,
             COALESCE(SUM(CASE WHEN pn.tipo='entrata' AND pn.annullato=0 THEN pn.importo ELSE 0 END),0) AS tot_entrate,
             COALESCE(SUM(CASE WHEN pn.tipo='uscita'  AND pn.annullato=0 THEN pn.importo ELSE 0 END),0) AS tot_uscite
             FROM accounts a
             LEFT JOIN prima_nota pn ON pn.account_id = a.id AND YEAR(pn.data_movimento) = ?
             WHERE a.company_id = ? AND a.attivo = 1
             GROUP BY a.id ORDER BY a.ordine, a.nome",
            [$esercizio, $cid]
        );

        $totEntrate = 0; $totUscite = 0; $totSaldo = 0;
        foreach ($saldi as &$s) {
            $s['saldo'] = $s['saldo_iniziale'] + $s['tot_entrate'] - $s['tot_uscite'];
            $totEntrate += $s['tot_entrate'];
            $totUscite  += $s['tot_uscite'];
            $totSaldo   += $s['saldo'];
        }
        unset($s);

        // Totale soci attivi
        $totSoci = (int)Database::fetchColumn(
            "SELECT COUNT(*) FROM soci WHERE company_id = ? AND attivo = 1", [$cid]
        );

        // Quote da incassare anno corrente
        $quoteAttesa = (float)(Database::fetchColumn(
            "SELECT COALESCE(SUM(importo_dovuto - importo_versato),0) FROM quote_socio
             WHERE company_id = ? AND anno = ? AND stato IN ('attesa','parziale')",
            [$cid, $esercizio]
        ) ?? 0);

        // Ultimi 10 movimenti
        $ultimiMovimenti = Database::fetchAll(
            "SELECT pn.*, c.descrizione AS causale_desc, c.tipo AS causale_tipo, c.codice_bilancio,
             a.nome AS conto_nome
             FROM prima_nota pn
             JOIN causali c ON c.id = pn.causale_id
             JOIN accounts a ON a.id = pn.account_id
             WHERE pn.company_id = ? AND pn.annullato = 0
             ORDER BY pn.data_movimento DESC, pn.id DESC LIMIT 10",
            [$cid]
        );

        // Dati grafico mensile entrate/uscite (anno corrente)
        $graficoDati = Database::fetchAll(
            "SELECT MONTH(data_movimento) AS mese,
             SUM(CASE WHEN tipo='entrata' THEN importo ELSE 0 END) AS entrate,
             SUM(CASE WHEN tipo='uscita'  THEN importo ELSE 0 END) AS uscite
             FROM prima_nota
             WHERE company_id = ? AND YEAR(data_movimento) = ? AND annullato = 0
             GROUP BY MONTH(data_movimento) ORDER BY mese",
            [$cid, $esercizio]
        );
        $mesi = ['', 'Gen','Feb','Mar','Apr','Mag','Giu','Lug','Ago','Set','Ott','Nov','Dic'];
        $graficoLabels   = [];
        $graficoEntrate  = [];
        $graficoUscite   = [];
        foreach ($graficoDati as $g) {
            $graficoLabels[]  = $mesi[(int)$g['mese']];
            $graficoEntrate[] = (float)$g['entrate'];
            $graficoUscite[]  = (float)$g['uscite'];
        }

        // Prossime quote in scadenza (soci senza quota pagata)
        $sociQuoteAttesa = Database::fetchAll(
            "SELECT s.cognome, s.nome, s.email, qs.importo_dovuto, qs.importo_versato, qs.stato
             FROM quote_socio qs JOIN soci s ON s.id = qs.socio_id
             WHERE qs.company_id = ? AND qs.anno = ? AND qs.stato IN ('attesa','parziale')
             ORDER BY s.cognome LIMIT 8",
            [$cid, $esercizio]
        );

        View::render('dashboard/index', [
            'pageTitle'       => 'Dashboard',
            'esercizioAttivo' => $esercizio,
            'saldi'           => $saldi,
            'totEntrate'      => $totEntrate,
            'totUscite'       => $totUscite,
            'totSaldo'        => $totSaldo,
            'totSoci'         => $totSoci,
            'quoteAttesa'     => $quoteAttesa,
            'ultimiMovimenti' => $ultimiMovimenti,
            'graficoLabels'   => json_encode($graficoLabels),
            'graficoEntrate'  => json_encode($graficoEntrate),
            'graficoUscite'   => json_encode($graficoUscite),
            'sociQuoteAttesa' => $sociQuoteAttesa,
        ]);
    }

    public function switchCompany(array $params = []): void
    {
        $this->requireAuth();
        $this->csrfCheck();
        $companyId = $this->inputInt('company_id');
        if ($companyId && Auth::switchCompany($companyId)) {
            Session::flash('success', 'Associazione cambiata.');
        }
        $this->redirect('/dashboard');
    }
}
