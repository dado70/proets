<?php
declare(strict_types=1);

namespace ProETS\Controllers;

use ProETS\Core\Controller;
use ProETS\Core\Database;
use ProETS\Core\View;
use ProETS\Services\RendicontoService;
use ProETS\Services\PdfService;

class RendicontoController extends Controller
{
    private function getService(): RendicontoService
    {
        return new RendicontoService($this->companyId());
    }

    public function index(array $params = []): void
    {
        $this->redirect('/rendiconto/annuale');
    }

    public function annuale(array $params = []): void
    {
        $this->requireAuth();
        $cid = $this->companyId();
        $company = $this->company();
        $esercizio = $this->inputInt('anno', (int)($company['esercizio_corrente'] ?? date('Y')));
        $esercizioPrec = $esercizio - 1;

        $svc = $this->getService();
        $datiAnno  = $svc->getRendiconto($esercizio);
        $datiPrec  = $svc->getRendiconto($esercizioPrec);

        // Saldi cassa e banca a fine esercizio
        $saldi = $svc->getSaldiConti($esercizio);

        View::render('rendiconto/annuale', [
            'pageTitle'    => 'Rendiconto Annuale ' . $esercizio,
            'esercizioAttivo' => $esercizio,
            'esercizio'    => $esercizio,
            'esercizioPrec' => $esercizioPrec,
            'dati'         => $datiAnno,
            'datiPrec'     => $datiPrec,
            'saldi'        => $saldi,
            'company'      => $company,
            'anni'         => range(date('Y'), 2020),
        ]);
    }

    public function scostamenti(array $params = []): void
    {
        $this->requireAuth();
        $cid = $this->companyId();
        $company = $this->company();
        $esercizio = $this->inputInt('anno', (int)($company['esercizio_corrente'] ?? date('Y')));

        $svc = $this->getService();
        $consuntivo = $svc->getRendiconto($esercizio);

        // Preventivo approvato
        $budget = Database::fetchOne(
            "SELECT b.*
             FROM budget b
             WHERE b.company_id = ? AND b.esercizio = ? AND b.stato = 'approvato' LIMIT 1",
            [$cid, $esercizio]
        );

        $preventivo = [];
        if ($budget) {
            $voci = Database::fetchAll(
                "SELECT bv.importo_preventivo, c.codice_bilancio
                 FROM budget_voci bv JOIN causali c ON c.id = bv.causale_id
                 WHERE bv.budget_id = ?",
                [$budget['id']]
            );
            foreach ($voci as $v) {
                $preventivo[$v['codice_bilancio']] = (float)$v['importo_preventivo'];
            }
        }

        View::render('rendiconto/scostamenti', [
            'pageTitle'    => 'Rendiconto Scostamenti ' . $esercizio,
            'esercizioAttivo' => $esercizio,
            'esercizio'    => $esercizio,
            'consuntivo'   => $consuntivo,
            'preventivo'   => $preventivo,
            'hasBudget'    => !empty($budget),
            'company'      => $company,
            'anni'         => range(date('Y'), 2020),
        ]);
    }

    public function sintetico(array $params = []): void
    {
        $this->requireAuth();
        $cid = $this->companyId();
        $company = $this->company();
        $esercizio = $this->inputInt('anno', (int)($company['esercizio_corrente'] ?? date('Y')));
        $esercizioPrec = $esercizio - 1;

        $svc = $this->getService();
        $datiAnno  = $svc->getRendicontoSintetico($esercizio);
        $datiPrec  = $svc->getRendicontoSintetico($esercizioPrec);

        View::render('rendiconto/sintetico', [
            'pageTitle'    => 'Rendiconto Sintetico ' . $esercizio,
            'esercizioAttivo' => $esercizio,
            'esercizio'    => $esercizio,
            'esercizioPrec' => $esercizioPrec,
            'dati'         => $datiAnno,
            'datiPrec'     => $datiPrec,
            'company'      => $company,
            'anni'         => range(date('Y'), 2020),
        ]);
    }

    public function testEts(array $params = []): void
    {
        $this->requireAuth();
        $cid = $this->companyId();
        $company = $this->company();
        $esercizio = $this->inputInt('anno', (int)($company['esercizio_corrente'] ?? date('Y')));

        $svc  = $this->getService();
        $test = $svc->getTestSecondarieta($esercizio);

        View::render('rendiconto/test_ets', [
            'pageTitle'    => 'Test Secondarietà ETS ' . $esercizio,
            'esercizioAttivo' => $esercizio,
            'esercizio'    => $esercizio,
            'test'         => $test,
            'company'      => $company,
            'anni'         => range(date('Y'), 2020),
        ]);
    }

    public function pdf(array $params = []): void
    {
        $this->requireAuth('export');
        $tipo      = $params['tipo'] ?? 'annuale';
        $cid       = $this->companyId();
        $company   = $this->company();
        $esercizio = $this->inputInt('anno', (int)($company['esercizio_corrente'] ?? date('Y')));

        $svc = $this->getService();
        $pdf = new PdfService();

        switch ($tipo) {
            case 'annuale':
                $dati = $svc->getRendiconto($esercizio);
                $datiPrec = $svc->getRendiconto($esercizio - 1);
                $saldi = $svc->getSaldiConti($esercizio);
                $pdf->rendicontoAnnuale($company, $esercizio, $dati, $datiPrec, $saldi);
                break;
            case 'sintetico':
                $dati = $svc->getRendicontoSintetico($esercizio);
                $datiPrec = $svc->getRendicontoSintetico($esercizio - 1);
                $pdf->rendicontoSintetico($company, $esercizio, $dati, $datiPrec);
                break;
            default:
                \ProETS\Core\Router::abort(404);
        }
    }
}
