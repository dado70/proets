<?php
declare(strict_types=1);

namespace ProETS\Controllers;

use ProETS\Core\Controller;
use ProETS\Core\Database;
use ProETS\Core\Session;
use ProETS\Core\View;

class BudgetController extends Controller
{
    public function index(array $params = []): void
    {
        $this->requireAuth();
        $cid       = $this->companyId();
        $esercizio = (int)($this->company()['esercizio_corrente'] ?? date('Y'));

        $budgets = Database::fetchAll(
            "SELECT b.*, u.username AS creatore FROM budget b LEFT JOIN users u ON u.id = b.created_by
             WHERE b.company_id = ? ORDER BY b.esercizio DESC",
            [$cid]
        );

        View::render('budget/index', [
            'pageTitle'    => 'Preventivo / Budget',
            'esercizioAttivo' => $esercizio,
            'budgets'      => $budgets,
            'csrf'         => Session::csrf(),
        ]);
    }

    public function create(array $params = []): void
    {
        $this->requireAuth('write');
        $cid    = $this->companyId();
        $error  = null;

        $causali = Database::fetchAll(
            "SELECT * FROM causali WHERE (company_id IS NULL OR company_id = ?) AND attivo = 1 ORDER BY ordine",
            [$cid]
        );

        if ($this->isPost()) {
            $this->csrfCheck();
            $esercizio = $this->inputInt('esercizio', (int)date('Y'));
            $nome      = $this->inputString('nome', 'Preventivo');

            // Crea testata budget
            $budgetId = Database::insert('budget', [
                'company_id' => $cid,
                'esercizio'  => $esercizio,
                'nome'       => $nome,
                'stato'      => 'bozza',
                'created_by' => \ProETS\Core\Auth::id(),
            ]);

            // Salva voci
            foreach ($_POST['voci'] ?? [] as $causaleId => $importo) {
                $importo = (float)str_replace(',','.', $importo);
                if ($importo > 0) {
                    Database::insert('budget_voci', [
                        'budget_id'         => $budgetId,
                        'causale_id'        => (int)$causaleId,
                        'importo_preventivo' => $importo,
                    ]);
                }
            }

            $this->flash('success', "Budget {$esercizio} creato.");
            $this->redirect('/budget');
        }

        $esercizio = (int)($this->company()['esercizio_corrente'] ?? date('Y'));
        View::render('budget/form', [
            'pageTitle'    => 'Nuovo Preventivo',
            'esercizioAttivo' => $esercizio,
            'error'        => $error,
            'budget'       => null,
            'voci'         => [],
            'causali'      => $causali,
            'csrf'         => Session::csrf(),
            'anni'         => range(date('Y') + 1, 2020),
        ]);
    }

    public function edit(array $params = []): void
    {
        $this->requireAuth('write');
        $cid      = $this->companyId();
        $id       = (int)($params['id'] ?? 0);
        $budget   = Database::fetchOne("SELECT * FROM budget WHERE id = ? AND company_id = ?", [$id, $cid]);
        if (!$budget) \ProETS\Core\Router::abort(404);

        $causali = Database::fetchAll(
            "SELECT * FROM causali WHERE (company_id IS NULL OR company_id = ?) AND attivo = 1 ORDER BY ordine", [$cid]
        );
        $vociRaw = Database::fetchAll("SELECT causale_id, importo_preventivo FROM budget_voci WHERE budget_id = ?", [$id]);
        $voci    = [];
        foreach ($vociRaw as $v) $voci[$v['causale_id']] = $v['importo_preventivo'];

        if ($this->isPost()) {
            $this->csrfCheck();
            Database::update('budget', ['nome' => $this->inputString('nome')], 'id = ?', [$id]);
            Database::delete('budget_voci', 'budget_id = ?', [$id]);
            foreach ($_POST['voci'] ?? [] as $causaleId => $importo) {
                $importo = (float)str_replace(',','.', $importo);
                if ($importo > 0) {
                    Database::insert('budget_voci', ['budget_id'=>$id,'causale_id'=>(int)$causaleId,'importo_preventivo'=>$importo]);
                }
            }
            $this->flash('success', 'Budget aggiornato.');
            $this->redirect('/budget');
        }

        $esercizio = (int)($this->company()['esercizio_corrente'] ?? date('Y'));
        View::render('budget/form', [
            'pageTitle'    => 'Modifica Preventivo',
            'esercizioAttivo' => $esercizio,
            'error'        => null,
            'budget'       => $budget,
            'voci'         => $voci,
            'causali'      => $causali,
            'csrf'         => Session::csrf(),
            'anni'         => range(date('Y') + 1, 2020),
        ]);
    }

    public function approva(array $params = []): void
    {
        $this->requireAuth('write');
        $this->csrfCheck();
        $cid = $this->companyId();
        $id  = (int)($params['id'] ?? 0);
        Database::update('budget', ['stato'=>'approvato','data_approvazione'=>date('Y-m-d')], 'id = ? AND company_id = ?', [$id,$cid]);
        $this->flash('success', 'Preventivo approvato.');
        $this->redirect('/budget');
    }

    public function pdf(array $params = []): void
    {
        $this->requireAuth('export');
        $id     = (int)($params['id'] ?? 0);
        $cid    = $this->companyId();
        $budget = Database::fetchOne("SELECT * FROM budget WHERE id = ? AND company_id = ?", [$id,$cid]);
        if (!$budget) \ProETS\Core\Router::abort(404);

        $voci = Database::fetchAll(
            "SELECT bv.importo_preventivo, c.descrizione, c.codice_bilancio, c.tipo, c.sezione
             FROM budget_voci bv JOIN causali c ON c.id = bv.causale_id
             WHERE bv.budget_id = ? ORDER BY c.ordine",
            [$id]
        );
        $pdf = new \ProETS\Services\PdfService();
        $pdf->budget($this->company(), $budget, $voci);
    }
}
