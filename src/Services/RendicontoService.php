<?php
declare(strict_types=1);

namespace ProETS\Services;

use ProETS\Core\Database;

/**
 * RendicontoService
 * Aggrega i movimenti della Prima Nota secondo lo schema ETS (D.Lgs 117/2017)
 * tramite la tabella di mappatura causali → voci di rendiconto.
 */
class RendicontoService
{
    private int $companyId;

    // Struttura del rendiconto: [sezione => [codice_bilancio => label]]
    private const STRUTTURA = [
        'A_uscite' => [
            'UA1' => '1) Materie prime, sussidiarie, di consumo e merci',
            'UA2' => '2) Servizi',
            'UA3' => '3) Godimento beni di terzi',
            'UA4' => '4) Personale',
            'UA5' => '5) Uscite diverse di gestione',
        ],
        'A_entrate' => [
            'EA1'  => '1) Entrate da quote associative e apporti dei fondatori',
            'EA2'  => '2) Entrate dagli associati per attività mutuali',
            'EA3'  => '3) Entrate per prestazioni e cessioni ad associati e fondatori',
            'EA4'  => '4) Erogazioni liberali',
            'EA5'  => '5) Entrate del 5 per mille',
            'EA6'  => '6) Contributi da soggetti privati',
            'EA7'  => '7) Entrate per prestazioni e cessioni a terzi',
            'EA8'  => '8) Contributi da enti pubblici',
            'EA9'  => '9) Entrate da contratti con enti pubblici',
            'EA10' => '10) Altre entrate',
        ],
        'B_uscite' => [
            'UB1' => '1) Materie prime, sussidiarie, di consumo e merci',
            'UB2' => '2) Servizi',
            'UB3' => '3) Godimento beni di terzi',
            'UB4' => '4) Personale',
            'UB5' => '5) Uscite diverse di gestione',
        ],
        'B_entrate' => [
            'EB1' => '1) Entrate per prestazioni e cessioni ad associati e fondatori',
            'EB2' => '2) Contributi da soggetti privati',
            'EB3' => '3) Entrate per prestazioni e cessioni a terzi',
            'EB4' => '4) Contributi da enti pubblici',
            'EB5' => '5) Entrate da contratti con enti pubblici',
            'EB6' => '6) Altre entrate',
        ],
        'C_uscite' => [
            'UC1' => '1) Uscite per raccolte fondi abituali',
            'UC2' => '2) Uscite per raccolte fondi occasionali',
            'UC3' => '3) Altre uscite',
        ],
        'C_entrate' => [
            'EC1' => '1) Entrate da raccolte fondi abituali',
            'EC2' => '2) Entrate da raccolte fondi occasionali',
            'EC3' => '3) Altre entrate',
        ],
        'D_uscite' => [
            'UD1' => '1) Su rapporti bancari',
            'UD2' => '2) Su investimenti finanziari',
            'UD3' => '3) Su patrimonio edilizio',
            'UD4' => '4) Su altri beni patrimoniali',
            'UD5' => '5) Altre uscite',
        ],
        'D_entrate' => [
            'ED1' => '1) Da rapporti bancari',
            'ED2' => '2) Da altri investimenti finanziari',
            'ED3' => '3) Da patrimonio edilizio',
            'ED4' => '4) Da altri beni patrimoniali',
            'ED5' => '5) Altre entrate',
        ],
        'E_uscite' => [
            'UE1' => '1) Materie prime, sussidiarie, di consumo e merci',
            'UE2' => '2) Servizi',
            'UE3' => '3) Godimento beni di terzi',
            'UE4' => '4) Personale',
            'UE5' => '5) Altre uscite',
        ],
        'E_entrate' => [
            'EE1' => '1) Entrate da distacco del personale',
            'EE2' => '2) Altre entrate di supporto generale',
        ],
        'investimenti' => [
            'INV1' => '1) Investimenti in immobilizzazioni (attività di interesse generale)',
            'INV2' => '2) Investimenti in immobilizzazioni (attività diverse)',
            'INV3' => '3) Investimenti in attività finanziarie e patrimoniali',
            'INV4' => '4) Rimborso di finanziamenti e prestiti',
        ],
        'disinvestimenti' => [
            'DIS1' => '1) Disinvestimenti immobilizzazioni (interesse generale)',
            'DIS2' => '2) Disinvestimenti immobilizzazioni (attività diverse)',
            'DIS3' => '3) Disinvestimenti attività finanziarie e patrimoniali',
            'DIS4' => '4) Ricevimento di finanziamenti e prestiti',
        ],
        'figurativi' => [
            'CF1' => 'Costi figurativi attività interesse generale',
            'CF2' => 'Costi figurativi attività diverse',
            'PF1' => 'Proventi figurativi attività interesse generale',
            'PF2' => 'Proventi figurativi attività diverse',
        ],
        'imposte' => [
            'IMP' => 'Imposte (IRES/IRAP)',
            'IPA' => 'Imposte patrimoniali',
        ],
    ];

    public function __construct(int $companyId)
    {
        $this->companyId = $companyId;
    }

    /**
     * Recupera i totali per codice bilancio per un dato anno
     */
    private function getTotaliPerCodice(int $anno): array
    {
        $rows = Database::fetchAll(
            "SELECT c.codice_bilancio, pn.tipo,
             COALESCE(SUM(pn.importo),0) AS totale
             FROM prima_nota pn
             JOIN causali c ON c.id = pn.causale_id
             WHERE pn.company_id = ? AND YEAR(pn.data_movimento) = ? AND pn.annullato = 0
             GROUP BY c.codice_bilancio, pn.tipo",
            [$this->companyId, $anno]
        );

        $totali = [];
        foreach ($rows as $r) {
            $cod = $r['codice_bilancio'];
            if (!isset($totali[$cod])) $totali[$cod] = 0.0;
            // Per uscite il segno è negativo nel calcolo
            $totali[$cod] += (float)$r['totale'];
        }
        return $totali;
    }

    /**
     * Rendiconto completo per un anno
     */
    public function getRendiconto(int $anno): array
    {
        $totali = $this->getTotaliPerCodice($anno);
        $r      = [];

        // Sezioni A-E
        foreach (['A','B','C','D','E'] as $sez) {
            $r[$sez] = [
                'label_uscite'  => "Uscite da attività " . $this->sezioneLabel($sez),
                'label_entrate' => "Entrate da attività " . $this->sezioneLabel($sez),
                'uscite'   => [],
                'entrate'  => [],
                'tot_uscite'  => 0.0,
                'tot_entrate' => 0.0,
                'avanzo'      => 0.0,
            ];
            foreach (self::STRUTTURA["{$sez}_uscite"] as $cod => $label) {
                $val = $totali[$cod] ?? 0.0;
                $r[$sez]['uscite'][$cod] = ['label' => $label, 'importo' => $val];
                $r[$sez]['tot_uscite'] += $val;
            }
            foreach (self::STRUTTURA["{$sez}_entrate"] as $cod => $label) {
                $val = $totali[$cod] ?? 0.0;
                $r[$sez]['entrate'][$cod] = ['label' => $label, 'importo' => $val];
                $r[$sez]['tot_entrate'] += $val;
            }
            $r[$sez]['avanzo'] = $r[$sez]['tot_entrate'] - $r[$sez]['tot_uscite'];
        }

        // Totali gestione
        $totUsciteGestione  = array_sum(array_column(array_map(fn($s) => ['u' => $s['tot_uscite']], $r), 'u'));
        $totEntGestione     = array_sum(array_column(array_map(fn($s) => ['e' => $s['tot_entrate']], $r), 'e'));
        $totUsciteGestione  = array_sum(array_map(fn($s) => $s['tot_uscite'], $r));
        $totEntGestione     = array_sum(array_map(fn($s) => $s['tot_entrate'], $r));

        // Imposte
        $imposte = ($totali['IMP'] ?? 0.0) + ($totali['IPA'] ?? 0.0);

        // Avanzo/disavanzo gestione (prima degli inv.)
        $avanzoGestione = $totEntGestione - $totUsciteGestione - $imposte;

        // Investimenti / Disinvestimenti
        $inv  = [];
        $totInv  = 0.0;
        foreach (self::STRUTTURA['investimenti'] as $cod => $label) {
            $val = $totali[$cod] ?? 0.0;
            $inv[$cod] = ['label' => $label, 'importo' => $val];
            $totInv += $val;
        }
        $dis  = [];
        $totDis  = 0.0;
        foreach (self::STRUTTURA['disinvestimenti'] as $cod => $label) {
            $val = $totali[$cod] ?? 0.0;
            $dis[$cod] = ['label' => $label, 'importo' => $val];
            $totDis += $val;
        }
        $avanzoInvDis = $totDis - $totInv;

        // Figurativi
        $fig = [];
        foreach (self::STRUTTURA['figurativi'] as $cod => $label) {
            $fig[$cod] = ['label' => $label, 'importo' => $totali[$cod] ?? 0.0];
        }

        $avanzoComplessivo = $avanzoGestione + $avanzoInvDis;

        return [
            'sezioni'           => $r,
            'tot_uscite_gest'   => $totUsciteGestione,
            'tot_entrate_gest'  => $totEntGestione,
            'imposte'           => $imposte,
            'avanzo_gestione'   => $avanzoGestione,
            'investimenti'      => $inv,
            'disinvestimenti'   => $dis,
            'tot_inv'           => $totInv,
            'tot_dis'           => $totDis,
            'avanzo_inv_dis'    => $avanzoInvDis,
            'avanzo_complessivo' => $avanzoComplessivo,
            'figurativi'        => $fig,
            'anno'              => $anno,
        ];
    }

    /**
     * Rendiconto sintetico (solo livello sezione, lettere A-E)
     */
    public function getRendicontoSintetico(int $anno): array
    {
        $full = $this->getRendiconto($anno);
        $sintetico = [];
        foreach ($full['sezioni'] as $sez => $data) {
            $sintetico[$sez] = [
                'tot_uscite'  => $data['tot_uscite'],
                'tot_entrate' => $data['tot_entrate'],
                'avanzo'      => $data['avanzo'],
            ];
        }
        return [
            'sezioni'           => $sintetico,
            'tot_uscite_gest'   => $full['tot_uscite_gest'],
            'tot_entrate_gest'  => $full['tot_entrate_gest'],
            'imposte'           => $full['imposte'],
            'avanzo_gestione'   => $full['avanzo_gestione'],
            'tot_inv'           => $full['tot_inv'],
            'tot_dis'           => $full['tot_dis'],
            'avanzo_inv_dis'    => $full['avanzo_inv_dis'],
            'avanzo_complessivo' => $full['avanzo_complessivo'],
            'anno'              => $anno,
        ];
    }

    /**
     * Saldi cassa e banca a fine anno
     */
    public function getSaldiConti(int $anno): array
    {
        return Database::fetchAll(
            "SELECT a.nome, a.tipo, a.saldo_iniziale,
             COALESCE(SUM(CASE WHEN pn.tipo='entrata' AND pn.annullato=0 THEN pn.importo ELSE 0 END),0) AS entrate,
             COALESCE(SUM(CASE WHEN pn.tipo='uscita'  AND pn.annullato=0 THEN pn.importo ELSE 0 END),0) AS uscite
             FROM accounts a
             LEFT JOIN prima_nota pn ON pn.account_id = a.id AND YEAR(pn.data_movimento) <= ?
             WHERE a.company_id = ? AND a.attivo = 1
             GROUP BY a.id ORDER BY a.ordine",
            [$anno, $this->companyId]
        );
    }

    /**
     * Test di secondarietà delle attività diverse ETS (art. 6 CTS)
     * 1° Test: ricavi commerciali < 30% entrate totali
     *          ricavi commerciali < 66% costi complessivi
     * 2° Test: confronto entrate commerciali vs non commerciali
     */
    public function getTestSecondarieta(int $anno): array
    {
        $full = $this->getRendiconto($anno);
        $sezioni = $full['sezioni'];

        // Entrate totali di gestione
        $entrateIstit    = $sezioni['A']['tot_entrate'] + $sezioni['C']['tot_entrate'] + $sezioni['D']['tot_entrate'] + $sezioni['E']['tot_entrate'];
        $entrateCommerciali = $sezioni['B']['tot_entrate']; // Attività diverse (commerciali)
        $entrateTotali   = $full['tot_entrate_gest'];

        // Costi complessivi
        $costiTotali     = $full['tot_uscite_gest'] + $full['imposte'];

        // 1° TEST
        $test1a = $entrateTotali > 0 ? ($entrateCommerciali / $entrateTotali * 100) : 0;
        $test1b = $costiTotali > 0   ? ($entrateCommerciali / $costiTotali * 100) : 0;
        $test1Superato = ($test1a < 30) && ($test1b < 66);

        // 2° TEST - Entrate non commerciali vs commerciali
        $entrateNonComm = $sezioni['A']['tot_entrate']; // Quote + erogazioni + contributi istituzionali
        $entrateComm2   = $sezioni['B']['tot_entrate'] + $sezioni['B']['tot_uscite']; // Net commercial

        return [
            'entrate_totali'        => $entrateTotali,
            'entrate_commerciali'   => $entrateCommerciali,
            'entrate_istituzionali' => $entrateIstit,
            'costi_totali'          => $costiTotali,
            'test1_perc_entrate'    => $test1a,
            'test1_perc_costi'      => $test1b,
            'test1_superato'        => $test1Superato,
            'test1_req_a'           => $test1a < 30,
            'test1_req_b'           => $test1b < 66,
            'entrate_non_comm'      => $entrateNonComm,
            'entrate_comm_nette'    => $entrateComm2,
            'secondarieta_rispettata' => $test1Superato,
            'anno'                  => $anno,
        ];
    }

    private function sezioneLabel(string $sez): string
    {
        return match($sez) {
            'A' => 'di interesse generale',
            'B' => 'diverse',
            'C' => 'di raccolta fondi',
            'D' => 'finanziarie e patrimoniali',
            'E' => 'di supporto generale',
            default => $sez,
        };
    }
}
