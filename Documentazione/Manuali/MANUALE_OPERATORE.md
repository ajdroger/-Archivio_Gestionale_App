# 📗 MANUALE UTENTE - OPERATORE
## Sistema di Gestione Archivio Fratellanza Militare

**Versione:** 2.0  
**Data:** Gennaio 2026  
**Autore:** Soobadur Mohammad Ajmeer ©  
**Destinatari:** Operatori del Sistema  
**Livello Accesso:** Operativo (Gestione Soci e Documenti)

---

## 📋 INDICE

1. [Introduzione](#introduzione)
2. [Accesso al Sistema](#accesso)
3. [Dashboard Operativa](#dashboard)
4. [Gestione Soci](#gestione-soci)
5. [Gestione Documenti](#gestione-documenti)
6. [Ricerca Avanzata](#ricerca)
7. [Generazione Report](#report)
8. [Procedure Operative Standard](#procedure)
9. [Domande Frequenti (FAQ)](#faq)
10. [Assistenza](#assistenza)

---

## 🌟 INTRODUZIONE {#introduzione}

Benvenuto nel ruolo di **Operatore** del sistema Archivio Fratellanza Militare!

### Cosa Puoi Fare

Come Operatore, hai i permessi per:
- ✅ **Visualizzare** tutti i soci dell'archivio
- ✅ **Creare** nuovi record socio
- ✅ **Modificare** dati anagrafici esistenti
- ✅ **Caricare e gestire** documenti
- ✅ **Generare report** e statistiche
- ✅ **Cercare** soci con filtri avanzati

### Cosa NON Puoi Fare

Le seguenti operazioni sono riservate agli **Amministratori**:
- ❌ Gestire utenti e permessi
- ❌ Accedere a DevTools e debug
- ❌ Configurare API keys
- ❌ Eliminare definitivamente record (hard delete)
- ❌ Modificare configurazioni sistema

**📝 Nota**: Tutte le tue azioni vengono registrate nel sistema di Audit Log per tracciabilità e sicurezza.

---

## 🔐 ACCESSO AL SISTEMA {#accesso}

### 1.1 Come Accedere

1. Apri il tuo browser web (Chrome, Firefox, Safari, Edge)
2. Vai all'indirizzo del sistema (fornito dal tuo amministratore)
3. Nella schermata di login, inserisci:
   - **Username**: Il tuo nome utente (es: `mario.rossi`)
   - **Password**: La password fornita al primo accesso

### 1.2 Primo Accesso e Cambio Password

**Al tuo primo login**, il sistema ti chiederà di:

1. **Cambiare la password temporanea**:
   - Inserisci la password attuale (quella temporanea)
   - Scegli una nuova password sicura:
     - Almeno 8 caratteri
     - Almeno 1 lettera maiuscola
     - Almeno 1 numero
     - Almeno 1 carattere speciale (@, !, #, $, etc.)
   - Esempio password valida: `MiaPassword2026!`

2. **Configurare l'Autenticazione a Due Fattori (2FA)**:
   - Scarica un'app "Authenticator" sul tuo smartphone:
     - **Google Authenticator** (Android/iOS) - consigliata
     - **Microsoft Authenticator** (Android/iOS)
     - **Authy** (Android/iOS/Desktop)
   
   - Apri l'app e seleziona "Aggiungi account"
   - Scansiona il **QR Code** mostrato sullo schermo del computer
   - L'app genererà un codice a 6 cifre che cambia ogni 30 secondi
   - Inserisci questo codice nel campo richiesto
   
   - **⚠️ IMPORTANTE**: Salva i **Codici di Backup** mostrati:
     - Stampa la pagina o salvala come PDF
     - Conservali in luogo sicuro
     - Userai questi codici se perdi il telefono

### 1.3 Login Successivi

Ogni volta che accedi, dovrai fornire:
1. **Username**
2. **Password**
3. **Codice OTP** dall'app Authenticator (6 cifre)

**💡 Suggerimento**: Il codice cambia ogni 30 secondi. Aspetta l'inizio di un nuovo ciclo per avere più tempo per digitarlo!

### 1.4 Logout

Quando hai finito di lavorare:
1. Click sul tuo nome in alto a destra
2. Seleziona **"Esci"** o **"Logout"**
3. ✅ La sessione viene chiusa in sicurezza

**📌 Nota**: Non chiudere semplicemente il browser! Usa sempre il pulsante di Logout per sicurezza.

---

## 📊 DASHBOARD OPERATIVA {#dashboard}

Dopo il login, visualizzi la **Dashboard Operativa** - la tua "home" del sistema.

### Cosa Vedrai

#### **Statistiche Rapide** (in alto)
- 📈 **Totale Soci**: Numero complessivo nell'archivio
- ✅ **Soci Attivi**: Con iscrizione valida
- ⚠️ **Morosi**: Che non hanno pagato la quota annuale
- 📅 **Nuovi Questo Mese**: Iscritti recenti

#### **Azioni Rapide** (pulsanti grandi)
- 🆕 **Nuovo Socio**: Crea un nuovo record
- 🔍 **Ricerca Rapida**: Trova un socio velocemente
- 📄 **Ultimi Documenti**: Vedi i caricamenti recenti
- 📊 **Report del Giorno**: Genera report veloce

#### **Grafico Andamento** (centrale)
Mostra l'andamento delle iscrizioni mese per mese dell'anno corrente.

### Navigazione

**Menu Principale** (in alto o laterale):
- 🏠 **Dashboard**: Torna alla schermata principale
- 👥 **Archivio**: Lista completa soci
- 🆕 **Nuovo Socio**: Crea record
- 📊 **Statistiche**: Report e grafici

**Barra di Ricerca** (sempre visibile):
Puoi cercare un socio digitando:
- Nome o Cognome
- Codice Fiscale
- Matricola
- Email o Telefono

---

## 👥 GESTIONE SOCI {#gestione-soci}

### 3.1 Visualizzare l'Archivio Completo

1. Click su **"Archivio"** nel menu
2. Vedrai una **tabella** con tutti i soci:
   - Matricola
   - Nome e Cognome
   - Codice Fiscale
   - Stato (Attivo / Sospeso / Cancellato)
   - Morosità (✅ Pagante / ⚠️ Moroso)
   - Azioni (Visualizza, Modifica)

#### **Navigazione Tabella**
- **Pagine**: In fondo alla tabella, click sui numeri per cambiare pagina
- **Record per Pagina**: Scegli quanti soci visualizzare (10, 25, 50, 100)
- **Ordinamento**: Click sull'intestazione di una colonna per ordinare
  - Esempio: Click su "Cognome" → ordina alfabeticamente A-Z
  - Click di nuovo → ordina Z-A

#### **Filtri**
Usa i menu a tendina sopra la tabella per filtrare:
- **Stato**: Mostra solo Attivi / Sospesi / Cancellati
- **Morosità**: Mostra solo Paganti / Morosi
- **Anno**: Filtra per anno di iscrizione

**📌 Tip**: Combina più filtri! Es: "Attivi + Morosi" = soci attivi che devono ancora pagare.

### 3.2 Creare un Nuovo Socio

**Quando Usarlo**: Un nuovo membro si iscrive all'associazione.

#### **Procedura Passo-Passo**

**STEP 1: Apri il Form**
1. Click su **"+ Nuovo Socio"** (pulsante grande verde)
2. Si apre la scheda "INIZIALIZZAZIONE NUOVO RECORD SOCIO"

**STEP 2: Compila Dati Identificativi** (colonna sinistra)

**Campi Obbligatori** (contrassegnati con *):
- **Nome**: Es. `Mario`
  - Solo lettere, niente numeri
- **Cognome**: Es. `Rossi`
- **Data di Nascita**: Usa il calendario
  - Formato: GG/MM/AAAA (es: 15/03/1985)
- **Sesso**: Seleziona M o F dal menu
- **Comune o Stato**: Luogo di nascita
  - Es. `Roma` o `Milano` o `Germania` (se nato all'estero)
- **Codice Fiscale**: 16 caratteri maiuscoli
  - Es. `RSSMRA85C15H501Y`
  
  **💡 TRUCCO - Calcolo Automatico**:
  - Se non conosci il CF, compila prima tutti i campi sopra
  - Click sul pulsante blu **"CALCOLA AUTOMATICO"**
  - Il sistema genera il CF corretto!
  - Verifica che sia giusto

**STEP 3: Compila Reti di Contatto** (colonna destra)

**Campi Opzionali** (ma raccomandati):
- **Indirizzo**: Via e civico, CAP, Città
  - Es. `Via Roma 123, 50123 Firenze`
- **Email**: Indirizzo email valido
  - Es. `mario.rossi@gmail.com`
  - ⚠️ Controllo automatico formato
- **Telefono**: Numero con prefisso
  - Es. `+39 333 1234567` o `333-1234567`
- **Matricola**: Lascialo VUOTO
  - Il sistema genera automaticamente un numero progressivo

**STEP 4: Gestione Pagamento Quota**

In fondo al form trovi una **casella switch**:
- **"REGISTRAZIONE QUOTA ASSOCIATIVA 2026"**

**Se il socio HA PAGATO**:
- ✅ Attiva il toggle (diventa verde)
- Effetto: Il sistema genera automaticamente il PDF modulo iscrizione

**Se il socio NON HA PAGATO**:
- ❌ Lascia il toggle spento (grigio)
- Potrai caricare il documento in seguito quando paga

**STEP 5: Salvataggio**

1. **Rivedi tutti i dati** inseriti
2. Click sul pulsante verde **"COMMIT RECORD"**
3. Attendi il caricamento (simbolo rotante)

**✅ Conferma Successo**:
- Messaggio verde: "Socio creato con successo!"
- Reindirizzamento automatico alla scheda del socio
- Se pagamento attivo: PDF generato e salvato

#### **Errori Comuni durante Creazione**

**Errore: "Codice Fiscale già esistente"**
- **Causa**: Stai cercando di creare un socio già presente
- **Soluzione**: 
  1. Usa la ricerca per trovare il socio esistente
  2. Se necessario, MODIFICA il record esistente invece di crearne

 uno nuovo

**Errore: "Campi obbligatori mancanti"**
- **Causa**: Non hai compilato tutti i campi con *
- **Soluzione**: Scorri il form, i campi mancanti saranno evidenziati in rosso

**Errore: "Data non valida"**
- **Causa**: Formato data errato o data futura
- **Soluzione**: Usa il calendario fornito, non digitare manualmente

### 3.3 Modificare un Socio Esistente

**Quando Usarlo**: Cambia indirizzo, telefono, email, corregge errori di digitazione.

#### **Procedura**

1. **Trova il socio**:
   - Usa la barra di ricerca (più veloce)
   - Oppure naviga la tabella Archivio

2. **Apri la scheda**:
   - Click sull'icona **✏️ Matita** accanto al nome
   - Oppure click sul nome → pulsante "Modifica"

3. **Modifica i campi**:
   - Aggiorna i dati che servono
   - ⚠️ **Non puoi modificare**: Codice Fiscale (chiave univoca)

4. **Salva**:
   - Click su **"Salva Modifiche"**
   - Conferma: Messaggio "Aggiornamento riuscito"

**📌 Best Practice**:
- Modifica solo i campi necessari
- Non lasciare campi vuoti a caso
- Verifica doppia di CF e nome prima di salvare

### 3.4 Visualizzare Dettaglio Socio

1. Click sul **nome** del socio nella tabella
2. Si apre la **Scheda Completa** con tab:
   - 📋 **Anagrafica**: Tutti i dati personali
   - 📄 **Documenti**: Lista file allegati
   - 📊 **Storico**: Cronologia modifiche (se disponibile)

**Dalla Scheda Puoi**:
- ✏️ Modificare i dati
- 📄 Caricare documenti
- 📥 Scaricare PDF modulo
- 🖨️ Stampare scheda

### 3.5 Gestione Morosità

**Identificare Soci Morosi**:
- **Icona ⚠️ rossa** nella colonna Morosità
- Oppure usa il **Filtro "Morosi"** sull'archivio

**Cosa Significa "Moroso"?**
Un socio è moroso se non ha un documento "Modulo Iscrizione" con stato "Validato" per l'anno corrente.

**Come Regolarizzare un Moroso**:
1. Socio paga la quota associativa
2. Apri scheda socio
3. Tab **"Documenti"**
4. Click **"+ Carica Documento"**
5. Tipo: "Modulo Iscrizione"
6. Upload file PDF o foto ricevuta
7. Anno: 2026 (anno corrente)
8. Salva
9. ✅ Socio automaticamente rimosso da lista morosi

---

## 📄 GESTIONE DOCUMENTI {#gestione-documenti}

### 4.1 Visualizzare Documenti di un Socio

1. Apri **scheda socio**
2. Click su tab **"Documenti"**
3. Vedi la lista con:
   - Nome file
   - Tipo (Modulo Iscrizione / Documento Generico)
   - Data caricamento
   - Stato (In Attesa / Validato / Rifiutato)
   - Anno solare di riferimento
   - Azioni (Download, Elimina)

### 4.2 Caricare un Nuovo Documento

**Quando Serve**:
- Socio paga quota annuale → Modulo Iscrizione
- Ricevuta bonifico → Documento Generico
- Certificato medico → Documento Generico
- Altro documento importantissimo

#### **Procedura Caricamento**

**STEP 1**: Scheda socio → Tab "Documenti" → **"+ Carica Documento"**

**STEP 2**: Compila il form:
- **Tipo Documento**: Scegli dal menu
  - `Modulo Iscrizione`: Per quote associative (PDF generato o caricato)
  - `Documento Generico`: Per tutto il resto

**STEP 3**: Click su **"Scegli File"**
- Naviga il tuo computer
- Seleziona il file:
  - **Formati accettati**: PDF (.pdf), Immagini (.jpg, .jpeg, .png)
  - **Dimensione max**: 10 MB
  
**STEP 4**: Compila dettagli (se Modulo Iscrizione):
- **Anno Solare**: Anno di riferimento (default: corrente 2026)
- **Quota Versata**: Importo in euro (es: 50.00)

**STEP 5**: Se richiesto, conferma **Consenso GDPR**:
- Trattamento dati personali: ✅
- Cessione a terzi: ❌ (di solito)
- Marketing: ❌ (di solito)

**STEP 6**: Click **"Upload"**
- Progress bar di caricamento
- ✅ Conferma: "Documento caricato con successo"

#### **Errori Comuni Upload**

**"File troppo grande"**
- Il file supera 10MB
- **Soluzione**: Comprimi il PDF o riduci qualità immagine
  - Tool online: smallpdf.com, ilovepdf.com

**"Formato non supportato"**
- Stai caricando .docx, .txt o altro formato
- **Soluzione**: Converti in PDF
  - Word → Salva come → PDF
  - Immagini → Usa scanner app per generare PDF

**"Errore durante upload"**
- Connessione internet lenta o interrotta
- **Soluzione**: Riprova, verifica connessione Wi-Fi

### 4.3 Scaricare un Documento

1. Tab "Documenti" del socio
2. Click sull'icona **📥 Download** accanto al nome file
3. Il browser scarica il file nella cartella "Download"
4. Apri con lettore PDF o visualizzatore immagini

**💡 Tip**: Rinomina subito il file scaricato in modo descrittivo:
- ❌ `1234_modulo.pdf`
- ✅ `Rossi_Mario_Modulo2026.pdf`

### 4.4 Eliminare un Documento

**⚠️ Attenzione**: Operazione delicata, assicurati di voler davvero eliminare!

1. Tab "Documenti"
2. Click sull'icona **🗑️ Cestino** accanto al documento
3. Conferma popup: "Sei sicuro?"
4. ✅ Documento eliminato (file cancellato dal server)

**📌 Nota**: L'eliminazione è definitiva per gli Operatori. Solo gli Amministratori possono recuperare da backup.

---

## 🔍 RICERCA AVANZATA {#ricerca}

### 5.1 Ricerca Rapida (Barra Principale)

La **barra di ricerca** in alto è sempre disponibile e cerca automaticamente in:
- Nome
- Cognome
- Codice Fiscale
- Matricola
- Email
- Telefono

**Come Usarla**:
1. Digita almeno 3 caratteri
2. I risultati appaiono in tempo reale
3. Click sul socio desiderato

**Esempi**:
- Digita `Mario` → trova tutti i "Mario..."
- Digita `RSS` → trova CF che iniziano con RSS (come Rossi)
- Digita `333` → trova numeri telefono che contengono 333
- Digita `M001` → trova matricola M001

### 5.2 Ricerca con Filtri (Archivio)

Dalla pagina **Archivio**, usa i filtri per ricerche complesse:

**Filtri Disponibili**:
- **Stato**: Attivo / Sospeso / Cancellato
- **Morosità**: Tutti / Solo Paganti / Solo Morosi
- **Anno Iscrizione**: 2020, 2021, ..., 2026
- **Ricerca Testuale**: Campo libero per nome/cognome/CF

**Combinazioni Utili**:
- `Attivi + Morosi` → Soci che devono pagare
- `Anno 2025 + Paganti` → Chi ha pagato nel 2025
- `Sospesi` → Soci da riattivare

**Reset Filtri**:
Click sul pulsante **"Reset"** o **"Mostra Tutti"**

### 5.3 Ricerca per Nome Parziale

La ricerca funziona anche con **nomi parziali**:
- `Mar` → trova Mario, Marco, Marta, Mariano
- `Ros` → trova Rossi, Rossini, Rossetti

**💡 Case Insensitive**: Puoi digitare maiuscolo o minuscolo, funziona uguale!

---

## 📊 GENERAZIONE REPORT {#report}

### 6.1 Accesso Statistiche

**Menu**: `Statistiche` o `Report`

Visualizzi la dashboard con:
- **Numeri Aggregati**: Totali, Attivi, Morosi, %
- **Grafico Trend**: Iscrizioni per mese
- **Grafico Demografico**: Distribuzione per età

### 6.2 Esportare Report

**Quando Serve**:
- Report per assemblea annuale
- Elenco soci da stampare
- Analisi Excel per tesoriere

#### **Procedura Export**

1. Pagina **Statistiche** → Click **"Esporta Report"**
2. Scegli **Formato**:
   - **PDF**: Per stampa o presentazione
   - **Excel (.xlsx)**: Per analisi dati
   - **CSV**: Per import in altri programmi
3. Scegli **Scope**:
   - Tutti i soci
   - Solo attivi
   - Solo morosi
4. Click **"Download"**
5. Il file viene scaricato nel browser

**📌 Tip**: Il file PDF include intestazione con logo e data generazione automatica.

### 6.3 Report Personalizzati

**Se hai bisogno di report specifici** (es: soci di una certa città, età, ecc):
1. Contatta l'**Amministratore**
2. Spiega quale dato ti serve
3. L'admin può creare query custom nel Database Inspector

---

## 📋 PROCEDURE OPERATIVE STANDARD {#procedure}

### 7.1 Procedura: Iscrizione Nuovo Socio

**Scenario**: Una persona si presenta per iscriversi.

**Step**:
1. ✅ **Raccogli dati anagrafici**:
   - Carta d'identità per CF, nome, cognome, data nascita
   - Indirizzo residenza
   - Email e telefono
2. ✅ **Verifica socio non esista già**:
   - Ricerca per CF
   - Se esiste: STOP, non duplicare
3. ✅ **Crea record socio** come da sezione 3.2
4. ✅ **Registra pagamento quota**:
   - Se paga subito: Attiva toggle "Quota Associativa"
   - Se paga dopo: Lascia toggle off, registrerai dopo
5. ✅ **Genera/Stampa modulo**:
   - Se toggle attivo: PDF auto-generato
   - Scarica e stampa per firma
6. ✅ **Consegna documenti socio**:
   - Copia modulo firmato
   - Tessera associativa (se disponibile)
   - Materiale informativo

**✅ Check Finale**: Socio visibile in archivio con stato "Attivo" e matricola assegnata.

### 7.2 Procedura: Rinnovo Quota Annuale

**Scenario**: Socio esistente paga quota per anno nuovo.

**Step**:
1. ✅ **Identifica socio**: Ricerca per nome o matricola
2. ✅ **Verifica morosità**: Se già pagato, conferma con socio
3. ✅ **Registra pagamento**:
   - Apri scheda → Tab "Documenti"
   - Upload nuovo Modulo Iscrizione anno corrente
   - Compila quota versata
4. ✅ **Verifica rimozione morosità**:
   - Torna ad archivio
   - Filtro "Morosi"
   - Socio non deve più apparire
5. ✅ **Stampa ricevuta** (se richiesta):
   - Scarica PDF modulo appena caricato
   - Stamp e consegna

### 7.3 Procedura: Aggiornamento Dati Contatto

**Scenario**: Socio cambia email, telefono o indirizzo.

**Step**:
1. ✅ **Ricevi richiesta**: Email, telefono, visita
2. ✅ **Verifica identità**: CF o documento valido
3. ✅ **Apri scheda socio**
4. ✅ **Modifica dati**:
   - Click "Modifica"
   - Aggiorna solo campi richiesti
   - Non toccare altri dati!
5. ✅ **Salva**
6. ✅ **Conferma a socio**: Invia email o SMS conferma

**⚠️ Attenzione**: Se il socio richiede modifica nome/cognome/CF (cambio identità legale), richiedi documento ufficiale e consulta Amministratore!

### 7.4 Procedura: Gestione Documento Scaduto

**Scenario**: Modulo iscrizione anno precedente da archiviare.

**Step**:
1. ✅ **Non eliminare**: I documenti vecchi vanno conservati per storico
2. ✅ **Verifica nuovo documento caricato** per anno corrente
3. ✅ **Se richiesto archiviazione fisica**:
   - Scarica PDF documento vecchio
   - Stampa
   - Archivia in raccoglitore fisico anno precedente
   - Aggiungi nota nel sistema (se campo disponibile)

---

## ❓ DOMANDE FREQUENTI (FAQ) {#faq}

### **Q: Ho dimenticato la password, cosa faccio?**
**A**: 
1. Nella schermata login, click su "Password dimenticata?"
2. Inserisci il tuo username
3. Riceverai email con link reset
4. Segui istruzioni email
5. Se non ricevi email: Contatta Amministratore

### **Q: Ho perso il telefono con l'app 2FA, come accedo?**
**A**:
1. Usa uno dei **codici di backup** salvati al setup
2. Ogni codice funziona una volta sola
3. Dopo login, vai in Impostazioni → Riconfigura 2FA
4. Se hai perso anche i codici backup: Contatta Amministratore urgentemente

### **Q: Posso creare soci senza email o telefono?**
**A**: Sì, sono campi opzionali. Ma è **fortemente raccomandato** raccogliere almeno un contatto per comunicazioni future.

### **Q: Come faccio a sapere se un socio ha pagato?**
**A**: 
- Colonna "Morosità" nella tabella: ✅ = Pagato, ⚠️ = Moroso
- Oppure apri scheda → Tab "Documenti" → Verifica Modulo Iscrizione anno corrente con stato "Validato"

### **Q: Posso modificare un documento dopo caricamento?**
**A**: No, i documenti sono immutabili. Se hai caricato file sbagliato:
1. Elimina documento errato
2. Carica documento corretto

### **Q: Cosa significa "Soft Delete"?**
**A**: Gli Operatori non eliminano definitivamente i record. Quando elimini un socio, viene solo "nascosto" ma recuperabile da Amministratore. Questo previene errori irreversibili.

### **Q: Posso vedere chi ha modificato un socio?**
**A**: Se il sistema ha Audit Log attivo (chiedi all'Amministratore), ogni modifica è tracciata con:
- Chi (username)
- Quando (data e ora)
- Cosa (campi modificati)

Ma tu come Operatore probabilmente non vedi questo log direttamente. Chiedi all'Amministratore se serve.

### **Q: Il sistema è lento, cosa faccio?**
**A**:
1. **Verifica connessione internet**: Apri speedtest.net
2. **Svuota cache browser**: Ctrl+Shift+Canc → Svuota cache
3. **Prova altro browser**: Se usi Chrome, prova Firefox o viceversa
4. **Se persiste**: Segnala ad Amministratore indicando:
   - Quale pagina è lenta
   - Quando è iniziato
   - Cosa stavi facendo

### **Q: Posso usare il sistema da smartphone/tablet?**
**A**: Sì, il sistema è "responsive" ma l'esperienza è ottimizzata per desktop. Alcune funzioni (upload documenti, grafici) funzionano meglio su computer.

---

## 🆘 ASSISTENZA {#assistenza}

### Quando Contattare il Supporto

**Contatta Subito se**:
- ❌ Non riesci ad accedere (dopo 3 tentativi)
- ❌ Errore critico durante salvataggio (dati persi?)
- ❌ Sistema completamente inaccessibile
- ❌ Sospetto di dato errato critico (CF sbagliato, duplicati)

**Contatta Quando Puoi se**:
- ❔ Dubbio su procedura operativa
- ❔ Richiesta funzionalità non trovata
- ❔ Suggerimento miglioramento sistema
- ❔ Formazione aggiuntiva

### Canali di Supporto

**Livello 1 - Supporto Operatori** (preferito):
- ✉️ Email: `supporto@associazione.it`
- ☎️ Telefono: `+39 XXX XXX XXXX`
- ⏰ Orario: Lunedì-Venerdì, 9:00-18:00

**Livello 2 - Amministratori**:
- Solo per problemi tecnici gravi
- Mail: `admin@associazione.it`

### Informazioni da Fornire

Quando scrivi al supporto, includere:
- ✅ Tuo **username**
- ✅ **Cosa stavi facendo** quando è successo il problema
- ✅ **Messaggio di errore** esatto (screenshot se possibile)
- ✅ **Browser utilizzato** (Chrome, Firefox, etc.)
- ✅ **Quando** è successo (data e ora)

**Esempio Email Supporto**:
```
Oggetto: Errore upload documento

Buongiorno,
sono Mario Rossi (username: m.rossi).
Oggi alle 10:30 stavo caricando un PDF per il socio 
RSSMRA80A01H501Z ma ho ricevuto errore 
"File troppo grande" anche se il file è solo 2MB.

Ho provato con Chrome e stesso errore.
Potete aiutarmi?

Grazie
```

---

## ✅ CHECKLIST OPERATORE

**All'Inizio Giornata**:
- [ ] Login al sistema
- [ ] Verifica nessun alert critico in dashboard
- [ ] Check casella email per richieste soci

**Durante Lavorazione**:
- [ ] Doppio controllo Codice Fiscale prima di salvare nuovo socio
- [ ] Verifica email valida se inserita
- [ ] Salva frequentemente (non tenere form aperti ore)

**Fine Giornata**:
- [ ] Verifica che tutti i documenti caricati oggi siano visibili
- [ ] Logout dal sistema (non chiudere solo browser!)
- [ ] Report eventuali anomalie ad Amministratore

**Settimanale**:
- [ ] Review lista morosi (se di tua competenza)
- [ ] Verifica che tutti i nuovi soci inseriti abbiano documenti

---

## 📚 RISORSE UTILI

- **Manuale Completo PDF**: Chiedi ad Amministratore
- **VideoTutorial**: (se disponibili, link forniti da admin)
- **Glossario**: 
  - **CF**: Codice Fiscale
  - **PDF**: Portable Document Format (file documento)
  - **2FA**: Two-Factor Authentication (autenticazione a due fattori)
  - **OTP**: One-Time Password (codice usa-e-getta)
  - **Soft Delete**: Eliminazione reversibile

---

**Fine Manuale Operatore**  
*Versione 2.0 - Gennaio 2026*  
*Per assistenza: supporto@associazione.it*

**Buon Lavoro! 🚀**
