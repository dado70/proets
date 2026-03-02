<?php
$sezLabel = ['A'=>'Interesse Generale','B'=>'Attività Diverse','C'=>'Raccolta Fondi','D'=>'Finanziarie e Patrimoniali','E'=>'Supporto Generale'];
$e = fn($v) => htmlspecialchars((string)$v);
$fmt = fn($v) => number_format(abs((float)$v),2,',','.');
$fmtSign = function($v) {
    $v = (float)$v;
    return ($v >= 0 ? '' : '-') . '€ ' . number_format(abs($v),2,',','.');
};
?>
<!-- Toolbar -->
<div class="d-flex justify-content-between align-items-center mb-3">
  <div class="d-flex align-items-center gap-2">
    <select class="form-select form-select-sm" style="width:100px" onchange="location.href='/rendiconto/annuale?anno='+this.value">
      <?php foreach ($anni as $a): ?>
      <option value="<?= $a ?>" <?= $a==$esercizio?'selected':'' ?>><?= $a ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="d-flex gap-2">
    <a href="/rendiconto/pdf/annuale?anno=<?= $esercizio ?>" class="btn btn-sm btn-danger no-print">
      <i class="bi bi-file-earmark-pdf me-1"></i>PDF
    </a>
    <button onclick="window.print()" class="btn btn-sm btn-outline-secondary no-print">
      <i class="bi bi-printer me-1"></i>Stampa
    </button>
    <a href="/rendiconto/sintetico?anno=<?= $esercizio ?>" class="btn btn-sm btn-outline-primary no-print">Sintetico</a>
    <a href="/rendiconto/scostamenti?anno=<?= $esercizio ?>" class="btn btn-sm btn-outline-primary no-print">Scostamenti</a>
  </div>
</div>

<!-- Intestazione stampa -->
<div class="card mb-3">
  <div class="card-body py-3 text-center">
    <div class="fw-bold fs-5"><?= $e($company['ragione_sociale'] ?? '') ?></div>
    <div class="text-muted small">
      <?= $e($company['indirizzo'] ?? '') ?> — CF: <?= $e($company['codice_fiscale'] ?? '') ?>
      <?php if ($company['nr_iscrizione_runts']): ?> — RUNTS: <?= $e($company['nr_iscrizione_runts']) ?><?php endif; ?>
    </div>
    <div class="fw-bold mt-1">RENDICONTO PER CASSA <?= $esercizio ?> <span class="fw-normal small">(art. 13, comma 2, D.Lgs 117/2017)</span></div>
  </div>
</div>

<div class="card">
  <div class="table-responsive">
    <table class="table table-sm table-bordered mb-0" style="font-size:.82rem">
      <thead class="table-dark">
        <tr>
          <th style="width:40px">Cod.</th>
          <th>USCITE</th>
          <th style="width:110px" class="text-end"><?= $esercizio ?></th>
          <th style="width:110px" class="text-end"><?= $esercizioPrec ?></th>
          <th style="width:40px">Cod.</th>
          <th>ENTRATE</th>
          <th style="width:110px" class="text-end"><?= $esercizio ?></th>
          <th style="width:110px" class="text-end"><?= $esercizioPrec ?></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($dati['sezioni'] as $sez => $sezDati):
          $maxRows = max(count($sezDati['uscite']), count($sezDati['entrate']));
          $usciteArr  = array_values($sezDati['uscite']);
          $entrateArr = array_values($sezDati['entrate']);
          $uCods = array_keys($sezDati['uscite']);
          $eCods = array_keys($sezDati['entrate']);
        ?>
        <!-- Intestazione sezione -->
        <tr class="table-primary">
          <td colspan="4" class="fw-bold">
            <?= $sez ?>) Uscite da attività di <?= strtolower($sezLabel[$sez]) ?>
          </td>
          <td colspan="4" class="fw-bold">
            <?= $sez ?>) Entrate da attività di <?= strtolower($sezLabel[$sez]) ?>
          </td>
        </tr>
        <!-- Righe voce per voce -->
        <?php for ($i=0; $i<$maxRows; $i++): ?>
        <tr>
          <?php if (isset($usciteArr[$i])): $u=$usciteArr[$i]; $uCod=$uCods[$i]; ?>
          <td class="text-muted text-nowrap"><?= $e($uCod) ?></td>
          <td><?= $e($u['label']) ?></td>
          <td class="text-end <?= $u['importo']>0?'text-uscita':'' ?>"><?= $u['importo']>0 ? '€ '.$fmt($u['importo']) : '-' ?></td>
          <td class="text-end text-muted"><?= ($datiPrec['sezioni'][$sez]['uscite'][$uCod]['importo']??0)>0 ? '€ '.$fmt($datiPrec['sezioni'][$sez]['uscite'][$uCod]['importo']??0) : '-' ?></td>
          <?php else: ?><td colspan="4"></td><?php endif; ?>

          <?php if (isset($entrateArr[$i])): $en=$entrateArr[$i]; $eCod=$eCods[$i]; ?>
          <td class="text-muted text-nowrap"><?= $e($eCod) ?></td>
          <td><?= $e($en['label']) ?></td>
          <td class="text-end <?= $en['importo']>0?'text-entrata':'' ?>"><?= $en['importo']>0 ? '€ '.$fmt($en['importo']) : '-' ?></td>
          <td class="text-end text-muted"><?= ($datiPrec['sezioni'][$sez]['entrate'][$eCod]['importo']??0)>0 ? '€ '.$fmt($datiPrec['sezioni'][$sez]['entrate'][$eCod]['importo']??0) : '-' ?></td>
          <?php else: ?><td colspan="4"></td><?php endif; ?>
        </tr>
        <?php endfor; ?>
        <!-- Totali sezione -->
        <tr class="table-light fw-semibold">
          <td colspan="2">Totale <?= $sez ?></td>
          <td class="text-end text-uscita">€ <?= $fmt($sezDati['tot_uscite']) ?></td>
          <td class="text-end text-muted">€ <?= $fmt($datiPrec['sezioni'][$sez]['tot_uscite']??0) ?></td>
          <td colspan="2">Totale <?= $sez ?></td>
          <td class="text-end text-entrata">€ <?= $fmt($sezDati['tot_entrate']) ?></td>
          <td class="text-end text-muted">€ <?= $fmt($datiPrec['sezioni'][$sez]['tot_entrate']??0) ?></td>
        </tr>
        <!-- Avanzo/disavanzo sezione -->
        <tr class="<?= $sezDati['avanzo']>=0?'table-success':'table-danger' ?> fw-bold">
          <td colspan="8" class="text-end">
            Avanzo/disavanzo attività di <?= strtolower($sezLabel[$sez]) ?>:
            <?= $fmtSign($sezDati['avanzo']) ?>
            <?php if ($esercizioPrec && isset($datiPrec['sezioni'][$sez])): ?>
            <span class="text-muted fw-normal ms-3">(<?= $esercizioPrec ?>: <?= $fmtSign($datiPrec['sezioni'][$sez]['avanzo']??0) ?>)</span>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>

        <!-- Totali Gestione -->
        <tr class="table-dark fw-bold">
          <td colspan="2">Totale uscite della gestione</td>
          <td class="text-end">€ <?= $fmt($dati['tot_uscite_gest']) ?></td>
          <td class="text-end text-muted">€ <?= $fmt($datiPrec['tot_uscite_gest']??0) ?></td>
          <td colspan="2">Totale entrate della gestione</td>
          <td class="text-end">€ <?= $fmt($dati['tot_entrate_gest']) ?></td>
          <td class="text-end text-muted">€ <?= $fmt($datiPrec['tot_entrate_gest']??0) ?></td>
        </tr>
        <tr class="<?= ($dati['avanzo_gestione'])>=0?'table-success':'table-danger' ?> fw-bold">
          <td colspan="6" class="text-end">Avanzo/disavanzo d'esercizio prima delle imposte:</td>
          <td class="text-end"><?= $fmtSign($dati['avanzo_gestione']) ?></td>
          <td class="text-end text-muted"><?= $fmtSign($datiPrec['avanzo_gestione']??0) ?></td>
        </tr>
        <?php if ($dati['imposte'] > 0): ?>
        <tr>
          <td colspan="6" class="text-end">Imposte (IRES/IRAP):</td>
          <td class="text-end text-uscita">€ <?= $fmt($dati['imposte']) ?></td>
          <td class="text-end text-muted">€ <?= $fmt($datiPrec['imposte']??0) ?></td>
        </tr>
        <?php endif; ?>

        <!-- Investimenti / Disinvestimenti -->
        <tr class="table-secondary fw-bold">
          <td colspan="4">Uscite da investimenti in immobilizzazioni</td>
          <td colspan="4">Entrate da disinvestimenti in immobilizzazioni</td>
        </tr>
        <?php
        $invArr = array_values($dati['investimenti']); $invCods = array_keys($dati['investimenti']);
        $disArr = array_values($dati['disinvestimenti']); $disCods = array_keys($dati['disinvestimenti']);
        $maxInvDis = max(count($invArr), count($disArr));
        for ($i=0; $i<$maxInvDis; $i++):
        ?>
        <tr>
          <?php if (isset($invArr[$i])): $inv=$invArr[$i]; $invCod=$invCods[$i]; ?>
          <td class="text-muted"><?= $e($invCod) ?></td>
          <td><?= $e($inv['label']) ?></td>
          <td class="text-end <?= $inv['importo']>0?'text-uscita':'' ?>"><?= $inv['importo']>0?'€ '.$fmt($inv['importo']):'-' ?></td>
          <td class="text-end text-muted">-</td>
          <?php else: ?><td colspan="4"></td><?php endif; ?>
          <?php if (isset($disArr[$i])): $dis=$disArr[$i]; $disCod=$disCods[$i]; ?>
          <td class="text-muted"><?= $e($disCod) ?></td>
          <td><?= $e($dis['label']) ?></td>
          <td class="text-end <?= $dis['importo']>0?'text-entrata':'' ?>"><?= $dis['importo']>0?'€ '.$fmt($dis['importo']):'-' ?></td>
          <td class="text-end text-muted">-</td>
          <?php else: ?><td colspan="4"></td><?php endif; ?>
        </tr>
        <?php endfor; ?>
        <tr class="table-light fw-semibold">
          <td colspan="2">Totale investimenti</td>
          <td class="text-end text-uscita">€ <?= $fmt($dati['tot_inv']) ?></td>
          <td></td>
          <td colspan="2">Totale disinvestimenti</td>
          <td class="text-end text-entrata">€ <?= $fmt($dati['tot_dis']) ?></td>
          <td></td>
        </tr>

        <!-- Avanzo complessivo -->
        <tr class="<?= $dati['avanzo_complessivo']>=0?'table-success':'table-danger' ?> fw-bold fs-6">
          <td colspan="6" class="text-end">Avanzo/disavanzo complessivo d'esercizio:</td>
          <td class="text-end"><?= $fmtSign($dati['avanzo_complessivo']) ?></td>
          <td class="text-end text-muted"><?= $fmtSign($datiPrec['avanzo_complessivo']??0) ?></td>
        </tr>
      </tbody>
    </table>
  </div>
</div>

<!-- Saldi cassa e banca -->
<?php if (!empty($saldi)): ?>
<div class="card mt-3">
  <div class="card-header"><i class="bi bi-wallet2 me-2"></i>Cassa e Banca a fine <?= $esercizio ?></div>
  <div class="table-responsive">
    <table class="table table-sm mb-0">
      <thead><tr><th>Conto</th><th>Tipo</th><th class="text-end">Saldo</th></tr></thead>
      <tbody>
        <?php foreach ($saldi as $s):
          $saldo = $s['saldo_iniziale'] + $s['entrate'] - $s['uscite'];
        ?>
        <tr>
          <td><?= $e($s['nome']) ?></td>
          <td class="text-muted small"><?= $e($s['tipo']) ?></td>
          <td class="text-end fw-semibold <?= $saldo>=0?'text-success':'text-danger' ?>">€ <?= $fmt($saldo) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<!-- Link test secondarietà -->
<div class="alert alert-info mt-3 py-2 no-print">
  <i class="bi bi-shield-check me-2"></i>
  Vuoi verificare la secondarietà delle attività diverse?
  <a href="/rendiconto/test-ets?anno=<?= $esercizio ?>" class="btn btn-sm btn-info ms-2">Test Secondarietà ETS</a>
</div>
