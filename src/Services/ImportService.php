<?php
declare(strict_types=1);

namespace ProETS\Services;

use ProETS\Core\Database;

/**
 * ImportService
 * Importa movimenti da CSV standard, estratti conto bancari, PayPal, Stripe
 */
class ImportService
{
    private int $companyId;
    private int $userId;

    public function __construct(int $companyId, int $userId)
    {
        $this->companyId = $companyId;
        $this->userId    = $userId;
    }

    /**
     * Import CSV ProETS (formato export del sistema)
     * Colonne: Data;Tipo;Importo;Descrizione;N.Caus;Causale;Cod.Bilancio;Conto;...
     */
    public function importCsv(string $filepath): array
    {
        $rows = $this->readCsv($filepath);
        if (empty($rows)) throw new \RuntimeException('File CSV vuoto o non leggibile.');

        $header = array_shift($rows); // Salta intestazione
        $imported = 0; $skipped = 0; $errors = [];

        foreach ($rows as $i => $row) {
            try {
                if (count($row) < 4) { $skipped++; continue; }
                $data    = $this->parseDate($row[0]);
                $tipo    = strtolower(trim($row[1]));
                $importo = (float)str_replace(['.','  '],['',','], str_replace(',','.',$row[2]));
                $descr   = trim($row[3]);

                if (!in_array($tipo, ['entrata','uscita'])) { $skipped++; continue; }
                if ($importo <= 0 || empty($descr) || !$data) { $skipped++; continue; }

                // Trova conto (colonna 7 se presente)
                $contoNome = isset($row[7]) ? trim($row[7]) : '';
                $accountId = $this->findAccount($contoNome) ?? $this->getDefaultAccount();
                if (!$accountId) { $skipped++; continue; }

                // Trova causale (colonna 6 = cod bilancio)
                $codBilancio = isset($row[6]) ? trim($row[6]) : '';
                $causaleId   = $this->findCausaleByCode($codBilancio, $tipo) ?? $this->getDefaultCausale($tipo);

                if ($this->isAlreadyImported($data, $importo, $tipo, $descr)) { $skipped++; continue; }

                Database::insert('prima_nota', [
                    'company_id'     => $this->companyId,
                    'account_id'     => $accountId,
                    'causale_id'     => $causaleId,
                    'data_movimento' => $data,
                    'descrizione'    => $descr,
                    'importo'        => $importo,
                    'tipo'           => $tipo,
                    'fonte_import'   => 'csv',
                    'esercizio'      => (int)substr($data,0,4),
                    'user_id'        => $this->userId,
                    'fornitore_beneficiario' => isset($row[8]) ? trim($row[8]) : null,
                    'numero_documento'       => isset($row[9]) ? trim($row[9]) : null,
                ]);
                $imported++;
            } catch (\Throwable $e) {
                $errors[] = "Riga " . ($i+2) . ": " . $e->getMessage();
                $skipped++;
            }
        }
        return ['imported' => $imported, 'skipped' => $skipped, 'errors' => $errors];
    }

    /**
     * Import estratto conto PayPal CSV
     * Formato PayPal: "Data","Ora","Fuso orario","Descrizione","Valuta","Lordo","Tariffa","Netto",...
     */
    public function importPaypal(string $filepath): array
    {
        $rows = $this->readCsv($filepath, ',');
        if (empty($rows)) throw new \RuntimeException('File PayPal CSV vuoto.');

        $header = array_shift($rows);
        // Trova indici colonne
        $idxData  = array_search('Data', $header) !== false ? array_search('Data', $header) : 0;
        $idxDescr = array_search('Descrizione', $header) !== false ? array_search('Descrizione', $header) : 3;
        $idxNetto = array_search('Netto', $header) !== false ? array_search('Netto', $header) : 7;
        $idxId    = array_search('ID transazione', $header) !== false ? array_search('ID transazione', $header) : -1;

        $imported = 0; $skipped = 0; $errors = [];
        $accountId = $this->findAccountByTipo('paypal') ?? $this->getDefaultAccount();

        foreach ($rows as $i => $row) {
            try {
                $data    = $this->parseDate($row[$idxData] ?? '');
                $netto   = (float)str_replace(['.','  '],['','.'], str_replace(',','.',$row[$idxNetto] ?? '0'));
                $descr   = trim($row[$idxDescr] ?? '');
                $txId    = $idxId >= 0 ? trim($row[$idxId] ?? '') : '';

                if (!$data || $netto == 0 || empty($descr)) { $skipped++; continue; }
                if ($txId && $this->isTxAlreadyImported($txId)) { $skipped++; continue; }

                $tipo     = $netto > 0 ? 'entrata' : 'uscita';
                $importo  = abs($netto);
                $causaleId = $this->findCausaleByCode($tipo === 'entrata' ? 'EB3' : 'UB2', $tipo)
                             ?? $this->getDefaultCausale($tipo);

                Database::insert('prima_nota', [
                    'company_id'           => $this->companyId,
                    'account_id'           => $accountId,
                    'causale_id'           => $causaleId,
                    'data_movimento'       => $data,
                    'descrizione'          => $descr ?: 'Transazione PayPal',
                    'importo'              => $importo,
                    'tipo'                 => $tipo,
                    'fonte_import'         => 'paypal',
                    'id_transazione_esterno' => $txId ?: null,
                    'esercizio'            => (int)substr($data,0,4),
                    'user_id'              => $this->userId,
                ]);
                $imported++;
            } catch (\Throwable $e) {
                $errors[] = "Riga " . ($i+2) . ": " . $e->getMessage();
                $skipped++;
            }
        }
        return ['imported' => $imported, 'skipped' => $skipped, 'errors' => $errors];
    }

    /**
     * Import Stripe CSV
     * Formato: id,Description,Amount,Amount Refunded,Currency,Captured,Converted Amount,Status,...
     */
    public function importStripe(string $filepath): array
    {
        $rows = $this->readCsv($filepath, ',');
        if (empty($rows)) throw new \RuntimeException('File Stripe CSV vuoto.');

        $header   = array_shift($rows);
        $imported = 0; $skipped = 0; $errors = [];
        $accountId = $this->findAccountByTipo('stripe') ?? $this->getDefaultAccount();

        foreach ($rows as $i => $row) {
            try {
                $combined = array_combine($header, array_pad($row, count($header), ''));
                $txId     = $combined['id'] ?? '';
                $amount   = (float)($combined['Amount'] ?? 0) / 100; // Stripe in centesimi
                $status   = strtolower($combined['Status'] ?? '');
                $descr    = $combined['Description'] ?? '';
                $created  = $combined['Created (UTC)'] ?? $combined['Created'] ?? '';
                $data     = $this->parseDate($created);

                if (!$data || $amount <= 0 || $status !== 'paid') { $skipped++; continue; }
                if ($txId && $this->isTxAlreadyImported($txId)) { $skipped++; continue; }

                $causaleId = $this->findCausaleByCode('EB3', 'entrata') ?? $this->getDefaultCausale('entrata');
                Database::insert('prima_nota', [
                    'company_id'           => $this->companyId,
                    'account_id'           => $accountId,
                    'causale_id'           => $causaleId,
                    'data_movimento'       => $data,
                    'descrizione'          => $descr ?: 'Pagamento Stripe',
                    'importo'              => $amount,
                    'tipo'                 => 'entrata',
                    'fonte_import'         => 'stripe',
                    'id_transazione_esterno' => $txId ?: null,
                    'esercizio'            => (int)substr($data,0,4),
                    'user_id'              => $this->userId,
                ]);
                $imported++;
            } catch (\Throwable $e) {
                $errors[] = "Riga " . ($i+2) . ": " . $e->getMessage();
                $skipped++;
            }
        }
        return ['imported' => $imported, 'skipped' => $skipped, 'errors' => $errors];
    }

    /**
     * Import estratto conto bancario (formato CBI/ABI o generico CSV)
     * Colonne: Data;DataValuta;Descrizione;Accredito;Addebito
     */
    public function importBancario(string $filepath): array
    {
        $rows = $this->readCsv($filepath);
        if (empty($rows)) throw new \RuntimeException('File bancario vuoto.');

        $header   = array_shift($rows);
        $imported = 0; $skipped = 0; $errors = [];
        $accountId = $this->findAccountByTipo('banca') ?? $this->getDefaultAccount();

        foreach ($rows as $i => $row) {
            try {
                if (count($row) < 3) { $skipped++; continue; }
                $data       = $this->parseDate($row[0]);
                $dataValuta = isset($row[1]) ? $this->parseDate($row[1]) : null;
                $descr      = trim($row[2] ?? '');
                $accredito  = isset($row[3]) ? abs((float)str_replace(',','.',$row[3])) : 0;
                $addebito   = isset($row[4]) ? abs((float)str_replace(',','.',$row[4])) : 0;

                if (!$data) { $skipped++; continue; }
                if ($accredito <= 0 && $addebito <= 0) { $skipped++; continue; }

                $tipo    = $accredito > 0 ? 'entrata' : 'uscita';
                $importo = $accredito > 0 ? $accredito : $addebito;

                $causaleId = $tipo === 'entrata'
                    ? ($this->findCausaleByCode('ED1', 'entrata') ?? $this->getDefaultCausale('entrata'))
                    : ($this->findCausaleByCode('UD1', 'uscita') ?? $this->getDefaultCausale('uscita'));

                if ($this->isAlreadyImported($data, $importo, $tipo, $descr)) { $skipped++; continue; }

                Database::insert('prima_nota', [
                    'company_id'     => $this->companyId,
                    'account_id'     => $accountId,
                    'causale_id'     => $causaleId,
                    'data_movimento' => $data,
                    'data_valuta'    => $dataValuta,
                    'descrizione'    => $descr ?: 'Movimento bancario',
                    'importo'        => $importo,
                    'tipo'           => $tipo,
                    'fonte_import'   => 'bancario',
                    'esercizio'      => (int)substr($data,0,4),
                    'user_id'        => $this->userId,
                ]);
                $imported++;
            } catch (\Throwable $e) {
                $errors[] = "Riga " . ($i+2) . ": " . $e->getMessage();
                $skipped++;
            }
        }
        return ['imported' => $imported, 'skipped' => $skipped, 'errors' => $errors];
    }

    // --- Helpers ---

    private function readCsv(string $filepath, string $sep = ';'): array
    {
        $handle = fopen($filepath, 'r');
        if (!$handle) throw new \RuntimeException('Impossibile aprire il file.');
        // Skip BOM UTF-8
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") rewind($handle);

        $rows = [];
        while (($row = fgetcsv($handle, 2000, $sep)) !== false) {
            $rows[] = $row;
        }
        fclose($handle);
        return $rows;
    }

    private function parseDate(string $raw): ?string
    {
        $raw = trim($raw);
        if (!$raw) return null;
        // Formati comuni: d/m/Y, Y-m-d, d-m-Y, m/d/Y
        foreach (['d/m/Y','Y-m-d','d-m-Y','m/d/Y','d.m.Y','Y/m/d'] as $fmt) {
            $dt = \DateTime::createFromFormat($fmt, $raw);
            if ($dt) return $dt->format('Y-m-d');
        }
        // Prova timestamp strtotime
        $ts = strtotime($raw);
        return $ts ? date('Y-m-d', $ts) : null;
    }

    private function findAccount(string $nome): ?int
    {
        if (!$nome) return null;
        $r = Database::fetchOne(
            "SELECT id FROM accounts WHERE company_id = ? AND (nome LIKE ? OR codice LIKE ?) AND attivo = 1 LIMIT 1",
            [$this->companyId, "%{$nome}%", "%{$nome}%"]
        );
        return $r ? (int)$r['id'] : null;
    }

    private function findAccountByTipo(string $tipo): ?int
    {
        $r = Database::fetchOne(
            "SELECT id FROM accounts WHERE company_id = ? AND tipo = ? AND attivo = 1 ORDER BY ordine LIMIT 1",
            [$this->companyId, $tipo]
        );
        return $r ? (int)$r['id'] : null;
    }

    private function getDefaultAccount(): ?int
    {
        $r = Database::fetchOne(
            "SELECT id FROM accounts WHERE company_id = ? AND attivo = 1 ORDER BY ordine LIMIT 1",
            [$this->companyId]
        );
        return $r ? (int)$r['id'] : null;
    }

    private function findCausaleByCode(string $cod, string $tipo): ?int
    {
        $r = Database::fetchOne(
            "SELECT id FROM causali WHERE codice_bilancio = ? AND (company_id IS NULL OR company_id = ?) AND attivo = 1 LIMIT 1",
            [$cod, $this->companyId]
        );
        return $r ? (int)$r['id'] : null;
    }

    private function getDefaultCausale(string $tipo): ?int
    {
        $cod = $tipo === 'entrata' ? 'EA10' : 'UA5';
        return $this->findCausaleByCode($cod, $tipo);
    }

    private function isAlreadyImported(string $data, float $importo, string $tipo, string $descr): bool
    {
        $r = Database::fetchColumn(
            "SELECT COUNT(*) FROM prima_nota WHERE company_id = ? AND data_movimento = ? AND importo = ? AND tipo = ? AND descrizione = ? AND annullato = 0",
            [$this->companyId, $data, $importo, $tipo, $descr]
        );
        return (int)$r > 0;
    }

    private function isTxAlreadyImported(string $txId): bool
    {
        $r = Database::fetchColumn(
            "SELECT COUNT(*) FROM prima_nota WHERE company_id = ? AND id_transazione_esterno = ?",
            [$this->companyId, $txId]
        );
        return (int)$r > 0;
    }
}
