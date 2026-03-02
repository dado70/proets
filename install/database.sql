-- ProETS - Sistema Gestionale per Associazioni di Promozione Sociale
-- Copyright (C) 2025 ProETS - Scapuzzi Alessandro <dado70@gmail.com>
-- https://www.proets.it
-- License: GNU GPL v3

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";
SET NAMES utf8mb4;

-- =============================================
-- COMPANIES (Multi-azienda)
-- =============================================
CREATE TABLE `companies` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `codice` varchar(20) NOT NULL,
  `ragione_sociale` varchar(255) NOT NULL,
  `forma_giuridica` enum('APS','ODV','ETS','ONLUS','Altro') NOT NULL DEFAULT 'APS',
  `codice_fiscale` varchar(20) DEFAULT NULL,
  `partita_iva` varchar(20) DEFAULT NULL,
  `nr_iscrizione_runts` varchar(50) DEFAULT NULL,
  `indirizzo` varchar(255) DEFAULT NULL,
  `cap` varchar(10) DEFAULT NULL,
  `citta` varchar(100) DEFAULT NULL,
  `provincia` varchar(5) DEFAULT NULL,
  `telefono` varchar(30) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `pec` varchar(150) DEFAULT NULL,
  `sito_web` varchar(150) DEFAULT NULL,
  `logo_path` varchar(255) DEFAULT NULL,
  `esercizio_corrente` year DEFAULT NULL,
  `data_costituzione` date DEFAULT NULL,
  `note` text DEFAULT NULL,
  `attivo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_codice` (`codice`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- USERS (Multi-utente)
-- =============================================
CREATE TABLE `users` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `cognome` varchar(100) NOT NULL,
  `ruolo` enum('superadmin','admin','operator','readonly') NOT NULL DEFAULT 'operator',
  `attivo` tinyint(1) NOT NULL DEFAULT 1,
  `email_verificata` tinyint(1) NOT NULL DEFAULT 0,
  `token_verifica` varchar(100) DEFAULT NULL,
  `ultimo_accesso` datetime DEFAULT NULL,
  `ip_ultimo_accesso` varchar(45) DEFAULT NULL,
  `tentativi_login` tinyint UNSIGNED NOT NULL DEFAULT 0,
  `bloccato_fino` datetime DEFAULT NULL,
  `preferenze` json DEFAULT NULL,
  `gdpr_consenso` tinyint(1) NOT NULL DEFAULT 0,
  `gdpr_data` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_username` (`username`),
  UNIQUE KEY `uk_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- USER - COMPANY (Accessi aziende per utente)
-- =============================================
CREATE TABLE `user_companies` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int UNSIGNED NOT NULL,
  `company_id` int UNSIGNED NOT NULL,
  `ruolo_azienda` enum('admin','operator','readonly') NOT NULL DEFAULT 'operator',
  `attivo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_company` (`user_id`,`company_id`),
  KEY `fk_uc_company` (`company_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- PASSWORD RESETS (GDPR compliant - TTL 1h)
-- =============================================
CREATE TABLE `password_resets` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `email` varchar(150) NOT NULL,
  `token` varchar(100) NOT NULL,
  `expires_at` datetime NOT NULL,
  `usato` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_token` (`token`),
  KEY `idx_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- AUDIT LOG (GDPR - traccia accessi e modifiche)
-- =============================================
CREATE TABLE `audit_log` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int UNSIGNED DEFAULT NULL,
  `company_id` int UNSIGNED DEFAULT NULL,
  `azione` varchar(100) NOT NULL,
  `tabella` varchar(100) DEFAULT NULL,
  `record_id` int UNSIGNED DEFAULT NULL,
  `dati_precedenti` json DEFAULT NULL,
  `dati_nuovi` json DEFAULT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `user_agent` varchar(500) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_company` (`company_id`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- CONTI (Cassa, Banca, PayPal, etc.)
-- =============================================
CREATE TABLE `accounts` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id` int UNSIGNED NOT NULL,
  `codice` varchar(20) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `tipo` enum('cassa','banca','paypal','stripe','carta_credito','altro') NOT NULL DEFAULT 'cassa',
  `iban` varchar(34) DEFAULT NULL,
  `banca` varchar(100) DEFAULT NULL,
  `saldo_iniziale` decimal(15,2) NOT NULL DEFAULT '0.00',
  `data_saldo_iniziale` date DEFAULT NULL,
  `valuta` char(3) NOT NULL DEFAULT 'EUR',
  `attivo` tinyint(1) NOT NULL DEFAULT 1,
  `note` text DEFAULT NULL,
  `ordine` int UNSIGNED NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_company_codice` (`company_id`,`codice`),
  KEY `fk_acc_company` (`company_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- CAUSALI (Fisse da modello ETS)
-- =============================================
CREATE TABLE `causali` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id` int UNSIGNED DEFAULT NULL COMMENT 'NULL = causale di sistema',
  `codice_numero` varchar(10) NOT NULL,
  `descrizione` varchar(255) NOT NULL,
  `codice_bilancio` varchar(10) NOT NULL,
  `voce_bilancio` text NOT NULL,
  `tipo` enum('entrata','uscita','giroconto','figurativo','imposta') NOT NULL,
  `sezione` enum('A','B','C','D','E','INV','DIS','FIG','IMP','GC') DEFAULT NULL,
  `area_gestionale` enum('interesse_generale','attivita_diverse','raccolta_fondi','finanziaria_patrimoniale','supporto_generale','investimenti','disinvestimenti','figurativo','imposta','giroconto') DEFAULT NULL,
  `attivo` tinyint(1) NOT NULL DEFAULT 1,
  `ordine` int UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_codice_numero` (`codice_numero`,`company_id`),
  KEY `fk_caus_company` (`company_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- PRIMA NOTA
-- =============================================
CREATE TABLE `prima_nota` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id` int UNSIGNED NOT NULL,
  `account_id` int UNSIGNED NOT NULL,
  `causale_id` int UNSIGNED NOT NULL,
  `data_movimento` date NOT NULL,
  `data_valuta` date DEFAULT NULL,
  `descrizione` varchar(500) NOT NULL,
  `importo` decimal(15,2) NOT NULL,
  `tipo` enum('entrata','uscita') NOT NULL,
  `numero_documento` varchar(50) DEFAULT NULL,
  `fornitore_beneficiario` varchar(255) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `riconciliata` tinyint(1) NOT NULL DEFAULT 0,
  `data_riconciliazione` date DEFAULT NULL,
  `riferimento_bancario` varchar(100) DEFAULT NULL,
  `id_transazione_esterno` varchar(100) DEFAULT NULL COMMENT 'PayPal/Stripe transaction ID',
  `fonte_import` enum('manuale','csv','xls','paypal','stripe','bancario') NOT NULL DEFAULT 'manuale',
  `esercizio` year NOT NULL,
  `annullato` tinyint(1) NOT NULL DEFAULT 0,
  `user_id` int UNSIGNED NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_company_data` (`company_id`,`data_movimento`),
  KEY `idx_account` (`account_id`),
  KEY `idx_causale` (`causale_id`),
  KEY `idx_esercizio` (`esercizio`),
  KEY `fk_pn_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- BUDGET / PREVENTIVO
-- =============================================
CREATE TABLE `budget` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id` int UNSIGNED NOT NULL,
  `esercizio` year NOT NULL,
  `nome` varchar(150) NOT NULL DEFAULT 'Preventivo',
  `stato` enum('bozza','approvato') NOT NULL DEFAULT 'bozza',
  `data_approvazione` date DEFAULT NULL,
  `note` text DEFAULT NULL,
  `created_by` int UNSIGNED NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_company_esercizio` (`company_id`,`esercizio`),
  KEY `fk_budget_company` (`company_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `budget_voci` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `budget_id` int UNSIGNED NOT NULL,
  `causale_id` int UNSIGNED NOT NULL,
  `importo_preventivo` decimal(15,2) NOT NULL DEFAULT '0.00',
  `note` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_budget_causale` (`budget_id`,`causale_id`),
  KEY `fk_bv_causale` (`causale_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- SOCI
-- =============================================
CREATE TABLE `soci` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id` int UNSIGNED NOT NULL,
  `numero_tessera` varchar(30) DEFAULT NULL,
  `cognome` varchar(100) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `codice_fiscale` varchar(16) DEFAULT NULL,
  `data_nascita` date DEFAULT NULL,
  `luogo_nascita` varchar(100) DEFAULT NULL,
  `sesso` enum('M','F','Altro') DEFAULT NULL,
  `indirizzo` varchar(255) DEFAULT NULL,
  `cap` varchar(10) DEFAULT NULL,
  `citta` varchar(100) DEFAULT NULL,
  `provincia` varchar(5) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `telefono` varchar(30) DEFAULT NULL,
  `cellulare` varchar(30) DEFAULT NULL,
  `tipo_socio` enum('ordinario','fondatore','onorario','sostenitore') NOT NULL DEFAULT 'ordinario',
  `data_iscrizione` date NOT NULL,
  `data_cessazione` date DEFAULT NULL,
  `motivo_cessazione` varchar(255) DEFAULT NULL,
  `quota_annuale` decimal(8,2) DEFAULT NULL COMMENT 'Quota personalizzata, NULL = quota standard',
  `modalita_pagamento_pref` enum('contanti','bonifico','paypal','stripe','altro') DEFAULT NULL,
  `iban` varchar(34) DEFAULT NULL,
  `note` text DEFAULT NULL,
  -- GDPR
  `privacy_consenso` tinyint(1) NOT NULL DEFAULT 0,
  `privacy_data` datetime DEFAULT NULL,
  `marketing_consenso` tinyint(1) NOT NULL DEFAULT 0,
  `newsletter_consenso` tinyint(1) NOT NULL DEFAULT 0,
  `attivo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_company` (`company_id`),
  KEY `idx_tessera` (`numero_tessera`),
  KEY `idx_cf` (`codice_fiscale`),
  KEY `idx_attivo` (`attivo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- QUOTE ASSOCIATIVE
-- =============================================
CREATE TABLE `quote_socio` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id` int UNSIGNED NOT NULL,
  `socio_id` int UNSIGNED NOT NULL,
  `anno` year NOT NULL,
  `importo_dovuto` decimal(8,2) NOT NULL,
  `importo_versato` decimal(8,2) NOT NULL DEFAULT '0.00',
  `data_versamento` date DEFAULT NULL,
  `prima_nota_id` bigint UNSIGNED DEFAULT NULL COMMENT 'Collegamento con prima nota',
  `metodo_pagamento` enum('contanti','bonifico','paypal','stripe','altro') DEFAULT NULL,
  `riferimento_pagamento` varchar(100) DEFAULT NULL,
  `stato` enum('attesa','parziale','pagata','esonerata') NOT NULL DEFAULT 'attesa',
  `token_pagamento` varchar(100) DEFAULT NULL COMMENT 'Token per link pagamento',
  `token_scadenza` datetime DEFAULT NULL,
  `note` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_socio_anno` (`socio_id`,`anno`),
  KEY `idx_company_anno` (`company_id`,`anno`),
  KEY `fk_qs_pn` (`prima_nota_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- COMUNICAZIONI MASSIVE
-- =============================================
CREATE TABLE `comunicazioni` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id` int UNSIGNED NOT NULL,
  `oggetto` varchar(255) NOT NULL,
  `corpo` text NOT NULL,
  `tipo` enum('richiesta_quota','newsletter','iscrizione','avviso','altro') NOT NULL DEFAULT 'newsletter',
  `destinatari_filtro` json DEFAULT NULL,
  `totale_destinatari` int UNSIGNED NOT NULL DEFAULT 0,
  `inviati` int UNSIGNED NOT NULL DEFAULT 0,
  `errori` int UNSIGNED NOT NULL DEFAULT 0,
  `stato` enum('bozza','in_corso','completata','errore') NOT NULL DEFAULT 'bozza',
  `data_invio` datetime DEFAULT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_company` (`company_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- QUOTE ANNUALI (Tariffe standard per anno)
-- =============================================
CREATE TABLE `quote_annuali` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id` int UNSIGNED NOT NULL,
  `anno` year NOT NULL,
  `tipo_socio` enum('ordinario','fondatore','onorario','sostenitore') NOT NULL DEFAULT 'ordinario',
  `importo` decimal(8,2) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_company_anno_tipo` (`company_id`,`anno`,`tipo_socio`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- CONFIGURAZIONE BACKUP
-- =============================================
CREATE TABLE `backup_configs` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id` int UNSIGNED DEFAULT NULL COMMENT 'NULL = globale',
  `tipo` enum('locale','ftp','sftp','smb','webdav','googledrive','dropbox','nextcloud') NOT NULL,
  `nome` varchar(100) NOT NULL,
  `attivo` tinyint(1) NOT NULL DEFAULT 1,
  `configurazione` json NOT NULL COMMENT 'Config specifica per tipo (host, user, pass cifrata, path)',
  `frequenza` enum('giornaliero','settimanale','mensile') NOT NULL DEFAULT 'giornaliero',
  `ora_esecuzione` time NOT NULL DEFAULT '02:00:00',
  `rotazione_giorni` tinyint UNSIGNED NOT NULL DEFAULT 7,
  `ultimo_backup` datetime DEFAULT NULL,
  `ultimo_stato` enum('ok','errore','mai_eseguito') NOT NULL DEFAULT 'mai_eseguito',
  `ultimo_messaggio` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_company` (`company_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- STORICO BACKUP
-- =============================================
CREATE TABLE `backup_history` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `config_id` int UNSIGNED NOT NULL,
  `filename` varchar(255) NOT NULL,
  `dimensione` bigint UNSIGNED DEFAULT NULL,
  `checksum_md5` varchar(32) DEFAULT NULL,
  `stato` enum('ok','errore') NOT NULL,
  `messaggio` text DEFAULT NULL,
  `percorso_remoto` varchar(500) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_bh_config` (`config_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- IMPOSTAZIONI APP
-- =============================================
CREATE TABLE `app_settings` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id` int UNSIGNED DEFAULT NULL,
  `chiave` varchar(100) NOT NULL,
  `valore` text DEFAULT NULL,
  `tipo` enum('string','int','bool','json','encrypted') NOT NULL DEFAULT 'string',
  `gruppo` varchar(50) NOT NULL DEFAULT 'generale',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_company_chiave` (`company_id`,`chiave`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- TEMPLATE EMAIL
-- =============================================
CREATE TABLE `email_templates` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id` int UNSIGNED DEFAULT NULL,
  `codice` varchar(50) NOT NULL,
  `nome` varchar(150) NOT NULL,
  `oggetto` varchar(255) NOT NULL,
  `corpo_html` text NOT NULL,
  `variabili` json DEFAULT NULL COMMENT 'Lista variabili disponibili',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_company_codice` (`company_id`,`codice`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- RICONCILIAZIONE BANCARIA
-- =============================================
CREATE TABLE `riconciliazione` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id` int UNSIGNED NOT NULL,
  `account_id` int UNSIGNED NOT NULL,
  `data_riconciliazione` date NOT NULL,
  `saldo_estratto_conto` decimal(15,2) NOT NULL,
  `saldo_calcolato` decimal(15,2) NOT NULL,
  `differenza` decimal(15,2) GENERATED ALWAYS AS (`saldo_estratto_conto` - `saldo_calcolato`) STORED,
  `note` text DEFAULT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_company_account` (`company_id`,`account_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- FOREIGN KEYS
-- =============================================
ALTER TABLE `user_companies`
  ADD CONSTRAINT `fk_uc_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_uc_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE;

ALTER TABLE `accounts`
  ADD CONSTRAINT `fk_acc_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE;

ALTER TABLE `causali`
  ADD CONSTRAINT `fk_caus_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE;

ALTER TABLE `prima_nota`
  ADD CONSTRAINT `fk_pn_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_pn_account` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`),
  ADD CONSTRAINT `fk_pn_causale` FOREIGN KEY (`causale_id`) REFERENCES `causali` (`id`),
  ADD CONSTRAINT `fk_pn_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

ALTER TABLE `budget`
  ADD CONSTRAINT `fk_budget_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_budget_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

ALTER TABLE `budget_voci`
  ADD CONSTRAINT `fk_bv_budget` FOREIGN KEY (`budget_id`) REFERENCES `budget` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_bv_causale` FOREIGN KEY (`causale_id`) REFERENCES `causali` (`id`);

ALTER TABLE `soci`
  ADD CONSTRAINT `fk_soci_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE;

ALTER TABLE `quote_socio`
  ADD CONSTRAINT `fk_qs_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_qs_socio` FOREIGN KEY (`socio_id`) REFERENCES `soci` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_qs_pn` FOREIGN KEY (`prima_nota_id`) REFERENCES `prima_nota` (`id`) ON DELETE SET NULL;

ALTER TABLE `backup_history`
  ADD CONSTRAINT `fk_bh_config` FOREIGN KEY (`config_id`) REFERENCES `backup_configs` (`id`) ON DELETE CASCADE;

ALTER TABLE `riconciliazione`
  ADD CONSTRAINT `fk_ric_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ric_account` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`);

-- =============================================
-- CAUSALI DI SISTEMA (da modello ETS)
-- =============================================
INSERT INTO `causali` (`company_id`,`codice_numero`,`descrizione`,`codice_bilancio`,`voce_bilancio`,`tipo`,`sezione`,`area_gestionale`,`ordine`) VALUES
-- ENTRATE - Area A: Interesse Generale
(NULL,'1','INCASSO QUOTE ASSOCIATIVE','EA1','Entrate da quote associative e apporti dei fondatori','entrata','A','interesse_generale',10),
(NULL,'2','INCASSO QUOTE MUTUALI DA ASSOCIATI','EA2','Entrate dagli associati per attività mutuali','entrata','A','interesse_generale',20),
(NULL,'3','INCASSI PER PRESTAZIONI A ASSOCIATI','EA3','Entrate per prestazioni e cessioni ad associati e fondatori','entrata','A','interesse_generale',30),
(NULL,'4','INCASSO EROGAZIONI LIBERALI','EA4','Erogazioni liberali','entrata','A','interesse_generale',40),
(NULL,'5','INCASSO DA 5 X MILLE','EA5','Entrate del 5 per mille','entrata','A','interesse_generale',50),
(NULL,'6','INC. CONTRIBUTI DA PRIVATI','EA6','Contributi da soggetti privati','entrata','A','interesse_generale',60),
(NULL,'7','INC.PER PRESTAZIONI/CESSIONI A TERZI','EA7','Entrate per prestazioni e cessioni a terzi','entrata','A','interesse_generale',70),
(NULL,'8','INC. CONTRIBUTI DA ENTI PUBBL.','EA8','Contributi da enti pubblici','entrata','A','interesse_generale',80),
(NULL,'9','INCASSI DA CONTRATTI CON ENTI PUBBLICI','EA9','Entrate da contratti con enti pubblici','entrata','A','interesse_generale',90),
(NULL,'10','ALTRI INCASSI DA ATT. DI INT GEN','EA10','Altre entrate','entrata','A','interesse_generale',100),
-- ENTRATE - Area B: Attività Diverse
(NULL,'11','INC. PREST/CESSIONI COMMLI ASSOCIATI','EB1','Entrate per prestazioni e cessioni ad associati e fondatori','entrata','B','attivita_diverse',110),
(NULL,'12','INC.CONTR DA PRIVATI COMM.LI','EB2','Contributi da soggetti privati','entrata','B','attivita_diverse',120),
(NULL,'13','INC. PREST/CESSIONI COMM.LI A TERZI','EB3','Entrate per prestazioni e cessioni a terzi','entrata','B','attivita_diverse',130),
(NULL,'14','INC. CONTR. ENTI PUBBLICI (Com)','EB4','Contributi da enti pubblici','entrata','B','attivita_diverse',140),
(NULL,'15','INC. CONTR, COMM ENTI PUBBL.','EB5','Entrate da contratti con enti pubblici','entrata','B','attivita_diverse',150),
(NULL,'16','INC ALTRE PROV COMM.LI','EB6','Altre entrate','entrata','B','attivita_diverse',160),
-- ENTRATE - Area C: Raccolta Fondi
(NULL,'17','INC. RACCOLTE FONDI ABITUALI','EC1','Entrate da raccolte fondi abituali','entrata','C','raccolta_fondi',170),
(NULL,'18','INC. RACCOLTE FONDI OCC.LI','EC2','Entrate da raccolte fondi occasionali','entrata','C','raccolta_fondi',180),
(NULL,'19','INC. ALTRE ENTRATE','EC3','Altre entrate','entrata','C','raccolta_fondi',190),
-- ENTRATE - Area D: Finanziaria e Patrimoniale
(NULL,'20','INC. DA RAPPORTI BANCARI','ED1','Da rapporti bancari','entrata','D','finanziaria_patrimoniale',200),
(NULL,'21','INC. DA INVESTIMENTI FINANZIARI','ED2','Da altri investimenti finanziari','entrata','D','finanziaria_patrimoniale',210),
(NULL,'22','INC DA PATRIMONIO EDILIZIO','ED3','Da patrimonio edilizio','entrata','D','finanziaria_patrimoniale',220),
(NULL,'23','INC. DA ALTRI BENI PATRIMONIALI','ED4','Da altri beni patrimoniali','entrata','D','finanziaria_patrimoniale',230),
(NULL,'24','ALTRI INC.DA BENI PATRIMONIALI','ED5','Altre entrate','entrata','D','finanziaria_patrimoniale',240),
-- ENTRATE - Area E: Supporto Generale
(NULL,'25','INC. DA DISTACCO PERSONALE','EE1','Entrate da distacco del personale','entrata','E','supporto_generale',250),
(NULL,'26','ALTRI INCASSI DA ATTIVITA DI SUPP.GENERALE','EE2','Altre entrate di supporto generale','entrata','E','supporto_generale',260),
-- ENTRATE - Disinvestimenti
(NULL,'27','INC. DA DISINVEST. ISTITUZ.LI','DIS1','Disinvestimenti di immobilizzazioni inerenti alle attività di interesse generale','entrata','DIS','disinvestimenti',270),
(NULL,'28','INC. DA DISINVEST, ATTIVITA DIVERSE','DIS2','Disinvestimenti di immobilizzazioni inerenti alle attività diverse','entrata','DIS','disinvestimenti',280),
(NULL,'29','INC. DA DISINVEST. ATT. FINANZIARIE','DIS3','Disinvestimenti di attività finanziarie e patrimoniali','entrata','DIS','disinvestimenti',290),
(NULL,'30','INC. DA FINANZIAMENTI/PRESTITI OTTENUTI','DIS4','Ricevimento di finanziamenti e di prestiti','entrata','DIS','disinvestimenti',300),
-- USCITE - Area A: Interesse Generale
(NULL,'101','PAGAM. ACQUISTI MP/MERCI/MDC ISTIT.LI','UA1','Materie prime, sussidiarie, di consumo e di merci','uscita','A','interesse_generale',310),
(NULL,'102','PAGAM. ACQUISTO SERVIZI ISTIT.LI','UA2','Servizi','uscita','A','interesse_generale',320),
(NULL,'103','PAGAM. SPESE PER GOD. BENI DI TERZI ISTIT.LI','UA3','Godimento beni di terzi','uscita','A','interesse_generale',330),
(NULL,'104','PAGAM. SPESE PER IL PERSONALE ISTIT.LI','UA4','Personale','uscita','A','interesse_generale',340),
(NULL,'105','PAGAM. SPESE DIVERSE DI GESTIONE ISTIT.LI','UA5','Uscite diverse di gestione','uscita','A','interesse_generale',350),
-- USCITE - Area B: Attività Diverse
(NULL,'106','PAGAM. ACQ. MP/MERCI/MDC COMM.LI','UB1','Materie prime, sussidiarie, di consumo e di merci','uscita','B','attivita_diverse',360),
(NULL,'107','PAGAM. ACQUISTO SERVIZI COMM.LI','UB2','Servizi','uscita','B','attivita_diverse',370),
(NULL,'108','PAGAM. SPESE GOD BENI DI TERZI COMM.LI','UB3','Godimento beni di terzi','uscita','B','attivita_diverse',380),
(NULL,'109','PAGAM. SPESE PERSONALE COMM.LI','UB4','Personale','uscita','B','attivita_diverse',390),
(NULL,'110','PAGAM. SPESE DIVERSE DI GESTIONE COMM.LI','UB5','Uscite diverse di gestione','uscita','B','attivita_diverse',400),
-- USCITE - Area C: Raccolta Fondi
(NULL,'111','PAGAM. SPESE PER RACC FONDI ABITUALI','UC1','Uscite per raccolte fondi abituali','uscita','C','raccolta_fondi',410),
(NULL,'112','PAGAM. SPESE PER RACC FONDI OCCASIONALI','UC2','Uscite per raccolte fondi occasionali','uscita','C','raccolta_fondi',420),
(NULL,'113','PAGAM. ALTRE SPESE PER RACC.FONDI','UC3','Altre uscite','uscita','C','raccolta_fondi',430),
-- USCITE - Area D: Finanziaria e Patrimoniale
(NULL,'114','PAGAM. SPESE BANCARIE/INTERESSI','UD1','Su rapporti bancari','uscita','D','finanziaria_patrimoniale',440),
(NULL,'115','PAGAM. SPESE SU INV. FINANZIARI','UD2','Su investimenti finanziari','uscita','D','finanziaria_patrimoniale',450),
(NULL,'116','PAGAM. SPESE SU INV. IMMOBILIARI','UD3','Su patrimonio edilizio','uscita','D','finanziaria_patrimoniale',460),
(NULL,'117','PAGAM. SPESE SU INV. ALTRI BENI','UD4','Su altri beni patrimoniali','uscita','D','finanziaria_patrimoniale',470),
(NULL,'118','PAGAM. SPESE SU INV. ALTRI INVESTIMENTI','UD5','Altre uscite','uscita','D','finanziaria_patrimoniale',480),
-- USCITE - Area E: Supporto Generale
(NULL,'119','PAG. ACQ. MP/MERCI/MDC PROMISCUI','UE1','Materie prime, sussidiarie, di consumo e di merci','uscita','E','supporto_generale',490),
(NULL,'120','PAGAM. ACQ. SERVIZI PROMISCUI','UE2','Servizi','uscita','E','supporto_generale',500),
(NULL,'121','PAGAM. PER GOD. BENI DI TERZI PROMISCUI','UE3','Godimento beni di terzi','uscita','E','supporto_generale',510),
(NULL,'122','PAGAM. SPESE PERSONALE PROMISCUI','UE4','Personale','uscita','E','supporto_generale',520),
(NULL,'123','PAGAM. ALTRE SPESE PROMISCUE','UE5','Altre uscite','uscita','E','supporto_generale',530),
-- USCITE - Investimenti
(NULL,'124','PAG. PER INV. IMM.LI IST.LI','INV1','Investimenti in immobilizzazioni inerenti alle attività di interesse generale','uscita','INV','investimenti',540),
(NULL,'125','PAG. PER INV. IMM.LI COMM.LI','INV2','Investimenti in immobilizzazioni inerenti alle attività diverse','uscita','INV','investimenti',550),
(NULL,'126','PAG. PER INV. ATT. FIN E PATR.LI','INV3','Investimenti in attività finanziarie e patrimoniali','uscita','INV','investimenti',560),
(NULL,'127','PAG. PER RIMB. QUOTE CAP. FINANZ.TI','INV4','Rimborso di finanziamenti per quota capitale e di prestiti','uscita','INV','investimenti',570),
-- FIGURATIVI
(NULL,'31','PROV FIGURATIVI ATT. INT. GENERALE','PF1','Proventi figurativi da attività di interesse generale','figurativo','FIG','figurativo',580),
(NULL,'32','PROV. FIGURATIVI ATT. DIVERSE','PF2','Proventi figurativi da attività diverse','figurativo','FIG','figurativo',590),
(NULL,'128','COSTI FIGURATIVI ATT. INT. GENERALE','CF1','Costi figurativi da attività di interesse generale','figurativo','FIG','figurativo',600),
(NULL,'129','COSTI FIGURATIVI ATT. DIVERSE','CF2','Costi figurativi da attività diverse','figurativo','FIG','figurativo',610),
-- SPECIALI
(NULL,'99','GIROCONTO','GC','Giroconto tra conti interni','giroconto','GC','giroconto',620),
(NULL,'200','IMPOSTE (IRES/IRAP)','IMP','Imposte','imposta','IMP','imposta',630),
(NULL,'201','IMPOSTE PATRIMONIALI','IPA','Imposte patrimoniali','imposta','IMP','imposta',640);

-- =============================================
-- TEMPLATE EMAIL DI SISTEMA
-- =============================================
INSERT INTO `email_templates` (`company_id`,`codice`,`nome`,`oggetto`,`corpo_html`,`variabili`) VALUES
(NULL,'reset_password','Reset Password','Reimposta la tua password - {{nome_associazione}}',
'<p>Gentile {{nome_utente}},</p><p>Hai richiesto il reset della password per il tuo account.</p><p>Clicca sul link seguente per reimpostare la password (valido 1 ora):</p><p><a href="{{link_reset}}">{{link_reset}}</a></p><p>Se non hai richiesto il reset, ignora questa email.</p><p>{{nome_associazione}}</p>',
'["nome_utente","nome_associazione","link_reset"]'),
(NULL,'richiesta_quota','Richiesta Pagamento Quota Associativa','Rinnovo quota associativa {{anno}} - {{nome_associazione}}',
'<p>Gentile {{nome_socio}},</p><p>Ti informiamo che è in scadenza/scaduta la quota associativa per l''anno {{anno}} dell''associazione {{nome_associazione}}.</p><p>Importo quota: <strong>€ {{importo}}</strong></p><p>Per effettuare il pagamento puoi utilizzare il seguente link sicuro:</p><p><a href="{{link_pagamento}}">Paga ora</a></p><p>Per bonifico bancario:<br>Intestatario: {{nome_associazione}}<br>IBAN: {{iban_associazione}}</p><p>Causale: Quota associativa {{anno}} - {{nome_socio}}</p><p>Distinti saluti,<br>{{nome_associazione}}</p>',
'["nome_socio","anno","importo","link_pagamento","nome_associazione","iban_associazione"]'),
(NULL,'lettera_iscrizione','Lettera di Benvenuto Socio','Benvenuto in {{nome_associazione}} - Conferma iscrizione',
'<p>Gentile {{nome_socio}},</p><p>Siamo lieti di confermare la Sua iscrizione all''associazione <strong>{{nome_associazione}}</strong> in qualità di socio {{tipo_socio}}.</p><p><strong>Dati iscrizione:</strong><br>Numero tessera: {{numero_tessera}}<br>Data iscrizione: {{data_iscrizione}}<br>Quota anno {{anno}}: € {{importo_quota}}</p><p>Informativa Privacy ex art. 13 GDPR:<br>I Suoi dati personali sono trattati dall''associazione {{nome_associazione}} per la gestione del rapporto associativo. Il titolare del trattamento è {{nome_associazione}} ({{email_associazione}}). Ha diritto di accesso, rettifica, cancellazione, portabilità e opposizione al trattamento.</p><p>Distinti saluti,<br>{{nome_associazione}}</p>',
'["nome_socio","numero_tessera","tipo_socio","data_iscrizione","anno","importo_quota","nome_associazione","email_associazione"]');

-- =============================================
-- MIGRAZIONI DB
-- =============================================
CREATE TABLE `db_migrations` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `migration` varchar(100) NOT NULL,
  `version` varchar(20) NOT NULL DEFAULT '',
  `description` varchar(255) NOT NULL DEFAULT '',
  `applied_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_migration` (`migration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- IMPOSTAZIONI DI DEFAULT
-- =============================================
INSERT INTO `app_settings` (`company_id`,`chiave`,`valore`,`tipo`,`gruppo`) VALUES
(NULL,'app_version','1.0.0','string','sistema'),
(NULL,'app_name','ProETS','string','sistema'),
(NULL,'timezone','Europe/Rome','string','sistema'),
(NULL,'date_format','d/m/Y','string','sistema'),
(NULL,'currency','EUR','string','sistema'),
(NULL,'smtp_host','','string','email'),
(NULL,'smtp_port','587','int','email'),
(NULL,'smtp_user','','string','email'),
(NULL,'smtp_pass','','encrypted','email'),
(NULL,'smtp_secure','tls','string','email'),
(NULL,'smtp_from','','string','email'),
(NULL,'smtp_from_name','ProETS','string','email'),
(NULL,'gdpr_dpo_email','','string','gdpr'),
(NULL,'gdpr_data_retention_years','10','int','gdpr'),
(NULL,'backup_local_path','backups/','string','backup'),
(NULL,'backup_max_local','7','int','backup');
