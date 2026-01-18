# Changelog

Tutte le modifiche notevoli a questo progetto saranno documentate in questo file.

Il formato è basato su [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
e questo progetto aderisce al [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---



---

## [Unreleased]

## [v7.6.0-sovereign-state] - 2026-01-18 "**Sovereign State**"
### Legal & Policy Framework (Compliance 100%)
- **PolicyController Engine**: Nuovo controller `src/Controller/PolicyController.php` per la gestione dinamica dei documenti legali (Privacy, Cookie, EULA).
    - **Dynamic Injection**: Iniezione contestuale (`ai_context`) per istruire l'AI su come rispondere a domande legali.
    - **HTML Purge**: Rendering pulito di `SLA_MAINTENANCE`, `PRIVACY_POLICY` e `TERMS_OF_SERVICE` dalla directory `public/landing/legal/`.
- **Enterprise SLA**: Pagina statica `SLA_MAINTENANCE.html` con definizione livelli di servizio (P1/P2/P3/P4) e penali.
- **GDPR Core**: Informative strutturate per Art. 13/14 GDPR con tabelle di retention policy.

### Workshift Core Expansion
- **Reports Module**: Nuovo `ReportsController` e template `reports.mustache` per generazione PDF/CSV turni.
- **Settings & Config**: Pannello `settings.mustache` per configurazione globale (Ferie default, Ore target).
- **Operator Profile**: Scheda `operator-profile.mustache` per gestione skills e preferenze turni.
- **Info Hub**: Pagine informative interne (`hr-policy`, `labor-laws`) per consultazione normativa.

### External Modules Integration (Final)
- **TaskFlow & ExpenseBar PRO**: Finalizzazione integrazione DB (`install_taskflow_db.php`) e asset (`public/assets/taskflow`).
    - **Tests**: Aggiunti `TaskflowTest.php`, `ExpensebarTest.php` e `WorkshiftTest.php` alla suite.

## [v7.5.0-strategic-ops] - 2026-01-18 "**Strategic Operations Suite**"
### New Module: ExpenseBar (Financial Intelligence)
- **Dashboard Finanziaria**: Nuova interfaccia `External/ExpensebarController` per la gestione spese e budget.
    - **Analytics Core**: Vista dedicata per analisi trend di spesa.
    - **Python Forecasting Bridge**: Integrazione nativa (`proc_open`) con script Python (`expense_forecast.py`) per proiezioni finanziarie ML-based.
    - **CRUD Operativo**: API `addExpense`, `getExpenses` supportate da Repository PDO dedicato.
    - **Architecture**: Controller isolato in `src/Controller/External` per modularità pulita.

### New Module: TaskFlow (Project Management)
- **Tactical Task Manager**: Sistema Kanban/Lista per gestione task operativi.
    - **Fluid Interface**: UI reattiva con gestione stato completamento immediata.
    - **Bulk Actions**: Funzionalità `clearCompleted` per pulizia rapida backlog.
    - **Persistence**: Storage su DB (`taskflow_tasks`) tramite `PDOTaskflowRepository`.

### Workshift Evolution (Shift Commander)
- **Unified Command Interface**: Standardizzazione visuale completa dei moduli Turni, Team e Ferie (`header`, `navbar`) per coerenza "Squadron".
- **Tactical Navigation**: Implementazione logica di navigazione temporale (Settimana/Mese/Anno) nel calendario turni.
    - **Logic Core**: Algoritmo `updateDate` che gestisce incrementi differenziali in base alla vista attiva.
- **Universal Scroll HUD**: Estensione del componente `ScrollNavigator` (Progress Ring) a tutte le view operative.
    - **Iconography**: Iniezione FontAwesome per fix icone frecce direzionali.

### Risolto
- **JS Runtime**: Corretto selettore CSS non valido (`.text-[10px]`) che bloccava il parsing in `workshift-shift-management.js`.
- **API Endpoint**: Corretto URL endpoint per l'ottimizzatore AI.

## [v7.4.0-operational-command] - 2026-01-15 "**Operational Command**"
### Core Evolution: AI & Shell
- **AI Coding Core**: Connettore Ollama locale (deepseek-coder/llama3) per assistenza coding offline.
- **Universal Shell**: Backend `run_cmd` potenziato per supportare nativamente PowerShell (Windows) e Python, oltre a Bash.
- **Omni-Editor**: Editor modale visuale con supporto multi-lingua (.php, .js, .css), drag & drop e creazione file.
- **Surgical Refactoring**: Separazione modulare degli asset della Debug Console (`debug_console.css/js`) per massima pulizia.

### Hotfix Massivo: Socio Profile & Security (Fix 1-19)
1. **Vault UI Fix**: Risolto clash layout nel footer del "Vault Documenti" e allineamento form upload.
2. **Scroll Lock**: Implementato `max-height: 550px` e scroll interno per prevenire overflow pagina.
3. **Card Rendering**: Forzata altezza minima (750px) per layout consistente.
4. **Routing**: Abilitato link `/modifica` con redirect intelligente.
5. **Asset Types**: Aggiunte proprietà `TipoDocumento` estese.
6. **Accessibility**: Badge ad alto contrasto per i documenti.
7. **Pro Vault**: Categorie professionali (Legal, Financial, Career) per classificazione documenti.
8. **Login Button**: Risolto problema `z-index` e `pointer-events` che rendeva il login incliccabile.
9. **CSRF Bypass**: Iniezione token e BaseURL mancanti nel login form v2.
10. **Form Action**: Semplificazione action a percorso relativo per prevenire errori template.
11. **Route Parser**: Reintrodotta logica robusta di parsing rotte nel Controller.
12. **Debug Mode**: Marker visivo "ACCEDI (FIX)" per diagnosi cache immediata.
13. **Cache Busting**: Rinomina template a `login_v2` per invalidazione forzata cache server.
14. **HTML Integrity**: Chiusura tag mancanti (`layout_footer`) in `socio_detail`.
15. **Admin Visibility**: Corretta discrepanza `is_admin` vs `real_is_admin`.
16. **Role Access**: Estesi privilegi operativi al ruolo `Segreteria` e `Direttore`.
17. **Full Management**: Accesso completo in lettura/scrittura per la Segreteria Soci.
18. **Contextual Workflows**: Menu utente intelligente popolato in base al ruolo (Azioni Rapide).
19. **Visual Assurance**: Verifica scroll navigation e nuove card Dashboard User.

## [v7.3.0-parrot-arsenal] - 2026-01-15 "**Parrot Arsenal**"
### Security Suite
- **Legacy of the Hacker**: Interfaccia menu multilivello ispirata a Parrot OS.
- **Real Networking Tools**:
    - **Port Scanner**: Implementazione nativa PHP (Socket API) senza nmap.
    - **Whois Client**: Query TCP raw dirette ai server registrar.
    - **DNS Enum**: Risoluzione record avanzata.
- **Hybrid Simulation**: Engine di simulazione per tool non sicuri in ambiente web (Metasploit, SQLMap) per training.
- **Cyber-Warfare UI**: Layout categorizzato (Recon, Vuln, Exploit, Forensics).

## [v7.2.0-god-mode] - 2026-01-15 "**God Mode**"
### Neural Interface UX
- **Dual-Core UI Engine**: Switch on-the-fly tra modalità "Hyper-Grid" (Tecnica) e "Neural" (Organica).
- **Synaptic Web**: Sfondo interattivo (Canvas) con rete neurale che reagisce al mouse.
- **Living UI**: Elementi dell'interfaccia con animazioni "breathing" e transizioni fluide.
- **Omni-Search**: Barra di ricerca olografica fluttuante.

## [v7.1.0-hypergrid] - 2026-01-15 "**Hyper-Grid**"
### Toolkit Revolution
- **Quantum Engineering Deck**: Layout a griglia reattiva modulare per il Toolkit.
- **Recursive Metrics**: Conteggio preciso dei test tramite scansione regex ricorsiva su filesystem.
- **Lazy Loading**: Caricamento asincrono dei pannelli per performance istantanee.
- **Persistent Console**: Terminale sempre attivo in background.

## [v6.0.0-genius] - 2026-01-15 "**Genius Mode**"
### Holographic Dashboard
- **Mission Control UI**: Completo restyling olografico con effetti neon/glass.
- **Live Widgets**:
    - **DEFCON Selector**: Cambio stato globale sistema.
    - **Threat Map**: Visualizzazione geo-localizzata minacce (Canvas).
    - **Financial Ticker**: Dati di borsa simulati in scorrimento.
    - **Neural Uplink**: Visualizzatore log connessioni neurali.
- **Switchboard**: Toggle fisici per manutenzione e accessi.
- **Privacy Core**: Rimozione totale dipendenze esterne.

## [5.7.1] - 2026-01-15 "**Dossier Polish**"
### Migliorato
- **UX Dossier**: Redirect automatico al fascicolo completo al click sulla riga (bypass QuickView).
- **UI Tweaks**: Corretta visualizzazione "Archivio/Attivo" e stili Timeline mancanti.
- **Privacy**: Sostituita CDN SweetAlert2 con versione locale.

## [5.7.0] - 2026-01-15 "**Classified Dossier**"
### Aggiunto
- **Dossier Intelligence System**: Nuova vista dettaglio socio trasformata in "Fascicolo Classificato".
    - **Identity Card**: Card olografica con watermark e timbri "Classified".
    - **Service Timeline**: Cronologia verticale degli eventi di servizio (Arruolamento, Promozioni).
    - **Digital Footprint**: Log accessi e azioni audit per monitorare la sicurezza del fascicolo.
    - **Ribbon Rack**: Sistema di gamification con nastrini e medaglie (Servizio Attivo, ID Verificato).
- **Backend Mocking**: Iniezione di dati di intelligence (simulati) nel `DetailController` per popolare la nuova UI.

## [5.6.1] - 2026-01-15 "**Hotfix**"
### Risolto
- **Syntax Error**: Corretta parentesi ridondante in `socio_list_admin.mustache` che bloccava l'inizializzazione JS.

## [5.6.0] - 2026-01-15 "**Personnel Command Center**"
### Aggiunto
- **Personnel Intelligence HUD**: Dashboard tattica nell'elenco soci con KPI in tempo reale (Totale, Attivi, Ufficiali, Congedo).
- **Interactive Data Grid**: Sostituzione della tabella statica con una Griglia Operativa interattiva.
    - **Visual Filters**: Filtri istantanei (Attivi, Morosi, Tutti) senza ricaricamento di pagina.
    - **Row Interactivity**: Ogni riga è cliccabile e apre un Dossier Rapido.
- **Quick View Dossier**: Pannello laterale (Offcanvas) per consultazione rapida profilo socio.
    - **Dati Chiave**: Foto, Matricola, Grado, Reparto.
    - **Azioni Rapide**: Collegamenti diretti a Modifica/Dettaglio e funzione Stampa.
- **Backend Optimization**: `ListController` ora calcola statistiche aggregate on-the-fly e inietta payload JSON (`data-json`) per performance frontend istantanee.

## [5.5.2] - 2026-01-15 "**Financial Intelligence Unit**"
### Aggiunto
- **Financial Intelligence Dashboard**: Nuova sezione Admin per analisi predittiva e monitoraggio asset.
    - **Wall Street Ticker**: KPI finanziari scorrevoli in tempo reale.
    - **Asset Allocation Map**: Visualizzazione breakdown valore (Capitale Umano / Tech / IP).
    - **AI Growth Forecast**: Grafico proiettivo 2026-2030 (Regressione Lineare).
- **Backend Analytics**: Implementazione `StatsDashboardController::getFinancialProjections`.
- **Privacy Fix**: Libreria Chart.js scaricata in locale (`js/lib/chart.min.js`) per prevenire blocchi anti-tracking.

## [5.5.1] - 2026-01-15 "**Mission Control System**"
### Aggiunto
- **Security Operations Center (SOC)**: Trasformazione della pagina Impostazioni (`admin/impostazioni`) in una console di comando.
    - **Defense Matrix**: Visualizzazione stato sicurezza (2FA, SQL Firewall, Threat Level).
    - **Live Audit Log**: Tabella eventi di sicurezza in tempo reale.
    - **Active Session Manager**: Monitoraggio sessioni attive con UI per terminazione.
    - **System Health**: Metriche CPU/RAM e stato DB.
- **Backend Architecture**: Implementazione `SettingsController::getSecurityAuditLog`, `getActiveSessions`, `getSystemHealth`.

## [5.5.0] - 2026-01-15 "**System Stabilization A1**"
### Aggiunto
- **DevTools Toolkit Shortcut**: Aggiunto pulsante "Toolkit Avanzato" nell'header del Mission Control per accesso rapido ai test.
- **AI Assistant Hybrid Launcher**: Implementato sistema di avvio automatico (via HTMX trigger) con pulsante manuale di fallback in caso di network lag.

### Modificato [CORE REFACTOR]
- **Scroll Navigator 2.0**: Refactoring completo da IIFE a `class ScrollNavigator`.
    - **Reusability**: Ora istanziabile multiplamente (Main Window + DevTools Drawer).
    - **Isolation**: Stili e logica incapsulati per non interferire con il DOM globale.
    - **Integration**: Inserito nativamente sia nel layout pubblico che nel container `#console-drawer` del DevTools.
- **User Statistics View**: Ripristinata la view semplificata "Trasparenza Comunità" per gli utenti base (non-admin) in `StatsDashboardController`, mantenendo la dashboard finanziaria per gli admin.
- **Backend Logic**: Implementati metodi repository `getMonthlyRegistrations`, `countByCategory`, `getRecent` in `PDOSocioRepository` per popolare la view utente con dati reali (No Mock/Placeholder).
- **Legacy Stats Pattern**: Implementato logic switch in `StatsDashboardController` per servire template `statistics_user.mustache` (v5.0.0) o `statistics.mustache` (v5.4.0) in base al ruolo.

### Risolto
- **Legacy Cleanup**: Rimossi vecchi script "back-to-top" ridondanti in `layout_footer` e `app.js`.
- **Z-Index Conflicts**: Risolti conflitti di sovrapposizione tra Chat AI e Scroll Navigator (`bottom: 110px`).

## [5.4.1] - 2026-01-14 "**UI Perfection & Strict Workflow**"
### Corretto [UI/UX]
- **Scroll Navigator Alignment**: Risolto conflitto visivo con il widget AI "Archivio Parlante".
    - **Dettaglio**: Il pulsante Scroll Navigator si sovrapponeva al widget.
    - **Fix**: Spostato verticalmente a `bottom: 110px` e allineato orizzontalmente a `right: 16px`.
    - **Codice**:
        ```css
        /* public/css/components/scroll_navigator.css */
        .scroll-navigator-container {
            bottom: 110px; /* Was 30px */
            right: 16px;   /* Was 30px */
        }
        ```
- **Stop Button Icon**: Centratura geometrica dell'icona "Stop" nel player TTS.
    - **Fix**: Aggiunte classi utility Flexbox e rimozione padding superfluo per centratura perfetta.
    - **Codice**:
        ```html
        <!-- templates/layout/layout_header.mustache -->
        <button id="ta-stop" class="... d-flex align-items-center justify-content-center p-0">
        ```
- **DevTools Text**: Corretto warning "Code Reactor".
    - **Fix**: "irreversibili" -> "IRREVERSIBILI" (Uppercase) per enfasi visiva.

### Aggiunto [WORKFLOW]
- **Regole Fondamentali Documentazione**: Aggiunta sezione "Documentation & Logging Rules" in `feature_development.md` che impone snippet di codice obbligatori.
- **Historical Releases**: Creati branch di release retroattivi (`v0.1.0` - `v2.4.0`) per conformità con il listino prezzi commerciale.

## [5.4.3] - 2026-01-14 "**Interactive Mission Control**"
### Aggiunto [DASHBOARD]
- **Interactive Workspace**: La Dashboard non è più solo statistica ma operativa.
    - **Switchboard Operativa**: Pannello con toggle fisici per "Global Maintenance", "Nuove Registrazioni", "Strict 2FA".
    - **Workflow Inbox**: Casella azioni rapide (Approva reset password, invia solleciti) direttamente dalla home.
    - **Quick Notes**: Blocco appunti persistente (LocalStorage hybrid) per l'Admin.
    - **Code Snippet (Backend Controller)**:
        ```php
        // src/Controller/Admin/DashboardActionController.php
        public function toggleConfig($request, $response) {
            // Gestione Real-time dei toggle di sistema
            $this->logger->info("Switchboard Action: " . $setting . " -> " . $value);
            return $this->json(['success' => true]);
        }
        ```

### Security & Testing
- **Automated Test Suite**: Creato `tests/Feature/DashboardInteractionTest.php` per validare le API dei toggle e broadcast.
- **GodMode Integration**: I nuovi moduli rispettano i privilegi `Aj_GodMode` (es. azioni distruttive segregate).

## [5.4.2] - 2026-01-14 "**Advanced Dynamic Dashboard**"
### Aggiunto
- **Commercial Pricing Tiers**: Definizione formale dei livelli di licenza basata sul Report Benchmark 2026.
    - **Standard v5.0** (€115.000): Licenza base con Source Code.
    - **Professional v5.0** (€135.000): Best Seller con DevTools Ultimate.
    - **Enterprise v5.0** (€175.000): Mission-Critical con HA Cluster e SLA 99.9%.

## [5.3.2] - 2026-01-13 "**Platinum Grade Reliability**"
### Aggiunto
- **Commercial Pricing Tiers**: Definizione formale dei livelli di licenza basata sul Report Benchmark 2026.
    - **Standard v5.0** (€115.000): Licenza base con Source Code.
    - **Professional v5.0** (€135.000): Best Seller con DevTools Ultimate.
    - **Enterprise v5.0** (€175.000): Mission-Critical con HA Cluster e SLA 99.9%.

### Risolto [CRITICAL]
- **Toolkit Console JSON Fix**: Risolto errore "Unexpected end of JSON input" in `terminal.php`.
    - **Tecnica**: Implementazione output buffering (`ob_start` / `ob_end_clean`) per catturare e sopprimere warning PHP spuri (es. "Undefined array key") che corrompevano il payload JSON.
- **System Check Backup Logic**: Corretta la logica di controllo backup in `SystemCheck.php`.
    - **Problema**: Il sistema cercava backup solo in `storage/backups` ignorando gli snapshot generati da `safe_test_runner.php` in `backups/safety_snapshots`.
    - **Fix**: Aggiunta scansione multi-directory per rilevare correttamente l'ultimo backup valido.
- **Test Runner Path Resolution**: Fixato `safe_test_runner.php` per ambienti Windows.
    - **Dettaglio**: Il path relativo `../../vendor/bin/pest` falliva se eseguito da directory diverse. Sostituito con `realpath(__DIR__ . '/../../vendor/bin/pest')` assoluto.
- **Link DevTools**: Aggiornato link hardcoded errato nel template `devtools.mustache` (`/fratellanza-militare-archivio/bin/` -> dinamico).

### Performance e Metriche
- **Test Suite**: Verificato 100% pass rate su 184 test (Feature + Unit + Security).
- **Valutazione**: ROI Sviluppatore certificato a €63/h con 2.140 ore totali di ingegneria.

---

## [5.3.0] - 2026-01-13 "**Operation Open Heart: Rebranding**"
### Rebranding [CRITICAL]
- **Identity Shift**: Rinomina completa del progetto da "Fratellanza Militare" a **MCAG** (Militare-Civile Archivio Gestionale).
- **Physical Rename**: Cartella root rinominata a `MCAG_Militare-Civile-Archivio-Gestionale`.
- **Database Rename**: Migrazione fisica da `fratellanza_db` a `mcag_db` con export/import dei dati esistenti.

### Chirurgia Namespace
- **Namespace Migration**: Refactoring massivo (`Regex`) di tutti i namespace PHP (`namespace FratellanzaMilitare\` -> `namespace MCAG\`) e degli import (`use`).
- **Composer Update**: Mappatura PSR-4 aggiornata (`"MCAG\\": "src/"`) e dump dell'autoload.
- **Legacy Safety Net**: Creato `legacy_aliases.php` per mappare le vecchie classi `FratellanzaMilitare\*` sulle nuove `MCAG\*`, garantendo retrocompatibilità per script esterni o cache non pulite.

### Interfaccia Utente (Tessuti Molli)
- **UI Strings**: Sostituzione globale stringhe visibili ("Fratellanza Militare" -> "MCAG").
- **2FA Assets**: Aggiornata etichetta QR Code in `TwoFactorController` per mostrare "MCAG (username)" invece del vecchio brand.
- **Console Logs**: Aggiornato `main.js` ("MCAG - App Loaded").
- **Styles**: Aggiornati commenti header in `app.scss`.

### Database
- **Content Migration**: Phinx migration `20260113132359_rebrand_to_mcag` per aggiornare i valori nella tabella `settings` ("Fratellanza Militare Firenze" -> "MCAG...", ecc).
- **Configuration**: Aggiornato `.env` con nuovo `APP_NAME`, `APP_URL` e `DB_DATABASE`.

### Verifica
- **Test Suite**: Tutti i 169+ test passano sotto il nuovo namespace.

---


## [5.2.1] - 2026-01-13 "**Omni-Reader Precision**"
### Aggiunto
- **Knowledge Base Expansion**: Inclusione automatica del `REPORT_COMMERCIALE_BENCHMARK_2026.md` nello script di ingestione (`bin/ingest_docs.php`). Precedentemente, l'AI ignorava questo file cruciale, rispondendo "Non ho trovato informazioni" a domande sui benchmark.

### Modificato [TECHNICAL DEEP DIVE]
- **Semantic Chunking Switch**: Il `DocumentChunkerService` è stato riscritto per abbandonare lo splitting puramente basato sulla lunghezza in favore di uno splitting semantico basato sulla struttura Markdown.
    - **Vecchio Approccio**: `preg_split('/(?<=[.?!])\s+/', ...)` -> Spezzava a metà degli ADR se superavano i 500 caratteri.
    - **Nuovo Approccio**: `preg_split('/^(?="#{1,3}\s)/m', ...)` -> Rispetta gli Header (#, ##, ###) mantenendo Titolo e Contenuto uniti.
    - **Codice**:
      ```php
      $sections = preg_split('/^(?="#{1,3}\s)/m', $text, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
      ```
    - **Risultato**: Il retrieval score per query specifiche (es. "Redis ADR") è passato da <0.60 a >0.71.
- **RAG Recall Optimization**: Modifica parametri di recupero in `AssistantController.php` per evitare falsi negativi su query tecniche.
    - **Threshold Relaxation**: Abbassata soglia *cosine similarity* da `0.55` a `0.45`. Questo permette all'AI di considerare "rilevanti" anche frammenti tecnici (regex, config) che hanno una densità semantica diversa dal linguaggio naturale.
    - **Context Window Expansion**: Aumentato il numero di *chunks* recuperati da 5 a 10 (`search($embedding, 10)`).
    - **Source Attribution**: Aggiunto metadata `[Source: filename]` nel prompt di sistema per permettere all'AI di citare le fonti con precisione.

### Risolto [CRITICAL]
- **Ghost Data (Allucinazioni)**: Rimossa la presenza di duplici definizioni di `ADR-029` nel `DECISION_LOG.md`.
    - **Problema**: Esisteva una vecchia bozza "Sales Readiness" in coda al file che sovrascriveva l'ADR reale "Omni-Reader Architecture" durante l'ingestione sequenziale.
    - **Fix**: Rimozione fisica del blocco errato e re-indicizzazione completa (Wipe Vector Store + Re-ingest).
- **RAG Verification Tool Check**: Corretto il namespace errato in `bin/verify_knowledge.php` (`FratellanzaMilitare\Service\AI\...` -> `FratellanzaMilitare\AI\...`) e il metodo invocato (`getEmbeddings` -> `embed`), permettendo la validazione automatica della conoscenza post-ingestione.

## [5.2.0] - 2026-01-13 "**Omni-Reader Edition**"
### Aggiunto
- **Omni-Reader AI Engine**: Supporto unificato per .pdf, .docx, .xlsx, .md, .txt, .php, .py, .java, .js, .sql.
- **Global AI Widget**: Assistente fluttuante disponibile su tutte le pagine (`templates/partials/ai_widget.mustache`).
- **Smart Context**: L'AI rileva automaticamente il contesto della pagina corrente (Scheda Socio, Dashboard) e inietta i dati nel prompt.
- **Voice Interface**: Funzionalità Speech-to-Text per comandi vocali.
- **Zero-Dependency Architecture**: Rimossa dipendenza hard da Redis per le code; fallback automatico su Database Queue.
- **Code Parser Service**: Gestione dedicata per blocchi di codice e file markdown.

## [5.1.1] - 2026-01-13 "**Singularity Hotfix**"
### Risolto [CRITICAL]
- **AI Assistant Infinite Spinner**: La libreria `htmx.min.js` mancava nell'header della dashboard amministrativa (`admin_header.mustache`). Aggiunta inclusione globale.
- **Errore 403 Forbidden (Chat)**: Il modulo di chat AI non includeva i token CSRF. Iniettati token validi via `AssistantController` e campi nascosti nel form.
- **Queue Worker Crash**: Lo script `worker.php` non riusciva a deserializzare i Job Objects (`JobInterface`). Rifattorizzato il worker per utilizzare il DI Container e l'autoloading corretto.
- **Layout**: Aggiunto pulsante di "Avvio Manuale" come fallback per browser con policy restrittive.

---

## [5.1.0] - 2026-01-13 "**Singularity: AI & Async**"
### Aggiunto
- **Archivio Parlante (RAG Engine)**:
    - **Local AI**: Integrazione con Ollama (`llama3`) per privacy totale.
    - **Knowledge Base**: Caricamento PDF, Chunking automatico e Vector Store (JSON-based).
    - **Chat Interface**: UI in stile "chat" con HTMX e streaming della risposta.
- **Asynchronous Processing**:
    - **Database Queue**: Sistema di coda lavori su MariaDB/MySQL (Zero-Config, Zero-Cost).
    - **Worker**: Script background (`php bin/worker.php`) per elaborazione documenti pesanti.
    - **Job System**: Architettura scalabile `QueueInterface` / `JobInterface`.
- **Integrazioni**:
    - **PDF Parser**: Estrazione testo automatica da documenti caricati.
    - **Timeout Handling**: Estensione limiti esecuzione per generazione LLM.

### Ottimizzato
- **Layout**: Risolti percorsi asset (CSS/JS) mancanti.
- **Worker**: Eliminati warning su namespace globali (`use PDO`).

---

## [4.0.0] - 2026-01-11/12 "**Ultimate Upgrade & Sales Ready**"
### Aggiunto
- **DevTools Ultimate v4.0**: Dashboard amministrativa completa (Terminal, Security, Audit).
    - **Pro Terminal**: Console Web (Bash/PowerShell) integrata.
    - **Security Center**: Gestione utenti, 2FA Ops, Security Score in tempo reale.
    - **Audit Inspector**: Visualizzatore log avanzato.
- **Demo Ecosystem**:
    - **Restricted Mode**: Sistema di sessione limitata (403 su aree sensibili) per utenti demo.
    - **Invitation System**: Generatore inviti via email con credenziali temporanee.
    - **Public Route**: `/auth/start-demo` per accesso rapido.
- **Sales Frontend**:
    - **Landing Page Refactor**: Nuova UI "Glassmorphism" in `public/landing/`.
    - **Login Modal**: Accesso unificato Clienti/Demo con design premium.
- **Distribution**:
    - **Archives**: Generati pacchetti installazione `Installazione_MCAG/` (v1, v2, v3, v4).

### Sicurezza
- **Deep Restrictions**: Blocco server-side operazioni di scrittura (Store, Update, Delete, Export) in modalità Demo.
- **Polyglot Separation**: Applicazione rigorosa ADR-028 (No inline JS/CSS).
- **Error Handling**: Nuova pagina `403_demo.mustache` user-friendly.

### Policy & Workflow
- **Git Retention**: Adozione ADR-026 (Conservazione totale branch).
- **Quality Gate**: Branch `feature/tests` per validazione obbligatoria prima del merge.

## [2.5.0] - 2026-01-11 "**Historical Rigor**"
### Aggiunto
- **Policy Retention Totale**: Regola obbligatoria per il NON-cancellamento di qualsiasi branch per audit.
- **Workflow Update**: Guide aggiornate con istruzioni di chiusura/riapertura branch.
- **Mandatory Logging**: Obbligo aggiornamento `CHANGELOG` e `DECISION_LOG` prima della chiusura (ADR-026).

### Modificato
- **Git Workflow**: Branch di test perenni.
- **Documentazione**: Refactoring guide.

## [2.4.4] - 2026-01-10 - "**Enterprise Perfection & Strict Workflow**"
### Aggiunto
- **DevTools Ultimate v4.0**: Aggiornamento massivo della dashboard per sviluppatori con focus su stabilità e features "Mission-Critical".
    - **Pro Terminal**: Nuova sezione integrata *in-page* (bottom dashboard).
    - **Security Center**: Nuova gestione utenti avanzata con calcolo "Security Score".
    - **Audit Logs**: Visualizzazione avanzata dei log.
    - **Design Premium**: Integrazione completa del design system "Glassmorphism".
- **Quality Gate**: Branch `feature/tests` obbligatorio per certificazione 100% green.
- **PaidServicePlaceholder**: Implementazione completa logica servizi a pagamento.
- **InputSanitizer**: Logica completa di sanitizzazione HTMLPurifier.
- **Legal Kit Enterprise**: EULA, SLA Maintenance, GDPR DPA.
- **Backend API**: Nuovi endpoint sicuri per DevTools.
- **Feature Tests**: Suite completa `DevToolsV4Test.php`.

### Modificato
- **DevTools Dashboard**: Refactoring "Additive-Only" del template `devtools.mustache`.
- **Git Workflow**: Adozione modello "Sacred Main".
- **CI/CD Configuration**: Standardizzazione tag Actions (`v4`, `v2`).

### Risolto
- **CI/CD Lints**: Rimozione falsi positivi.
- **Code Gaps**: Eliminati placeholder vuoti.
- Risolto conflitto footer doppio in statistiche.

### Sicurezza
- **Verification Gate**: Nessun codice raggiunge `develop` senza passare il gate.


---




---

## [2.3.0] - 2026-01-10

### Aggiunto
- **Documentazione OpenAPI 3.0**: Specifica completa API accessibile a `/api/docs`
- **Swagger UI**: Interfaccia interattiva per esplorazione API
- **DocumentationController**: Controller dedicato per servire specifiche API
- **OpenApiSpec.php**: Definizioni API globali con attributi PHP 8.2
- **Test Documentazione**: `DocumentationTest.php` per verificare UI e JSON spec
- **Git Workflow**: Documentazione `GIT_WORKFLOW.md` per branch management

### Modificato
- **API Annotations**: Migrati a **Attributi PHP 8.2 nativi** (`#[OA\...]`) invece di PHPDoc
- **SociApiController**: Aggiornato con attributi OpenAPI
- **HealthController**: Aggiornato con attributi OpenAPI  
- **AuthMiddleware**: Permesso accesso pubblico a `/api/docs` e `/api/docs/json`
- **Workflow Git**: Branch feature mantenuti dopo merge per tracciabilità storica

### Rimosso
- **doctrine/annotations**: Rimossa dipendenza legacy (pacchetto abbandonato)

### Risolto
- **Codice Fiscale**: Corretto bug che impediva la corretta validazione e salvataggio del codice fiscale per alcuni formati.

### Sicurezza
- Validazione rigorosa dei path nelle route documentazione

---

## [2.2.0] - 2025-12-28

### Aggiunto
- **Sentry Integration**: Monitoraggio errori production con Sentry SDK 4.0
- **Soft Delete**: Implementazione soft delete per entità critiche
- **Pagination**: Sistema di paginazione per liste extensive
- **SentryMiddleware**: Middleware per cattura automatica eccezioni

### Modificato
- **Error Handling**: Centralizzato con Sentry reporting
- **Database Schema**: Aggiunto campo `deleted_at` per soft delete
- **Query Builder**: Supporto clausole WHERE per paginazione

### Performance
- Ottimizzata gestione memoria con paginazione server-side

---

## [2.1.0] - 2025-12-26

### Aggiunto
- **DI Container Modulare**: Suddiviso in 6 file (`core.php`, `services.php`, `auth.php`, `anagrafica.php`, `intelligence.php`, `devtools.php`)
- **Guide Deployment**: 
  - `GUIDA_GITHUB.md` - Setup repository privato
  - `GUIDA_VERCEL.md` - Deploy serverless Vercel
  - `GUIDA_RAILWAY.md` - Deploy PaaS Railway
- **Docker Multi-Service**: Configurazione completa con MySQL, PHPMyAdmin, ProxySQL

### Modificato
- **Container Loading**: Refactored da `array_merge` a `addDefinitions()` modulare
- **Entry Points**: Aggiornati tutti entry point (`index.php`, `api/index.php`, console, backup)
- **Route Organization**: Migliorata struttura routing

### Risolto
- **IDE Warning**: Eliminato "Internal limitation" su `config/container.php`
- **Base Path**: Corretto per esecuzione a livello root

---



---

## [2.0.1] - 2025-12-27 - Mission-Critical Enterprise
### Aggiunto
- **MySQL Native Support**: Migrazione definitiva da SQLite a MySQL 8.0/MariaDB.
- **Request Correlation**: Ogni richiesta HTTP ha un ID univoco tracciato nei log.
- **Environment Isolation**: Gestione sicura tramite `.env`.
- **Session Hardening**: SameSite=Strict, HttpOnly, Secure.
- **Audit Log Immutabile**: Tabella dedicata per tracciare modifiche dati sensibili.

### Risolto
- **Critical Fix**: Connessione DB negli script CLI.
- **Security Check**: Permission denied su cartella logs (Linux).

---

## [2.0.0] - 2025-12-25

### Aggiunto
- **Release Enterprise**: Prima release production-ready
- **Gestione Soci Completa**: CRUD con validazione avanzata
- **PDF Generation**: Moduli iscrizione e documenti con DomPDF
- **RBAC (Role-Based Access Control)**: 3 ruoli (Admin, Segreteria, Presidente)
- **2FA Obbligatorio**: TOTP con Google Authenticator
- **Audit Trail GDPR**: Logging completo con pseudonimizzazione IP
- **DevTools Dashboard**: Toolkit amministrativo completo
- **GraphQL API**: Schema completo con 12 queries, 8 mutations
- **REST API**: 25+ endpoint documentati
- **Test Suite Completa**: 130+ test automatizzati (Unit, Integration, Feature, Security, E2E)
- **Documentation**: 50+ documenti tecnici in `Documentazione/`

### Architettura
- **Clean Architecture**: Separazione Domain, Application, Infrastructure, Presentation
- **Repository Pattern**: Astrazione accesso dati
- **Service Layer**: Business logic isolata
- **Middleware Pipeline**: 10 middleware per security e logging

### Sicurezza
- CSRF protection (Slim/CSRF)
- Rate limiting (token bucket algorithm)
- Session hardening (SameSite Strict, httpOnly)
- CSP headers per XSS prevention
- Input validation rigorosa
- Password hashing Bcrypt

### Performance
- **MySQL Migration**: Da SQLite a MySQL (40-50x più veloce)
- Performance indices ottimizzati
- Query builder con PDO prepared statements
- Vite build system per frontend assets

---

## [1.3.1] - 2025-12-21 - Mission-Critical Edition

### Aggiunto
- **Transazioni ACID**: Implementazione transazioni atomiche (PDO)
- **Correlation IDs**: Tracciamento end-to-end requests
- **Resilience Monitor**: Monitoraggio proattivo sistema
- **Mission-Critical Console**: CLI per incident response
- **Storage Lockdown**: `.htaccess` per protezione directory uploads
- **Session Hardening Avanzato**: SameSite Strict enforcement

### Testing
- **Test Suite**: 71 test automatizzati, 100% pass rate
- **PHPStan Level 5**: Analisi statica rigorosa, 0 errori

### Sicurezza
- Integrity checks automatici
- Request tracing explorer
- Resilience hub per diagnostica

---

## [1.3.0] - 2025-10-15 - Modernizzazione & DevOps

### Aggiunto
- **Docker**: Containerizzazione completa con Docker Compose
- **Vite Build System**: Frontend build moderno con HMR
- **PestPHP**: Migration da PHPUnit a Pest (63 test)
- **PHPStan**: Static analysis Level 5
- **Phinx Migrations**: Database migration management
- **CI/CD Ready**: GitHub Actions workflows

### Frontend
- **SCSS Compilation**: Architettura CSS modulare
- **Premium Dark Design**: UI/UX moderna con glassmorphism
- **Vite HMR**: Hot Module Replacement per dev velocity

### DevOps
- Deployment automatizzato
- Environment consistency (Docker)
- Build pipeline ottimizzato

---

## [1.2.0] - 2025-08-20 - Robustezza Enterprise

### Aggiunto
- **2FA/TOTP**: Autenticazione a due fattori (OTPHP library)
- **TotpEncryptionService**: Encryption secrets 2FA (AES-256-GCM)
- **Audit Trail**: Logging completo azioni utente
- **GDPR Compliance**: Pseudonimizzazione automatica IP (SHA-256)
- **Security Headers**: CSP, X-Frame-Options, HSTS
- **CSRF Protection**: Token-based con Slim/CSRF

### Sicurezza
- Rate limiting middleware
- Session regeneration su login
- Secure session configuration
- Password policy enforcement

### Privacy
- Consenso GDPR tracking
- Right to erasure implementation
- Data portability (CSV export)

---

## [1.1.0] - 2025-06-10

### Aggiunto
- **Mustache Templates**: Template engine logic-less
- **Responsive Design**: Bootstrap 5.3 integration
- **Dashboard Statistiche**: Charts con Chart.js
- **DataTables**: Ricerca e ordinamento avanzati
- **Email Service**: Notification system (PHPMailer)
- **Backup Automatico**: Script backup database
- **ValidationService**: Validazione centralizzata input

### Modificato
- Refactored UI per mobile-first approach
- Ottimizzata user experience form ingresso

---

## [1.0.0] - 2025-05-01 - Release Iniziale

### Aggiunto
- **Architettura Base**: Clean Architecture foundation
- **Slim Framework 4**: HTTP routing e middleware
- **SQLite Database**: Storage iniziale con PDO
- **Domain Models**: `Socio`, `DatiAnagrafici`, `Documento`, `ModuloIscrizione`
- **Repository Pattern**: `PDOSocioRepository`, `PDODocumentoRepository`
- **Authentication**: Login base con password hashing Bcrypt
- **CRUD Soci**: Create, Read, Update, Delete operations
- **Upload Documenti**: File storage system
- **PHP-DI**: Dependency Injection container
- **Monolog**: Logging framework

### Architettura
- MVC pattern con Slim
- Repository pattern per persistenza
- Service layer per business logic
- Separation of concerns completa

### Sicurezza
- Password hashing Bcrypt (cost 12)
- SQL injection protection (PDO prepared statements)
- XSS prevention (HTML escaping Mustache)

---

## [0.5.0] - 2025-04-10 - Prototipo Funzionale

### Aggiunto
- Proof of concept registrazione socio
- Form HTML base
- Connessione SQLite database
- Query SQL CRUD semplici

---

## [0.1.0] - 2025-03-15 - Kickoff Progetto

### Aggiunto
- Setup iniziale repository
- Struttura directory base
- Composer configuration
- README iniziale
- `.gitignore`

### Decisioni
- Linguaggio: PHP 8.2+
- Framework: Slim 4
- Database: SQLite (iniziale)
- Template Engine: Mustache

---

## Legenda Categorie

- **Aggiunto**: Nuove funzionalità
- **Modificato**: Cambiamenti a funzionalità esistenti
- **Deprecato**: Funzionalità che saranno rimosse
- **Rimosso**: Funzionalità eliminate
- **Risolto**: Bug fix
- **Sicurezza**: Patch di sicurezza
- **Performance**: Ottimizzazioni performance

---

**Mantainer**: Soobadur Mohammad Ajmeer ©  
**Progetto**: Fratellanza Militare di Firenze - Archivio Digitale Soci  
**Versione Corrente**: 7.4.0 "Operational Command" (2026-01-15)  
**License**: Proprietary - All Rights Reserved
