<?php
declare(strict_types=1);

namespace ProETS\Controllers;

use ProETS\Core\Controller;
use ProETS\Core\Database;
use ProETS\Core\Session;
use ProETS\Core\View;
use ProETS\Services\BackupService;

class BackupController extends Controller
{
    public function index(array $params = []): void
    {
        $this->requireAuth('config');
        $cid = $this->companyId();

        $configs = Database::fetchAll(
            "SELECT * FROM backup_configs WHERE company_id = ? OR company_id IS NULL ORDER BY tipo",
            [$cid]
        );
        $history = Database::fetchAll(
            "SELECT bh.*, bc.nome AS config_nome, bc.tipo AS config_tipo
             FROM backup_history bh JOIN backup_configs bc ON bc.id = bh.config_id
             WHERE bc.company_id = ? OR bc.company_id IS NULL
             ORDER BY bh.created_at DESC LIMIT 30",
            [$cid]
        );

        $esercizio = (int)($this->company()['esercizio_corrente'] ?? date('Y'));
        View::render('backup/index', [
            'pageTitle'    => 'Backup & Ripristino',
            'esercizioAttivo' => $esercizio,
            'configs'      => $configs,
            'history'      => $history,
            'csrf'         => Session::csrf(),
        ]);
    }

    public function config(array $params = []): void
    {
        $this->requireAuth('config');
        $cid = $this->companyId();
        $error = null;

        if ($this->isPost()) {
            $this->csrfCheck();
            $tipo   = $this->inputString('tipo');
            $nome   = $this->inputString('nome');
            $id     = $this->inputInt('config_id');
            $attivo = !empty($_POST['attivo']) ? 1 : 0;
            $freq   = $this->inputString('frequenza', 'giornaliero');
            $ora    = $this->inputString('ora_esecuzione', '02:00');
            $rotaz  = $this->inputInt('rotazione_giorni', 7);

            // Configurazione specifica per tipo
            $config = match($tipo) {
                'locale'      => ['path' => $this->inputString('local_path')],
                'ftp','sftp'  => ['host' => $this->inputString('host'), 'user' => $this->inputString('ftp_user'), 'pass' => $this->inputString('ftp_pass'), 'path' => $this->inputString('remote_path'), 'port' => $this->inputInt('ftp_port', 21)],
                'webdav'      => ['url' => $this->inputString('webdav_url'), 'user' => $this->inputString('webdav_user'), 'pass' => $this->inputString('webdav_pass')],
                'nextcloud'   => ['url' => $this->inputString('nc_url'), 'user' => $this->inputString('nc_user'), 'pass' => $this->inputString('nc_pass'), 'path' => $this->inputString('nc_path')],
                'googledrive' => ['folder_id' => $this->inputString('gdrive_folder'), 'credentials' => $this->inputString('gdrive_creds')],
                'dropbox'     => ['token' => $this->inputString('dropbox_token'), 'path' => $this->inputString('dropbox_path')],
                default       => [],
            };

            $data = [
                'company_id'       => $cid,
                'tipo'             => $tipo,
                'nome'             => $nome,
                'attivo'           => $attivo,
                'configurazione'   => json_encode($config),
                'frequenza'        => $freq,
                'ora_esecuzione'   => $ora . ':00',
                'rotazione_giorni' => $rotaz,
            ];

            if ($id) {
                Database::update('backup_configs', $data, 'id = ?', [$id]);
                $this->flash('success', 'Configurazione backup aggiornata.');
            } else {
                Database::insert('backup_configs', $data);
                $this->flash('success', 'Configurazione backup salvata.');
            }
            $this->redirect('/backup');
        }

        $esercizio = (int)($this->company()['esercizio_corrente'] ?? date('Y'));
        $editConfig = null;
        $editId = $this->inputInt('edit');
        if ($editId) {
            $editConfig = Database::fetchOne("SELECT * FROM backup_configs WHERE id = ?", [$editId]);
            if ($editConfig) $editConfig['configurazione'] = json_decode($editConfig['configurazione'], true);
        }

        View::render('backup/config', [
            'pageTitle'    => 'Configurazione Backup',
            'esercizioAttivo' => $esercizio,
            'editConfig'   => $editConfig,
            'csrf'         => Session::csrf(),
        ]);
    }

    public function esegui(array $params = []): void
    {
        $this->requireAuth('config');
        $this->csrfCheck();
        $configId = $this->inputInt('config_id');
        $cid      = $this->companyId();

        $config = Database::fetchOne("SELECT * FROM backup_configs WHERE id = ?", [$configId]);
        if (!$config) { $this->flash('error', 'Configurazione non trovata.'); $this->redirect('/backup'); }

        try {
            $svc    = new BackupService($this->company());
            $result = $svc->esegui($config);
            $this->flash('success', "Backup completato: {$result['filename']} ({$result['size_human']})");
        } catch (\Throwable $e) {
            $this->flash('error', 'Errore backup: ' . $e->getMessage());
        }
        $this->redirect('/backup');
    }

    public function ripristina(array $params = []): void
    {
        $this->requireAuth('config');
        $this->csrfCheck();
        $historyId = $this->inputInt('history_id');

        $backup = Database::fetchOne("SELECT * FROM backup_history WHERE id = ?", [$historyId]);
        if (!$backup || $backup['stato'] !== 'ok') {
            $this->flash('error', 'Backup non disponibile per il ripristino.');
            $this->redirect('/backup');
        }

        try {
            $config = Database::fetchOne("SELECT * FROM backup_configs WHERE id = ?", [$backup['config_id']]);
            $svc    = new BackupService($this->company());
            $svc->ripristina($backup, $config);
            $this->flash('success', 'Ripristino completato con successo.');
        } catch (\Throwable $e) {
            $this->flash('error', 'Errore ripristino: ' . $e->getMessage());
        }
        $this->redirect('/backup');
    }

    public function download(array $params = []): void
    {
        $this->requireAuth('config');
        $id = (int)($params['id'] ?? 0);
        $backup = Database::fetchOne("SELECT * FROM backup_history WHERE id = ?", [$id]);
        if (!$backup) \ProETS\Core\Router::abort(404);

        $localPath = \ProETS\Core\Config::get('backup.local_path') . $backup['filename'];
        if (!file_exists($localPath)) {
            $this->flash('error', 'File backup non trovato localmente.');
            $this->redirect('/backup');
        }

        header('Content-Type: application/gzip');
        header('Content-Disposition: attachment; filename="' . basename($backup['filename']) . '"');
        header('Content-Length: ' . filesize($localPath));
        readfile($localPath);
        exit;
    }

    public function elimina(array $params = []): void
    {
        $this->requireAuth('config');
        $this->csrfCheck();
        $id = (int)($params['id'] ?? 0);
        $backup = Database::fetchOne("SELECT * FROM backup_history WHERE id = ?", [$id]);
        if ($backup) {
            $localPath = \ProETS\Core\Config::get('backup.local_path') . $backup['filename'];
            if (file_exists($localPath)) @unlink($localPath);
            Database::delete('backup_history', 'id = ?', [$id]);
        }
        $this->flash('success', 'Backup eliminato.');
        $this->redirect('/backup');
    }
}
