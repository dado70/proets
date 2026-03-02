<?php
declare(strict_types=1);

namespace ProETS\Services;

use ProETS\Core\Config;
use ProETS\Core\Database;

/**
 * BackupService
 * Esegue backup del database e lo invia al destinatario configurato.
 * Supporta: locale, FTP, SFTP, WebDAV, Nextcloud, Google Drive, Dropbox
 */
class BackupService
{
    private array $company;

    public function __construct(array $company)
    {
        $this->company = $company;
    }

    /**
     * Esegue il backup e lo salva nella destinazione configurata.
     */
    public function esegui(array $config): array
    {
        $cfg = json_decode($config['configurazione'], true) ?? [];

        // 1. Esporta il database in SQL
        $filename  = 'proets_backup_' . date('Ymd_His') . '_' . $config['tipo'] . '.sql.gz';
        $localPath = Config::get('backup.local_path', PROETS_ROOT . '/backups/');
        if (!is_dir($localPath)) mkdir($localPath, 0750, true);

        $sqlFile  = $localPath . 'tmp_' . uniqid() . '.sql';
        $gzipFile = $localPath . $filename;

        $this->dumpDatabase($sqlFile);

        // 2. Comprimi con gzip
        $this->gzip($sqlFile, $gzipFile);
        @unlink($sqlFile);

        $size     = filesize($gzipFile);
        $checksum = md5_file($gzipFile);

        // 3. Invia alla destinazione
        $remotePath = null;
        try {
            $remotePath = match($config['tipo']) {
                'locale'    => $gzipFile, // Già locale
                'ftp'       => $this->sendFtp($gzipFile, $filename, $cfg),
                'sftp'      => $this->sendSftp($gzipFile, $filename, $cfg),
                'webdav'    => $this->sendWebdav($gzipFile, $filename, $cfg),
                'nextcloud' => $this->sendNextcloud($gzipFile, $filename, $cfg),
                'googledrive'=> $this->sendGdrive($gzipFile, $filename, $cfg),
                'dropbox'   => $this->sendDropbox($gzipFile, $filename, $cfg),
                default     => $gzipFile,
            };
        } catch (\Throwable $e) {
            // Logga errore ma non blocca
            error_log("[ProETS] Backup transfer error ({$config['tipo']}): " . $e->getMessage());
        }

        // 4. Applica rotazione locale
        $this->rotateLocal($localPath, (int)$config['rotazione_giorni']);

        // 5. Registra in storico
        Database::insert('backup_history', [
            'config_id'      => $config['id'],
            'filename'       => $filename,
            'dimensione'     => $size,
            'checksum_md5'   => $checksum,
            'stato'          => 'ok',
            'percorso_remoto'=> $remotePath,
        ]);

        Database::update('backup_configs', [
            'ultimo_backup' => date('Y-m-d H:i:s'),
            'ultimo_stato'  => 'ok',
            'ultimo_messaggio' => 'Backup completato con successo.',
        ], 'id = ?', [$config['id']]);

        return [
            'filename'  => $filename,
            'size'      => $size,
            'size_human'=> $this->humanSize($size),
            'checksum'  => $checksum,
        ];
    }

    /**
     * Ripristina da un backup salvato localmente
     */
    public function ripristina(array $backup, array $config): void
    {
        $localPath = Config::get('backup.local_path', PROETS_ROOT . '/backups/');
        $gzipFile  = $localPath . $backup['filename'];

        if (!file_exists($gzipFile)) {
            throw new \RuntimeException("File backup non trovato: {$backup['filename']}");
        }

        // Verifica checksum
        if ($backup['checksum_md5'] && md5_file($gzipFile) !== $backup['checksum_md5']) {
            throw new \RuntimeException('Checksum non valido. Il file potrebbe essere corrotto.');
        }

        // Decomprime
        $sqlFile = sys_get_temp_dir() . '/proets_restore_' . uniqid() . '.sql';
        $this->gunzip($gzipFile, $sqlFile);

        // Esegui SQL di ripristino
        $sql = file_get_contents($sqlFile);
        @unlink($sqlFile);

        // Esegui statement per statement
        foreach (explode(";\n", $sql) as $stmt) {
            $stmt = trim($stmt);
            if ($stmt) Database::query($stmt);
        }
    }

    // ---- Database dump ----

    private function dumpDatabase(string $outputFile): void
    {
        $cfg = Config::get('db');
        $pdo = Database::pdo();

        $output = "-- ProETS Database Backup\n";
        $output .= "-- Date: " . date('Y-m-d H:i:s') . "\n";
        $output .= "-- Company: " . ($this->company['ragione_sociale'] ?? 'N/A') . "\n\n";
        $output .= "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n\n";

        $tables = $pdo->query("SHOW TABLES")->fetchAll(\PDO::FETCH_COLUMN);
        foreach ($tables as $table) {
            // CREATE TABLE
            $create = $pdo->query("SHOW CREATE TABLE `{$table}`")->fetch();
            $output .= "DROP TABLE IF EXISTS `{$table}`;\n";
            $output .= $create['Create Table'] . ";\n\n";

            // INSERT DATA
            $rows = $pdo->query("SELECT * FROM `{$table}`")->fetchAll(\PDO::FETCH_ASSOC);
            if (!empty($rows)) {
                $cols = '`' . implode('`,`', array_keys($rows[0])) . '`';
                $output .= "INSERT INTO `{$table}` ({$cols}) VALUES\n";
                $vals = [];
                foreach ($rows as $row) {
                    $escaped = array_map(fn($v) => $v === null ? 'NULL' : $pdo->quote((string)$v), $row);
                    $vals[] = '(' . implode(',', $escaped) . ')';
                }
                $output .= implode(",\n", $vals) . ";\n\n";
            }
        }
        $output .= "SET FOREIGN_KEY_CHECKS=1;\n";
        file_put_contents($outputFile, $output);
    }

    // ---- Trasferimenti ----

    private function sendFtp(string $local, string $remote, array $cfg): string
    {
        if (!extension_loaded('ftp')) throw new \RuntimeException('Estensione FTP non disponibile.');
        $conn = ftp_connect($cfg['host'], (int)($cfg['port'] ?? 21), 30);
        if (!$conn) throw new \RuntimeException("Connessione FTP fallita a {$cfg['host']}.");
        ftp_login($conn, $cfg['user'], $cfg['pass']);
        ftp_pasv($conn, true);
        $remotePath = rtrim($cfg['path'] ?? '/', '/') . '/' . $remote;
        if (!ftp_put($conn, $remotePath, $local, FTP_BINARY)) {
            throw new \RuntimeException("Upload FTP fallito.");
        }
        ftp_close($conn);
        return $remotePath;
    }

    private function sendSftp(string $local, string $remote, array $cfg): string
    {
        if (!extension_loaded('ssh2')) throw new \RuntimeException('Estensione SSH2 non disponibile.');
        $conn = ssh2_connect($cfg['host'], (int)($cfg['port'] ?? 22));
        if (!ssh2_auth_password($conn, $cfg['user'], $cfg['pass'])) {
            throw new \RuntimeException("Autenticazione SFTP fallita.");
        }
        $sftp       = ssh2_sftp($conn);
        $remotePath = rtrim($cfg['path'] ?? '/', '/') . '/' . $remote;
        ssh2_scp_send($conn, $local, $remotePath, 0640);
        return $remotePath;
    }

    private function sendWebdav(string $local, string $remote, array $cfg): string
    {
        $url = rtrim($cfg['url'] ?? '', '/') . '/' . $remote;
        $ch  = curl_init($url);
        $fp  = fopen($local, 'rb');
        curl_setopt_array($ch, [
            CURLOPT_PUT        => true,
            CURLOPT_INFILE     => $fp,
            CURLOPT_INFILESIZE => filesize($local),
            CURLOPT_USERPWD    => ($cfg['user'] ?? '') . ':' . ($cfg['pass'] ?? ''),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        $result = curl_exec($ch);
        $code   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        fclose($fp);
        if ($code < 200 || $code > 299) throw new \RuntimeException("WebDAV upload fallito (HTTP {$code}).");
        return $url;
    }

    private function sendNextcloud(string $local, string $remote, array $cfg): string
    {
        // Nextcloud usa WebDAV
        $cfg['url'] = rtrim($cfg['url'] ?? '', '/') . '/remote.php/dav/files/' . ($cfg['user'] ?? '') . '/' . ltrim($cfg['path'] ?? 'ProETS', '/');
        return $this->sendWebdav($local, $remote, $cfg);
    }

    private function sendGdrive(string $local, string $remote, array $cfg): string
    {
        // Richiede le credenziali OAuth2 di Google Drive API
        // Implementazione base tramite API v3 con access token
        if (empty($cfg['token'])) throw new \RuntimeException('Token Google Drive non configurato.');
        $url = 'https://www.googleapis.com/upload/drive/v3/files?uploadType=media';
        $ch  = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST       => true,
            CURLOPT_POSTFIELDS => file_get_contents($local),
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $cfg['token'],
                'Content-Type: application/gzip',
                'X-Upload-Content-Length: ' . filesize($local),
            ],
            CURLOPT_RETURNTRANSFER => true,
        ]);
        curl_exec($ch);
        curl_close($ch);
        return 'gdrive://' . $remote;
    }

    private function sendDropbox(string $local, string $remote, array $cfg): string
    {
        $path = '/' . ltrim($cfg['path'] ?? 'ProETS', '/') . '/' . $remote;
        $ch   = curl_init('https://content.dropboxapi.com/2/files/upload');
        curl_setopt_array($ch, [
            CURLOPT_POST       => true,
            CURLOPT_POSTFIELDS => file_get_contents($local),
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . ($cfg['token'] ?? ''),
                'Dropbox-API-Arg: ' . json_encode(['path' => $path, 'mode' => 'overwrite']),
                'Content-Type: application/octet-stream',
            ],
            CURLOPT_RETURNTRANSFER => true,
        ]);
        curl_exec($ch);
        curl_close($ch);
        return 'dropbox:/' . $path;
    }

    // ---- Utility ----

    private function gzip(string $input, string $output): void
    {
        $fp  = fopen($input, 'rb');
        $gz  = gzopen($output, 'wb9');
        while (!feof($fp)) gzwrite($gz, fread($fp, 65536));
        gzclose($gz);
        fclose($fp);
    }

    private function gunzip(string $input, string $output): void
    {
        $gz  = gzopen($input, 'rb');
        $fp  = fopen($output, 'wb');
        while (!gzeof($gz)) fwrite($fp, gzread($gz, 65536));
        gzclose($gz);
        fclose($fp);
    }

    private function rotateLocal(string $dir, int $keepDays): void
    {
        $files = glob($dir . 'proets_backup_*.sql.gz');
        if (!$files) return;
        $limit = time() - ($keepDays * 86400);
        foreach ($files as $f) {
            if (filemtime($f) < $limit) @unlink($f);
        }
    }

    private function humanSize(int $bytes): string
    {
        foreach (['B','KB','MB','GB'] as $u) {
            if ($bytes < 1024) return number_format($bytes, 1) . ' ' . $u;
            $bytes /= 1024;
        }
        return number_format($bytes, 1) . ' TB';
    }
}
