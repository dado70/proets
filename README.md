# ProETS — Gestionale per Associazioni di Promozione Sociale

![Versione](https://img.shields.io/badge/versione-1.0.0--beta-orange)
![Stato](https://img.shields.io/badge/stato-beta-yellow)
![Licenza](https://img.shields.io/badge/licenza-GPL%20v3-blue)
![PHP](https://img.shields.io/badge/PHP-8.1%2B-777BB4)

> ⚠️ **Versione Beta** — Il software è funzionante ma in fase di test.
> Si consiglia di non utilizzarlo in produzione con dati reali senza aver eseguito un backup preventivo.
> Segnala bug e suggerimenti aprendo una [Issue su GitHub](https://github.com/dado70/proets/issues).

Sistema gestionale open source per **APS / ODV / ETS** italiane.
Sviluppato da [Alessandro Scapuzzi](mailto:dado70@gmail.com) — [www.proets.it](https://www.proets.it)
Licenza: **GNU GPL v3**

---

## Funzionalità

- **Prima Nota** — cassa e banca multipla, saldo progressivo, riconciliazione bancaria
- **Rendiconto ETS** — schema per cassa annuale secondo D.Lgs 117/2017, confronto preventivo/consuntivo, rendiconto sintetico
- **Preventivo / Budget** — creazione, approvazione, scostamenti
- **Gestione Soci** — anagrafica, quote associative, comunicazioni massive via email, tessera PDF
- **Import movimenti** — da file CSV/XLS, estratto conto bancario, PayPal, Stripe
- **Backup** — locale, FTP, WebDAV, Google Drive, Dropbox, Nextcloud con rotazione settimanale e ripristino da interfaccia
- **Multiaziendale e multiutente** — ruoli superadmin / admin / operator / readonly
- **GDPR** — gestione consensi, audit log, data retention configurabile
- **Configurazione** — dati associazione, utenti, SMTP, quote annuali per tipo socio

---

## Requisiti

| Componente | Versione minima |
|---|---|
| PHP | 8.1+ |
| MySQL / MariaDB | 10.4+ |
| Apache | 2.4+ con `mod_rewrite` |
| Estensioni PHP | `pdo`, `pdo_mysql`, `mbstring`, `openssl`, `json`, `zlib` (consigliata), `curl` (consigliata) |

---

## Installazione

### 1. Scarica il codice

**Via Git:**
```bash
git clone https://github.com/dado70/proets.git
```

**Via ZIP:** scarica da GitHub → Code → Download ZIP ed estrai.

---

### 2. Installa le dipendenze PHP (Composer)

Le librerie PHP richieste (TCPDF, PHPMailer, League/CSV, Guzzle) si installano con Composer:

```bash
cd /percorso/del/sito
composer install --no-dev --optimize-autoloader
```

> **Senza accesso SSH?** Scarica il pacchetto di release da [GitHub Releases](https://github.com/dado70/proets/releases): include già la cartella `vendor/` pronta all'uso.

---

### 3. Posiziona i file sul server

Copia **tutto il contenuto** della cartella (inclusa `vendor/`) nella root del web server o nella sottocartella desiderata.

| Scenario | Dove caricare i file | URL risultante |
|---|---|---|
| **Root del dominio** | `public_html/` | `https://www.tuodominio.it/` |
| **Sottocartella** | `public_html/proets/` | `https://www.tuodominio.it/proets/` |
| **Sottodominio** | `public_html/gestionale/` (con subdomain puntato lì) | `https://gestionale.tuodominio.it/` |

Nella directory caricata devono essere presenti almeno:
```
install.php
index.php
.htaccess
src/
templates/
install/
vendor/          ← obbligatorio (da composer install o dal pacchetto release)
```

---

### 4. Prepara il database

#### Hosting condiviso / cPanel / Plesk
Crea database e utente dal pannello di controllo del tuo hosting, quindi annota:
- Host (quasi sempre `localhost`)
- Nome database
- Utente database
- Password

#### Server dedicato / VPS con accesso root MySQL
L'installer crea database e utente automaticamente — ti servirà solo la password root di MariaDB/MySQL.

---

### 5. Esegui l'installer web

Apri nel browser: `https://tuo-dominio/install.php`

**Step 1 — Requisiti di sistema**
Verifica che tutti i requisiti obbligatori siano soddisfatti. Le cartelle `config/`, `uploads/`, `logs/`, `backups/` vengono create automaticamente dall'installer. In caso di errori sulle estensioni PHP, contatta il supporto del tuo hosting.

**Step 2 — Configurazione Database**
Scegli la modalità:
- *Hosting condiviso* → inserisci le credenziali già create al punto 3
- *Server dedicato / VPS* → inserisci le credenziali root per creare DB e utente automaticamente

**Step 3 — Dati Associazione e Amministratore**
Inserisci ragione sociale, forma giuridica, URL dell'applicazione e i dati del primo utente amministratore.

> ⚠️ **L'URL inserito è importante**: determina il percorso base dell'applicazione.
> Esempi corretti: `https://www.tuodominio.it` oppure `https://www.tuodominio.it/proets` oppure `https://gestionale.tuodominio.it`

**Step 4 — Completamento**
L'installer crea automaticamente:
- `config/config.php` con tutte le impostazioni
- `.htaccess` aggiornato per il routing Apache
- Schema del database con 76 causali ETS pre-caricate

Al termine clicca **"Elimina install.php"** per sicurezza.

---

### 5. Configurazione Apache (se non usi cPanel/Plesk)

Verifica che nel VirtualHost sia abilitato `AllowOverride All`:

```apache
<Directory /var/www/html>
    AllowOverride All
</Directory>
```

Abilita `mod_rewrite`:
```bash
sudo a2enmod rewrite
sudo systemctl reload apache2
```

---

## Aggiornamento

```bash
cd /percorso/installazione
git pull origin main
```

> ⚠️ Non sovrascrivere `config/config.php` — contiene le tue credenziali e impostazioni.

---

## Struttura del progetto

```
install.php                  ← Installer web (da eliminare dopo l'installazione)
index.php                    ← Entry point applicazione
.htaccess                    ← Routing Apache (generato anche dall'installer)
composer.json                ← Dipendenze PHP
src/
├── bootstrap.php
├── Core/                    ← Framework MVC (Router, Auth, DB, View, Session…)
├── Controllers/             ← Auth, Dashboard, PrimaNota, Rendiconto, Budget, Soci, Backup, Config
└── Services/                ← Rendiconto, Import, Mailer, Backup, PDF
templates/                   ← Template PHP (Bootstrap 5 + Chart.js via CDN)
install/
└── database.sql             ← Schema DB completo con causali ETS pre-caricate
config/                      ← Generata dall'installer (non in git)
uploads/                     ← Loghi e allegati (non in git)
backups/                     ← Backup locali (non in git)
logs/                        ← Log applicazione (non in git)
vendor/                      ← Dipendenze Composer (non in git)
```

---

## Sicurezza post-installazione

- Elimina `install.php` dalla root (o usa il pulsante nell'installer)
- Verifica che `config/config.php` non sia accessibile via web (il `.htaccess` lo blocca già)
- Configura HTTPS con certificato SSL (Let's Encrypt è gratuito)
- Cambia la password dell'amministratore al primo accesso
- Configura backup automatici dalla sezione **Backup & Ripristino**

---

## Licenza

ProETS è distribuito sotto licenza **GNU General Public License v3.0**.
Vedi il file [LICENSE](LICENSE) o [https://www.gnu.org/licenses/gpl-3.0.html](https://www.gnu.org/licenses/gpl-3.0.html)

---

## Autore

**Alessandro Scapuzzi**
[dado70@gmail.com](mailto:dado70@gmail.com) — [www.proets.it](https://www.proets.it)
