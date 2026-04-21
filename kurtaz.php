<?php
// ============================================================
// KONFIGURACJA KURTAŻU — TU ZMIENIAJ WARTOŚCI PROCENTOWE
// ============================================================
// Jeśli procenty kurtażu się zmienią, edytuj tylko te trzy linie:
define('KURTAZ_RZEPAK',       0.11);  // 11% — zmień np. na 0.15 dla 15%
define('KURTAZ_INNE_UPRAWY',  0.14);  // 14% — zmień np. na 0.18 dla 18%
define('KURTAZ_DODATKOWY',    0.04);  //  4% — zmień np. na 0.05 dla 5%
// ============================================================

$wyniki = null;
$errors = [];

function parseKwota(string $val): float {
    $val = str_replace(' ', '', trim($val));
    $val = str_replace(',', '.', $val);
    return (float) $val;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pola = [
        'rzepak_klient'  => $_POST['rzepak_klient']  ?? '',
        'rzepak_panstwo' => $_POST['rzepak_panstwo'] ?? '',
        'inne_klient'    => $_POST['inne_klient']    ?? '',
        'inne_panstwo'   => $_POST['inne_panstwo']   ?? '',
    ];

    foreach (['rzepak_klient', 'rzepak_panstwo', 'inne_klient', 'inne_panstwo'] as $pole) {
        if ($pola[$pole] === '') {
            $errors[$pole] = 'Pole wymagane';
        }
    }

    if (empty($errors)) {
        $skladka_rzepak = parseKwota($pola['rzepak_klient']) + parseKwota($pola['rzepak_panstwo']);
        $skladka_inne   = parseKwota($pola['inne_klient'])   + parseKwota($pola['inne_panstwo']);

        $prowizja_rzepak = $skladka_rzepak * KURTAZ_RZEPAK;
        $prowizja_inne   = $skladka_inne   * KURTAZ_INNE_UPRAWY;

        $suma_skladek  = $skladka_rzepak + $skladka_inne;
        $suma_prowizji = $prowizja_rzepak + $prowizja_inne;

        $kurtaz_procent = ($suma_skladek > 0)
            ? ($suma_prowizji / $suma_skladek) * 100
            : 0;

        $kurtaz_dodatkowy = $suma_skladek * KURTAZ_DODATKOWY;

        $wyniki = [
            'skladka_rzepak'   => $skladka_rzepak,
            'skladka_inne'     => $skladka_inne,
            'prowizja_rzepak'  => $prowizja_rzepak,
            'prowizja_inne'    => $prowizja_inne,
            'suma_skladek'     => $suma_skladek,
            'suma_prowizji'    => $suma_prowizji,
            'kurtaz_procent'   => $kurtaz_procent,
            'kurtaz_dodatkowy' => $kurtaz_dodatkowy,
        ];
    }
}

function fmt(float $val): string {
    return number_format($val, 2, ',', ' ') . ' zł';
}
function fmtPct(float $val): string {
    return number_format($val, 6, ',', ' ') . ' %';
}
function val(string $key): string {
    return htmlspecialchars($_POST[$key] ?? '');
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Kalkulator Kurtażu</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Mono:wght@400;500&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
  :root {
    --bg:        #0f1710;
    --surface:   #172019;
    --card:      #1e2b1f;
    --border:    #2e3f2f;
    --accent:    #7ec97e;
    --accent2:   #b5e8a0;
    --muted:     #5a7a5b;
    --text:      #ddeedd;
    --text-dim:  #8aaa8b;
    --danger:    #e07070;
    --gold:      #d4c97a;
    --radius:    10px;
  }

  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

  body {
    background: var(--bg);
    color: var(--text);
    font-family: 'DM Sans', sans-serif;
    min-height: 100vh;
    padding: 2rem 1rem 4rem;
    background-image:
      radial-gradient(ellipse 60% 40% at 10% 0%, rgba(80,140,60,0.12) 0%, transparent 70%),
      radial-gradient(ellipse 40% 60% at 90% 100%, rgba(60,100,50,0.10) 0%, transparent 70%);
  }

  .wrap { max-width: 760px; margin: 0 auto; }

  header {
    text-align: center;
    margin-bottom: 2.5rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid var(--border);
  }
  header .eyebrow {
    font-family: 'DM Mono', monospace;
    font-size: 0.7rem;
    letter-spacing: 0.18em;
    color: var(--accent);
    text-transform: uppercase;
    margin-bottom: 0.5rem;
  }
  header h1 {
    font-family: 'DM Serif Display', serif;
    font-size: 2.4rem;
    font-weight: 400;
    color: var(--accent2);
    line-height: 1.15;
  }
  header p { margin-top: 0.5rem; font-size: 0.88rem; color: var(--text-dim); }

  .rates-badge {
    display: inline-flex;
    gap: 1.2rem;
    margin-top: 1rem;
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: 40px;
    padding: 0.4rem 1.2rem;
    font-family: 'DM Mono', monospace;
    font-size: 0.78rem;
    color: var(--text-dim);
  }
  .rates-badge span { color: var(--accent); font-weight: 500; }

  form { display: flex; flex-direction: column; gap: 1.4rem; }

  .section-card {
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    overflow: hidden;
  }
  .section-header {
    display: flex;
    align-items: center;
    gap: 0.7rem;
    padding: 0.85rem 1.2rem;
    background: rgba(126,201,126,0.06);
    border-bottom: 1px solid var(--border);
  }
  .section-header .tag {
    font-family: 'DM Mono', monospace;
    font-size: 0.68rem;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    background: rgba(126,201,126,0.15);
    color: var(--accent);
    border-radius: 4px;
    padding: 0.18em 0.55em;
  }
  .section-header h2 {
    font-family: 'DM Serif Display', serif;
    font-size: 1.05rem;
    font-weight: 400;
    color: var(--text);
  }
  .section-header .pct-badge {
    margin-left: auto;
    font-family: 'DM Mono', monospace;
    font-size: 0.78rem;
    color: var(--gold);
  }

  .fields { display: grid; grid-template-columns: 1fr 1fr; gap: 0; }
  .field {
    padding: 1rem 1.2rem;
    border-right: 1px solid var(--border);
    border-bottom: 1px solid var(--border);
  }
  .field:nth-child(even) { border-right: none; }
  .field:nth-last-child(-n+2) { border-bottom: none; }

  .field label { display: block; font-size: 0.75rem; color: var(--text-dim); margin-bottom: 0.45rem; }
  .field .input-wrap { position: relative; display: flex; align-items: center; }
  .field input {
    width: 100%;
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 6px;
    color: var(--text);
    font-family: 'DM Mono', monospace;
    font-size: 1rem;
    padding: 0.55rem 2.2rem 0.55rem 0.75rem;
    transition: border-color 0.18s, box-shadow 0.18s;
    outline: none;
  }
  .field input:focus { border-color: var(--accent); box-shadow: 0 0 0 3px rgba(126,201,126,0.12); }
  .field input.err { border-color: var(--danger); }
  .field .unit { position: absolute; right: 0.65rem; font-size: 0.7rem; color: var(--muted); font-family: 'DM Mono', monospace; pointer-events: none; }
  .field .error-msg { font-size: 0.7rem; color: var(--danger); margin-top: 0.3rem; }

  .btn-row { display: flex; gap: 1rem; }
  button[type="submit"] {
    flex: 1;
    background: var(--accent);
    color: var(--bg);
    border: none;
    border-radius: var(--radius);
    padding: 0.9rem 2rem;
    font-family: 'DM Sans', sans-serif;
    font-size: 0.95rem;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.18s, transform 0.1s;
  }
  button[type="submit"]:hover { background: var(--accent2); transform: translateY(-1px); }
  button[type="reset"] {
    background: transparent;
    color: var(--text-dim);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    padding: 0.9rem 1.4rem;
    font-family: 'DM Sans', sans-serif;
    font-size: 0.9rem;
    cursor: pointer;
    transition: border-color 0.18s, color 0.18s;
  }
  button[type="reset"]:hover { border-color: var(--muted); color: var(--text); }

  .results {
    background: var(--card);
    border: 1px solid var(--accent);
    border-radius: var(--radius);
    overflow: hidden;
    animation: fadeIn 0.35s ease;
  }
  @keyframes fadeIn { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }

  .results-header {
    padding: 0.9rem 1.4rem;
    background: rgba(126,201,126,0.08);
    border-bottom: 1px solid var(--border);
  }
  .results-header h3 {
    font-family: 'DM Serif Display', serif;
    font-size: 1.1rem;
    font-weight: 400;
    color: var(--accent2);
  }

  .results-grid { display: grid; grid-template-columns: 1fr 1fr; }
  .res-block {
    padding: 1rem 1.4rem;
    border-right: 1px solid var(--border);
    border-bottom: 1px solid var(--border);
  }
  .res-block:nth-child(even) { border-right: none; }
  .res-block:nth-last-child(-n+2) { border-bottom: none; }

  .res-block .res-label {
    font-size: 0.72rem;
    color: var(--text-dim);
    margin-bottom: 0.3rem;
    text-transform: uppercase;
    letter-spacing: 0.06em;
  }
  .res-block .res-val { font-family: 'DM Mono', monospace; font-size: 1.1rem; color: var(--text); }
  .res-block .res-val.highlight { color: var(--accent2); font-size: 1.25rem; }
  .res-block .res-val.gold { color: var(--gold); font-size: 1.25rem; }

  .kurtaz-banner {
    padding: 1.2rem 1.4rem;
    background: rgba(212,201,122,0.07);
    border-top: 1px solid rgba(212,201,122,0.25);
    display: flex;
    align-items: center;
    justify-content: space-between;
  }
  .kurtaz-banner .kb-label {
    font-size: 0.82rem;
    color: var(--text-dim);
    text-transform: uppercase;
    letter-spacing: 0.1em;
    font-family: 'DM Mono', monospace;
  }
  .kurtaz-banner .kb-val {
    font-family: 'DM Serif Display', serif;
    font-size: 2.2rem;
    color: var(--gold);
    line-height: 1;
  }

  @media (max-width: 500px) {
    .fields { grid-template-columns: 1fr; }
    .field { border-right: none; }
    .field:nth-last-child(-n+2) { border-bottom: 1px solid var(--border); }
    .field:last-child { border-bottom: none; }
    .results-grid { grid-template-columns: 1fr; }
    .res-block { border-right: none; }
    .res-block:nth-last-child(-n+2) { border-bottom: 1px solid var(--border); }
    .res-block:last-child { border-bottom: none; }
    header h1 { font-size: 1.8rem; }
  }
</style>
</head>
<body>
<div class="wrap">

  <header>
    <div class="eyebrow">Ubezpieczenia rolne</div>
    <h1>Kalkulator Kurtażu</h1>
    <p>Obliczanie prowizji i kurtażu dla upraw rolnych</p>
    <div class="rates-badge">
      Rzepak: <span><?= round(KURTAZ_RZEPAK * 100, 2) ?>%</span>
      &nbsp;|&nbsp;
      Inne uprawy: <span><?= round(KURTAZ_INNE_UPRAWY * 100, 2) ?>%</span>
    </div>
  </header>

  <form method="POST" action="" autocomplete="off">

    <!-- RZEPAK -->
    <div class="section-card">
      <div class="section-header">
        <span class="tag">Rzepak</span>
        <h2>Składka za Rzepak</h2>
        <span class="pct-badge"><?= round(KURTAZ_RZEPAK * 100, 2) ?>%</span>
      </div>
      <div class="fields">
        <div class="field">
          <label for="rzepak_klient">Składka płatna przez klienta</label>
          <div class="input-wrap">
            <input type="text" inputmode="decimal" id="rzepak_klient" name="rzepak_klient"
              value="<?= val('rzepak_klient') ?>" placeholder="0,00"
              class="<?= isset($errors['rzepak_klient']) ? 'err' : '' ?>">
            <span class="unit">zł</span>
          </div>
          <?php if (isset($errors['rzepak_klient'])): ?>
            <div class="error-msg"><?= $errors['rzepak_klient'] ?></div>
          <?php endif; ?>
        </div>
        <div class="field">
          <label for="rzepak_panstwo">Składka płatna przez państwo</label>
          <div class="input-wrap">
            <input type="text" inputmode="decimal" id="rzepak_panstwo" name="rzepak_panstwo"
              value="<?= val('rzepak_panstwo') ?>" placeholder="0,00"
              class="<?= isset($errors['rzepak_panstwo']) ? 'err' : '' ?>">
            <span class="unit">zł</span>
          </div>
          <?php if (isset($errors['rzepak_panstwo'])): ?>
            <div class="error-msg"><?= $errors['rzepak_panstwo'] ?></div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- INNE UPRAWY -->
    <div class="section-card">
      <div class="section-header">
        <span class="tag">Inne uprawy</span>
        <h2>Składka za Inne Uprawy</h2>
        <span class="pct-badge"><?= round(KURTAZ_INNE_UPRAWY * 100, 2) ?>%</span>
      </div>
      <div class="fields">
        <div class="field">
          <label for="inne_klient">Składka płatna przez klienta</label>
          <div class="input-wrap">
            <input type="text" inputmode="decimal" id="inne_klient" name="inne_klient"
              value="<?= val('inne_klient') ?>" placeholder="0,00"
              class="<?= isset($errors['inne_klient']) ? 'err' : '' ?>">
            <span class="unit">zł</span>
          </div>
          <?php if (isset($errors['inne_klient'])): ?>
            <div class="error-msg"><?= $errors['inne_klient'] ?></div>
          <?php endif; ?>
        </div>
        <div class="field">
          <label for="inne_panstwo">Składka płatna przez państwo</label>
          <div class="input-wrap">
            <input type="text" inputmode="decimal" id="inne_panstwo" name="inne_panstwo"
              value="<?= val('inne_panstwo') ?>" placeholder="0,00"
              class="<?= isset($errors['inne_panstwo']) ? 'err' : '' ?>">
            <span class="unit">zł</span>
          </div>
          <?php if (isset($errors['inne_panstwo'])): ?>
            <div class="error-msg"><?= $errors['inne_panstwo'] ?></div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div class="btn-row">
      <button type="submit">Oblicz kurtaż</button>
      <button type="reset" onclick="var r=document.getElementById('wyniki'); if(r) r.remove();">Wyczyść</button>
    </div>

  </form>

  <!-- WYNIKI -->
  <?php if ($wyniki): ?>
  <div class="results" id="wyniki" style="margin-top:2rem;">
    <div class="results-header">
      <h3>Wyniki obliczeń</h3>
    </div>

    <div class="results-grid">
      <div class="res-block">
        <div class="res-label">Składka całkowita — Rzepak</div>
        <div class="res-val"><?= fmt($wyniki['skladka_rzepak']) ?></div>
      </div>
      <div class="res-block">
        <div class="res-label">Prowizja Rzepak (<?= round(KURTAZ_RZEPAK*100,2) ?>%)</div>
        <div class="res-val"><?= fmt($wyniki['prowizja_rzepak']) ?></div>
      </div>
      <div class="res-block">
        <div class="res-label">Składka całkowita — Inne uprawy</div>
        <div class="res-val"><?= fmt($wyniki['skladka_inne']) ?></div>
      </div>
      <div class="res-block">
        <div class="res-label">Prowizja Inne uprawy (<?= round(KURTAZ_INNE_UPRAWY*100,2) ?>%)</div>
        <div class="res-val"><?= fmt($wyniki['prowizja_inne']) ?></div>
      </div>
      <div class="res-block">
        <div class="res-label">Suma składek łącznie</div>
        <div class="res-val highlight"><?= fmt($wyniki['suma_skladek']) ?></div>
      </div>
      <div class="res-block">
        <div class="res-label">Suma prowizji łącznie</div>
        <div class="res-val highlight"><?= fmt($wyniki['suma_prowizji']) ?></div>
      </div>
      <div class="res-block" style="grid-column: span 2;">
        <div class="res-label">Kurtaż dodatkowy (<?= round(KURTAZ_DODATKOWY*100,2) ?>% łącznej składki)</div>
        <div class="res-val gold"><?= fmt($wyniki['kurtaz_dodatkowy']) ?></div>
      </div>
    </div>

    <div class="kurtaz-banner">
      <div>
        <div class="kb-label">Kurtaż wynikowy</div>
        <div style="font-size:0.78rem;color:var(--text-dim);margin-top:0.2rem;">
          (suma prowizji ÷ suma składek) × 100
        </div>
      </div>
      <div class="kb-val"><?= fmtPct($wyniki['kurtaz_procent']) ?></div>
    </div>
  </div>
  <?php endif; ?>

</div>
</body>
</html>