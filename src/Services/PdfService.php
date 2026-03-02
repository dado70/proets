<?php
declare(strict_types=1);

namespace ProETS\Services;

/**
 * PdfService
 * Genera PDF tramite TCPDF (se disponibile) o una versione HTML stampabile.
 */
class PdfService
{
    private bool $hasTcpdf;

    public function __construct()
    {
        $this->hasTcpdf = class_exists('\\TCPDF');
    }

    public function rendicontoAnnuale(array $company, int $anno, array $dati, array $datiPrec, array $saldi): void
    {
        $title    = "Rendiconto Annuale {$anno}";
        $filename = "rendiconto_{$anno}.pdf";
        $this->outputHtmlPdf($title, $filename, $this->htmlRendiconto($company, $anno, $dati, $datiPrec, $saldi));
    }

    public function rendicontoSintetico(array $company, int $anno, array $dati, array $datiPrec): void
    {
        $title    = "Rendiconto Sintetico {$anno}";
        $filename = "rendiconto_sintetico_{$anno}.pdf";
        $this->outputHtmlPdf($title, $filename, $this->htmlRendicontoSintetico($company, $anno, $dati, $datiPrec));
    }

    public function budget(array $company, array $budget, array $voci): void
    {
        $title    = "Preventivo {$budget['esercizio']}";
        $filename = "preventivo_{$budget['esercizio']}.pdf";
        $this->outputHtmlPdf($title, $filename, $this->htmlBudget($company, $budget, $voci));
    }

    public function tessera(array $socio, array $company): void
    {
        $title    = "Tessera {$socio['cognome']} {$socio['nome']}";
        $filename = "tessera_{$socio['numero_tessera']}.pdf";
        $this->outputHtmlPdf($title, $filename, $this->htmlTessera($socio, $company));
    }

    // ---- HTML generators ----

    private function htmlRendiconto(array $co, int $anno, array $d, array $dp, array $saldi): string
    {
        $e   = fn($v) => htmlspecialchars((string)$v);
        $fmt = fn($v) => '€ ' . number_format(abs((float)$v),2,',','.');
        $sezLabel = ['A'=>'Interesse Generale','B'=>'Attività Diverse','C'=>'Raccolta Fondi','D'=>'Finanziarie e Patrimoniali','E'=>'Supporto Generale'];

        $h = $this->htmlHeader($co, "RENDICONTO PER CASSA {$anno}", "art. 13, comma 2, D.Lgs 117/2017");
        $h .= '<table class="t" cellspacing="0"><thead><tr><th width="5%">Cod</th><th width="30%">USCITE</th><th width="10%" align="right">'.$anno.'</th><th width="5%">Cod</th><th width="30%">ENTRATE</th><th width="10%" align="right">'.$anno.'</th></tr></thead><tbody>';

        foreach ($d['sezioni'] as $sez => $sd) {
            $h .= "<tr class='sh'><td colspan='3'>{$sez}) Uscite da attività di ".strtolower($sezLabel[$sez])."</td><td colspan='3'>{$sez}) Entrate da attività di ".strtolower($sezLabel[$sez])."</td></tr>";
            $uCods = array_keys($sd['uscite']); $eCods = array_keys($sd['entrate']);
            $uArr  = array_values($sd['uscite']); $eArr = array_values($sd['entrate']);
            $max   = max(count($uArr),count($eArr));
            for ($i=0; $i<$max; $i++) {
                $h .= '<tr>';
                if (isset($uArr[$i])) $h .= "<td class='cod'>{$e($uCods[$i])}</td><td>{$e($uArr[$i]['label'])}</td><td align='right'>" . ($uArr[$i]['importo']>0 ? $fmt($uArr[$i]['importo']) : '-') . "</td>";
                else $h .= '<td colspan="3"></td>';
                if (isset($eArr[$i])) $h .= "<td class='cod'>{$e($eCods[$i])}</td><td>{$e($eArr[$i]['label'])}</td><td align='right'>" . ($eArr[$i]['importo']>0 ? $fmt($eArr[$i]['importo']) : '-') . "</td>";
                else $h .= '<td colspan="3"></td>';
                $h .= '</tr>';
            }
            $h .= "<tr class='tot'><td colspan='2'>Totale {$sez}</td><td align='right'>{$fmt($sd['tot_uscite'])}</td><td colspan='2'>Totale {$sez}</td><td align='right'>{$fmt($sd['tot_entrate'])}</td></tr>";
        }
        $h .= "<tr class='grand'><td colspan='2'>Totale uscite gestione</td><td align='right'>{$fmt($d['tot_uscite_gest'])}</td><td colspan='2'>Totale entrate gestione</td><td align='right'>{$fmt($d['tot_entrate_gest'])}</td></tr>";
        $h .= "<tr class='avanzo'><td colspan='5' align='right'>Avanzo/Disavanzo d'esercizio:</td><td align='right'><b>" . $fmt($d['avanzo_complessivo']) . "</b></td></tr>";
        $h .= '</tbody></table>';

        // Saldi
        if (!empty($saldi)) {
            $h .= '<h4 style="margin-top:20px">Cassa e Banca a fine ' . $anno . '</h4><table class="t" cellspacing="0"><tr><th>Conto</th><th align="right">Saldo</th></tr>';
            foreach ($saldi as $s) {
                $saldo = $s['saldo_iniziale'] + $s['entrate'] - $s['uscite'];
                $h .= "<tr><td>{$e($s['nome'])}</td><td align='right'>{$fmt($saldo)}</td></tr>";
            }
            $h .= '</table>';
        }

        return $h . $this->htmlFooter($co);
    }

    private function htmlRendicontoSintetico(array $co, int $anno, array $d, array $dp): string
    {
        $e   = fn($v) => htmlspecialchars((string)$v);
        $fmt = fn($v) => '€ ' . number_format(abs((float)$v),2,',','.');
        $sezLabel = ['A'=>'Interesse Generale','B'=>'Attività Diverse','C'=>'Raccolta Fondi','D'=>'Finanziarie e Patrimoniali','E'=>'Supporto Generale'];

        $h = $this->htmlHeader($co, "RENDICONTO SINTETICO {$anno}", "art. 13, comma 2, D.Lgs 117/2017");
        $h .= '<table class="t" cellspacing="0"><thead><tr><th>Sezione</th><th align="right">Uscite</th><th align="right">Entrate</th><th align="right">Avanzo/Disavanzo</th></tr></thead><tbody>';
        foreach ($d['sezioni'] as $sez => $sd) {
            $h .= "<tr><td><b>{$sez}) {$sezLabel[$sez]}</b></td><td align='right'>{$fmt($sd['tot_uscite'])}</td><td align='right'>{$fmt($sd['tot_entrate'])}</td><td align='right'>" . $fmt($sd['avanzo']) . "</td></tr>";
        }
        $h .= "<tr class='grand'><td>Totale gestione</td><td align='right'>{$fmt($d['tot_uscite_gest'])}</td><td align='right'>{$fmt($d['tot_entrate_gest'])}</td><td align='right'>{$fmt($d['avanzo_gestione'])}</td></tr>";
        $h .= "<tr class='avanzo'><td colspan='3' align='right'>Avanzo/Disavanzo complessivo:</td><td align='right'><b>{$fmt($d['avanzo_complessivo'])}</b></td></tr>";
        $h .= '</tbody></table>';
        return $h . $this->htmlFooter($co);
    }

    private function htmlBudget(array $co, array $budget, array $voci): string
    {
        $e    = fn($v) => htmlspecialchars((string)$v);
        $fmt  = fn($v) => '€ ' . number_format(abs((float)$v),2,',','.');
        $h    = $this->htmlHeader($co, "PREVENTIVO {$budget['esercizio']}", $budget['nome']);
        $h   .= '<table class="t" cellspacing="0"><thead><tr><th>Cod.</th><th>Voce</th><th>Tipo</th><th align="right">Importo</th></tr></thead><tbody>';
        $totE = 0; $totU = 0;
        foreach ($voci as $v) {
            $h .= "<tr><td class='cod'>{$e($v['codice_bilancio'])}</td><td>{$e($v['descrizione'])}</td><td>{$e($v['tipo'])}</td><td align='right'>{$fmt($v['importo_preventivo'])}</td></tr>";
            if ($v['tipo']==='entrata') $totE += $v['importo_preventivo'];
            else $totU += $v['importo_preventivo'];
        }
        $h .= "<tr class='grand'><td colspan='3'>Totale Entrate</td><td align='right'>{$fmt($totE)}</td></tr>";
        $h .= "<tr class='grand'><td colspan='3'>Totale Uscite</td><td align='right'>{$fmt($totU)}</td></tr>";
        $avanzo = $totE - $totU;
        $h .= "<tr class='avanzo'><td colspan='3'>Avanzo/Disavanzo previsto</td><td align='right'><b>{$fmt($avanzo)}</b></td></tr>";
        $h .= '</tbody></table>';
        if ($budget['stato']==='approvato') {
            $h .= '<p style="margin-top:30px;font-size:10px">Approvato il ' . $e($budget['data_approvazione']) . '</p>';
        }
        return $h . $this->htmlFooter($co);
    }

    private function htmlTessera(array $socio, array $company): string
    {
        $e = fn($v) => htmlspecialchars((string)$v);
        return <<<HTML
        <!DOCTYPE html><html lang="it"><head><meta charset="UTF-8"><style>
        body{font-family:Arial,sans-serif;margin:0;padding:20px;}
        .tessera{border:2px solid #1a3a5c;border-radius:12px;padding:20px;max-width:400px;margin:0 auto;}
        .assoc{font-size:14px;font-weight:bold;color:#1a3a5c;} .nome{font-size:18px;font-weight:bold;margin-top:10px;}
        .info{font-size:12px;color:#555;margin-top:4px;} .tessera-n{font-size:24px;font-weight:900;color:#2563eb;margin-top:12px;}
        </style></head><body>
        <div class="tessera">
          <div class="assoc">{$e($company['ragione_sociale'] ?? '')}</div>
          <div class="nome">{$e($socio['cognome'])} {$e($socio['nome'])}</div>
          <div class="info">C.F.: {$e($socio['codice_fiscale'] ?? '-')}</div>
          <div class="info">Socio {$e($socio['tipo_socio'])} dal {$e($socio['data_iscrizione'])}</div>
          <div class="tessera-n">N° {$e($socio['numero_tessera'] ?? '----')}</div>
          <div class="info" style="margin-top:8px;font-size:10px">Anno {$e(date('Y'))}</div>
        </div></body></html>
        HTML;
    }

    // ---- Layout helpers ----

    private function htmlHeader(array $co, string $title, string $subtitle = ''): string
    {
        $e = fn($v) => htmlspecialchars((string)$v);
        return <<<HTML
        <!DOCTYPE html><html lang="it"><head><meta charset="UTF-8"><style>
        body{font-family:Arial,sans-serif;font-size:11px;margin:20px;}
        h1{font-size:14px;margin:0;color:#1a3a5c;} h2{font-size:11px;margin:2px 0;color:#666;}
        .header{border-bottom:2px solid #1a3a5c;padding-bottom:8px;margin-bottom:12px;text-align:center;}
        table.t{width:100%;border-collapse:collapse;margin-top:8px;}
        table.t th{background:#1a3a5c;color:#fff;padding:4px 6px;text-align:left;font-size:10px;}
        table.t td{padding:3px 6px;border-bottom:1px solid #eee;}
        tr.sh td{background:#dbeafe;font-weight:bold;font-size:10px;}
        tr.tot td{background:#f1f5f9;font-weight:bold;}
        tr.grand td{background:#1a3a5c;color:#fff;font-weight:bold;}
        tr.avanzo td{background:#16a34a;color:#fff;font-weight:bold;}
        .cod{font-size:9px;color:#888;} .footer{margin-top:20px;font-size:9px;color:#aaa;text-align:center;}
        </style></head><body>
        <div class="header">
          <div style="font-weight:bold;font-size:13px">{$e($co['ragione_sociale'] ?? '')}</div>
          <div style="font-size:10px;color:#555">{$e($co['indirizzo'] ?? '')} — CF: {$e($co['codice_fiscale'] ?? '')}</div>
          <h1 style="margin-top:8px">{$e($title)}</h1>
          <h2>{$e($subtitle)}</h2>
        </div>
        HTML;
    }

    private function htmlFooter(array $co): string
    {
        $e = fn($v) => htmlspecialchars((string)$v);
        return '<div class="footer">Generato da ProETS — ' . date('d/m/Y H:i') . ' — ' . $e($co['ragione_sociale'] ?? '') . '</div></body></html>';
    }

    // ---- Output ----

    private function outputHtmlPdf(string $title, string $filename, string $html): void
    {
        if ($this->hasTcpdf) {
            $this->outputTcpdf($title, $filename, $html);
        } else {
            // Fallback: stampa HTML (apre in browser, stampa nativa)
            header('Content-Type: text/html; charset=utf-8');
            header('Content-Disposition: inline; filename="' . $filename . '"');
            echo $html . '<script>window.onload=function(){window.print();}</script>';
        }
        exit;
    }

    private function outputTcpdf(string $title, string $filename, string $html): void
    {
        $pdf = new \TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->SetCreator('ProETS');
        $pdf->SetTitle($title);
        $pdf->SetFont('helvetica', '', 9);
        $pdf->SetMargins(10, 15, 10);
        $pdf->SetHeaderMargin(5);
        $pdf->SetFooterMargin(10);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->AddPage();
        $pdf->writeHTMLCell(0, 0, '', '', $html, 0, 1, 0, true, '');
        $pdf->Output($filename, 'D');
    }
}
