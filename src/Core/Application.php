<?php
declare(strict_types=1);

namespace ProETS\Core;

use ProETS\Controllers\AuthController;
use ProETS\Controllers\DashboardController;
use ProETS\Controllers\PrimaNotaController;
use ProETS\Controllers\RendicontoController;
use ProETS\Controllers\BudgetController;
use ProETS\Controllers\SociController;
use ProETS\Controllers\BackupController;
use ProETS\Controllers\ConfigController;
use ProETS\Controllers\SuperadminController;
use ProETS\Controllers\UpdateController;

class Application
{
    public static function run(): void
    {
        self::registerRoutes();
        Router::dispatch();
    }

    private static function registerRoutes(): void
    {
        // Auth
        Router::any('/auth/login',           [AuthController::class, 'login']);
        Router::get('/auth/logout',          [AuthController::class, 'logout']);
        Router::any('/auth/forgot-password', [AuthController::class, 'forgotPassword']);
        Router::any('/auth/reset-password',  [AuthController::class, 'resetPassword']);

        // Dashboard
        Router::get('/',                     [DashboardController::class, 'index']);
        Router::get('/dashboard',            [DashboardController::class, 'index']);
        Router::post('/dashboard/switch-company', [DashboardController::class, 'switchCompany']);

        // Prima Nota
        Router::get('/prima-nota',           [PrimaNotaController::class, 'index']);
        Router::any('/prima-nota/nuovo',     [PrimaNotaController::class, 'create']);
        Router::any('/prima-nota/:id/modifica', [PrimaNotaController::class, 'edit']);
        Router::post('/prima-nota/:id/annulla', [PrimaNotaController::class, 'cancel']);
        Router::any('/prima-nota/import',    [PrimaNotaController::class, 'import']);
        Router::get('/prima-nota/export',    [PrimaNotaController::class, 'export']);
        Router::get('/prima-nota/saldi',     [PrimaNotaController::class, 'saldi']);
        Router::any('/prima-nota/riconciliazione', [PrimaNotaController::class, 'riconciliazione']);

        // Rendiconto
        Router::get('/rendiconto',           [RendicontoController::class, 'index']);
        Router::get('/rendiconto/annuale',   [RendicontoController::class, 'annuale']);
        Router::get('/rendiconto/scostamenti', [RendicontoController::class, 'scostamenti']);
        Router::get('/rendiconto/sintetico', [RendicontoController::class, 'sintetico']);
        Router::get('/rendiconto/pdf/:tipo', [RendicontoController::class, 'pdf']);
        Router::get('/rendiconto/test-ets',  [RendicontoController::class, 'testEts']);

        // Budget
        Router::get('/budget',               [BudgetController::class, 'index']);
        Router::any('/budget/nuovo',         [BudgetController::class, 'create']);
        Router::any('/budget/:id/modifica',  [BudgetController::class, 'edit']);
        Router::post('/budget/:id/approva',  [BudgetController::class, 'approva']);
        Router::get('/budget/:id/pdf',       [BudgetController::class, 'pdf']);

        // Soci — le route statiche DEVONO precedere quelle con :id
        Router::get('/soci',                     [SociController::class, 'index']);
        Router::any('/soci/nuovo',               [SociController::class, 'create']);
        Router::get('/soci/quote',               [SociController::class, 'quote']);
        Router::any('/soci/quote/genera',        [SociController::class, 'generaQuote']);
        Router::any('/soci/comunicazioni',       [SociController::class, 'comunicazioni']);
        Router::any('/soci/comunicazioni/invia', [SociController::class, 'inviaComunicazione']);
        Router::get('/soci/export',              [SociController::class, 'export']);
        Router::any('/soci/:id/modifica',        [SociController::class, 'edit']);
        Router::post('/soci/:id/elimina',        [SociController::class, 'delete']);
        Router::get('/soci/:id/tessera',         [SociController::class, 'tessera']);
        Router::get('/soci/:id',                 [SociController::class, 'show']);

        // Link pagamento quota (pubblico - no auth)
        Router::any('/paga/:token',          [SociController::class, 'pagaQuota']);

        // Backup
        Router::get('/backup',               [BackupController::class, 'index']);
        Router::any('/backup/configurazione',[BackupController::class, 'config']);
        Router::post('/backup/esegui',       [BackupController::class, 'esegui']);
        Router::post('/backup/ripristina',   [BackupController::class, 'ripristina']);
        Router::get('/backup/download/:id',  [BackupController::class, 'download']);
        Router::post('/backup/elimina/:id',  [BackupController::class, 'elimina']);

        // Superadmin
        Router::any('/superadmin',                          [SuperadminController::class, 'index']);
        Router::post('/superadmin/aziende/:id/toggle',      [SuperadminController::class, 'toggleAzienda']);
        Router::any('/superadmin/utenti',                   [SuperadminController::class, 'utenti']);
        Router::any('/superadmin/aggiornamenti',            [UpdateController::class, 'index']);

        // Configurazione
        Router::any('/configurazione',       [ConfigController::class, 'index']);
        Router::any('/configurazione/azienda', [ConfigController::class, 'azienda']);
        Router::any('/configurazione/utenti',  [ConfigController::class, 'utenti']);
        Router::any('/configurazione/email',   [ConfigController::class, 'email']);
        Router::any('/configurazione/gdpr',    [ConfigController::class, 'gdpr']);
        Router::any('/configurazione/quote-annuali', [ConfigController::class, 'quoteAnnuali']);
    }
}
