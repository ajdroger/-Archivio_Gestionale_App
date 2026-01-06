# 📙 MANUALE UTENTE - UTENTE BASE
## Sistema di Gestione Archivio Fratellanza Militare

**Versione:** 2.0  
**Data:** Gennaio 2026  
**Autore:** Soobadur Mohammad Ajmeer ©  
**Destinatari:** Utenti Base (Solo Lettura)  
**Livello Accesso:** Consultazione (Read-Only)

---

## 📋 INDICE

1. [Introduzione](#introduzione)
2. [Accesso al Sistema](#accesso)
3. [Dashboard Utente](#dashboard)
4. [Consultazione Archivio](#archivio)
5. [Ricerca Soci](#ricerca)
6. [Visualizzazione Dettagli](#dettagli)
7. [Domande Frequenti (FAQ)](#faq)
8. [Assistenza](#assistenza)

---

## 🌟 INTRODUZIONE {#introduzione}

Benvenuto come **Utente Base** del sistema Archivio Fratellanza Militare!

### Cosa Puoi Fare

Come Utente, il tuo accesso è di **sola lettura (consultazione)**:
- ✅ **Visualizzare** l'elenco soci
- ✅ **Cercare** soci per nome, cognome, codice fiscale
- ✅ **Consultare** dettagli anagrafici di base
- ✅ **Visualizzare** statistiche generali

### Cosa NON Puoi Fare

Le seguenti operazioni richiedono permessi superiori:
- ❌ Creare nuovi soci
- ❌ Modificare dati esistenti
- ❌ Caricare o eliminare documenti
- ❌ Generare report avanzati
- ❌ Accedere a funzioni amministrative

**📝 Nota**: Se hai bisogno di modificare dati o eseguire operazioni, contatta un **Operatore** o **Amministratore**.

---

## 🔐 ACCESSO AL SISTEMA {#accesso}

### 2.1 Come Accedere

1. Apri il tuo browser web (Chrome, Firefox, Safari consigliati)
2. Vai all'indirizzo del sistema fornito dall'amministratore
   - Esempio: `https://archivio.associazione.it`
3. Nella schermata di login, inserisci:
   - **Username**: Il tuo nome utente assegnato
   - **Password**: La password fornita

### 2.2 Primo Accesso

Al primo login, ti verrà chiesto di:

**1. Cambiare la Password Temporanea**
- Inserisci la password attuale (temp)
- Crea una nuova password che rispetti i requisiti:
  - Minimo 8 caratteri
  - Almeno 1 lettera maiuscola
  - Almeno 1 numero
  - Almeno 1 simbolo speciale (@, !, #, etc.)
- Esempio: `MiaPasswordSicura2026!`

**2. Configurare 2FA (Autenticazione a Due Fattori)**

Questa è una misura di sicurezza aggiuntiva:

**Cosa ti serve**:
- Uno smartphone (Android o iOS)
- Un'app "Authenticator" gratuita:
  - **Google Authenticator** ⭐ (consigliata)
  - **Microsoft Authenticator**
  - **Authy**

**Procedura Setup**:
1. Scarica e installa l'app Authenticator sul telefono
2. Sul computer, visualizzi un **QR Code**
3. Apri l'app → "Aggiungi account" o "+"
4. Scansiona il QR Code con la fotocamera
5. L'app genererà un codice a 6 cifre che cambia ogni 30 secondi
6. Inserisci questo codice sul computer
7. ✅ 2FA configurato!

**⚠️ IMPORTANTE - Codici di Backup**:
- Dopo la configurazione, il sistema mostra **Codici di Backup**
- **Salvali in un posto sicuro** (stampali o salvali su file)
- Userai questi codici se perdi il telefono

### 2.3 Login Normale

Ogni volta che accedi, fornisci:
1. **Username**
2. **Password**
3. **Codice OTP** dall'app (quelle 6 cifre che cambiano)

**💡 Suggerimento**: Aspetta che il timer nell'app sia all'inizio di un nuovo ciclo, così hai 30 secondi pieni per digitare il codice!

### 2.4 Logout

Quando hai finito:
1. Click sul tuo **nome utente** in alto a destra
2. Seleziona **"Esci"** o **"Logout"**

**❗Non chiudere semplicemente la finestra**: Usa sempre il logout per sicurezza!

---

## 📊 DASHBOARD UTENTE {#dashboard}

Dopo il login, visualizzi la **Dashboard Principale** con informazioni generali.

### Cosa Vedi

#### **Statistiche Generali** (schede in alto)
- 📊 **Totale Soci**: Numero totale nell'archivio
- ✅ **Soci Attivi**: Con iscrizione valida
- 📅 **Ultimi Inseriti**: Nuovi soci recenti

#### **Grafico Andamento** (centrale)
Visualizza l'andamento delle iscrizioni mese per mese.

#### **Azioni Rapide**
- 🔍 **Ricerca Rapida**: Trova un socio velocemente
- 👥 **Vai all'Archivio**: Visualizza lista completa

### Menu di Navigazione

Il menu principale contiene:
- 🏠 **Dashboard**: Torna alla home
- 👥 **Archivio Soci**: Lista consultabile
- 🔍 **Ricerca**: Trova soci specifici
- 📞 **Assistenza**: Link supporto

**Barra di Ricerca**:
Sempre visibile in alto, permette ricerche rapide per:
- Nome o Cognome
- Codice Fiscale  
- Matricola

---

## 👥 CONSULTAZIONE ARCHIVIO {#archivio}

### 3.1 Visualizzare l'Elenco Soci

1. Click su **"Archivio Soci"** nel menu
2. Visualizzi una **tabella** con:
   - **Matricola**: Numero identificativo progressivo
   - **Nome e Cognome**: Dati anagrafici
   - **Codice Fiscale**: CF italiano
   - **Stato**: Attivo / Sospeso / Cancellato
   - **Morosità**: ✅ In regola / ⚠️ Quota da pagare
   - **Azioni**: 👁️ Visualizza dettagli

### 3.2 Navigare la Tabella

**Paginazione**:
- In fondo alla tabella trovi i numeri di pagina
- Click su un numero per andare a quella pagina
- Oppure usa "Precedente" / "Successivo"

**Elementi per Pagina**:
- Scegli quanti soci visualizzare per pagina: 10, 25, 50, 100
- Default: 25 soci per pagina

**Ordinamento**:
- Click sull'**intestazione** di una colonna per ordinare
- Esempio: Click su "Cognome" → ordina A-Z
- Click di nuovo → ordina Z-A (inverso)

### 3.3 Filtri di Visualizzazione

Sopra la tabella trovi menu a tendina per filtrare:

**Filtro Stato**:
- Tutti
- Solo Attivi
- Solo Sospesi
- Solo Cancellati

**Filtro Morosità**:
- Tutti
- Solo in Regola (paganti)
- Solo Morosi

**Esempio d'Uso**:
- Per vedere solo i soci attivi in regola: `Stato: Attivi + Morosità: In Regola`
- Per vedere chi deve ancora pagare: `Morosità: Morosi`

**Reset Filtri**:
Click sul pulsante **"Reset"** o **"Mostra Tutti"** per tornare alla vista completa.

---

## 🔍 RICERCA SOCI {#ricerca}

### 4.1 Ricerca Rapida (Barra Principale)

La **barra di ricerca** in alto è il modo più veloce per trovare qualcuno.

**Come Funziona**:
1. Digita almeno **3 caratteri**
2. Il sistema cerca automaticamente in:
   - Nome
   - Cognome
   - Codice Fiscale
   - Matricola
   - Email (se visibile)
3. I risultati appaiono in tempo reale
4. Click sul nome per aprire la scheda

**Esempi Pratici**:
- `Mar` → Trova Mario, Marco, Marta, Maria, ecc.
- `RSS` → Trova CF che iniziano con RSS (Rossi)
- `M00` → Trova matricole come M001, M002, ecc.
- `@gmail` → Trova soci con email Gmail

**💡 Tip**: La ricerca ignora maiuscole/minuscole, puoi digitare come preferisci!

### 4.2 Ricerca Avanzata

Dalla pagina **Archivio**, usa i filtri combinati:

**Filtri Disponibili**:
- Campo di ricerca testuale
- Stato iscrizione
- Morosità
- Anno iscrizione (se disponibile)

**Strategia di Ricerca Efficace**:
1. **Se conosci il cognome**: Digitalo nella barra
2. **Se cerchi categoria**: Usa filtri (es: tutti i morosi)
3. **Se cerchi per periodo**: Filtro anno + stato

### 4.3 Ricerca per Nome Parziale

Non serve digitare il nome completo:
- `Gio` → Giovanni, Giovanna, Giorgio, Gioele
- `D'Al` → D'Alessandro, D'Aliberti
- `Van` → Vanni, Vanessa

Il sistema cerca **ovunque** nel campo, quindi trova sia all'inizio che in mezzo al nome.

---

## 📋 VISUALIZZAZIONE DETTAGLI {#dettagli}

### 5.1 Aprire Scheda Socio

Dalla tabella Archivio:
1. Click sull'**icona 👁️** accanto al nome
2. Oppure click **direttamente sul nome**
3. Si apre la **Scheda Completa** del socio

### 5.2 Cosa Visualizzi

La scheda è organizzata in **schede (tab)**:

#### **Tab 📋 Anagrafica**
- **Dati Identificativi**:
  - Nome e Cognome
  - Codice Fiscale
  - Data di Nascita
  - Luogo di Nascita
  - Sesso

- **Dati di Contatto**:
  - Indirizzo completo
  - Email
  - Telefono

- **Dati Associativi**:
  - Matricola
  - Stato (Attivo/Sospeso/Cancellato)
  - Data iscrizione
  - Check morosità

#### **Tab 📄 Documenti** (se visibili)
- Nome file
- Tipo documento
- Data caricamento
- Anno di riferimento

**⚠️ Nota**: Potresti non vedere i contenuti dei documenti (solo nomi), dipende dalle policy di privacy configurate.

#### **Tab 📊 Statistiche** (se abilitato)
- Storico iscrizioni
- Pagamenti anni precedenti
- Altre info aggregate

### 5.3 Azioni Disponibili

Dalla scheda, puoi:
- 🖨️ **Stampare**: Click "Stampa" per stampare la scheda (senza documenti)
- 📋 **Copiare**: Seleziona testo e copia (Ctrl+C)
- 🔎 **Navigare**: Pulsanti per socio precedente/successivo
- ◀️ **Tornare**: Pulsante "Torna all'Archivio"

**❌ NON Puoi**:
- Modificare dati
- Scaricare documenti riservati
- Eliminare record

### 5.4 Informazioni Privacy

**Dati Sensibili**:
Alcuni campi potrebbero essere:
- 🔒 **Nascosti**: Simbolo `***` o campo vuoto
- 👁️ **Parzialmente Visibili**: (es. email: `m***@gmail.com`)

Questo dipende dalle regole GDPR e privacy configurate dall'amministratore.

**Cosa Fare se Serve un Dato Nascosto**:
Contatta un **Operatore** o **Amministratore** spiegando perché ti serve quella info. Valuteranno la richiesta secondo policy aziendali.

---

## ❓ DOMANDE FREQUENTI (FAQ) {#faq}

### **Q: Ho dimenticato la password, come faccio?**
**A**: 
1. Nella schermata login, cerca link **"Password dimenticata?"**
2. Inserisci il tuo username
3. Riceverai un'email con istruzioni
4. Segui il link nell'email per reimpostare
5. Se NON ricevi email: Contatta l'Amministratore

### **Q: Ho perso il telefono con l'app 2FA!**
**A**: 
1. Usa uno dei **codici di backup** che hai salvato
2. Ogni codice funziona una sola volta
3. Dopo il login, configura 2FA su un nuovo telefono
4. Se non hai i backup: Contatta **urgentemente** l'Amministratore

### **Q: Posso scaricare l'elenco soci?**
**A**: No, come Utente Base non hai permessi di export. Se ti serve per motivi legittimi (es: mailing list associativa), chiedi a un Amministratore che genererà il file per te.

### **Q: Vedo "⚠️ Moroso" accanto al mio nome, cosa significa?**
**A**: Significa che non risulta un pagamento della quota associativa per l'anno corrente. Se hai pagato:
1. Contatta la Segreteria con prova pagamento (bonifico, ricevuta)
2. Operatore caricherà il documento
3. Status aggiornato automaticamente

### **Q: Alcuni campi sono vuoti o con `***`, perché?**
**A**: 
- **Vuoti**: Dato non inserito al momento creazione socio
- **`***` (asterischi)**: Dato presente ma nascosto per privacy (policy GDPR)
- Solo chi ne ha diritto (es: segreteria) vede dati completi

### **Q: Posso consultare l'archivio dal cellulare?**
**A**: Sì! Il sistema è "responsive" (si adatta allo schermo). Però per esperienza ottimale, si consiglia:
- **Smartphone**: Solo ricerche veloci e consultazione
- **Desktop/Laptop**: Navigazione completa archivio

### **Q: Il sistema è lento, cosa posso fare?**
**A**: 
1. **Controlla connessione internet**: Apri speedtest.net
2. **Svuota cache browser**: 
   - Chrome: Ctrl+Shift+Canc → Elimina cache
   - Firefox: Similare
3. **Prova browser diverso**: Se usi Chrome, prova Firefox
4. **Se persiste**: Segnala ad Amministratore con:
   - Quale pagina è lenta
   - Quando hai notato il problema

### **Q: Posso vedere chi ha modificato i dati di un socio?**
**A**: No, come Utente Base non hai accesso all'Audit Log. Questa funzione è riservata ad Amministratori per tracciabilità e sicurezza.

### **Q: Vedo un socio duplicato con stesso nome, è un errore?**
**A**: Potrebbe essere:
- **Omonimia**: Due persone diverse con stesso nome (verifica CF)
- **Errore**: Duplicato reale (segnala ad Amministratore)

Controlla sempre il **Codice Fiscale** che è univoco.

### **Q: Quanto tempo rimango loggato?**
**A**: La sessione dura **8 ore di inattività**. Se non usi il sistema per 8 ore, verrai disconnesso automaticamente per sicurezza. Dovrai rifare login.

---

## 🆘 ASSISTENZA {#assistenza}

### 7.1 Quando Richiedere Supporto

**Contatta se**:
- ❌ Non riesci ad accedere dopo 3 tentativi
- ❌ Vedi errori strani o messaggi incomprensibili
- ❌ Dati visualizzati sembrano errati o incongruenti
- ❔ Hai dubbi su come interpretare informazioni
- 💡 Hai suggerimenti per migliorare il sistema

**NON serve contattare per**:
- ✅ Campi nascosti con `***` (è normale, privacy)
- ✅ "Non puoi modificare" (livello accesso corretto)

### 7.2 Come Contattare

**Supporto Generale**:
- ✉️ **Email**: `supporto@associazione.it`
- ☎️ **Telefono**: `+39 XXX XXX XXXX`
- ⏰ **Orario**: Lunedì-Venerdì, 9:00-18:00
- **Risposta**: Entro 24-48 ore lavorative

**Emergenze (es: account bloccato)**:
- ☎️ Telefono durante orari ufficio
- ✉️ Email con oggetto: **"URGENTE - [descrizione]"**

### 7.3 Informazioni da Fornire

Per aiutarti meglio, quando scrivi includi:
- ✅ Tuo **username** (NO password!)
- ✅ **Cosa stavi facendo** (es: cercavo socio...)
- ✅ **Messaggio di errore** esatto (screenshot se possibile)
- ✅ **Browser usato** (Chrome, Firefox, Safari, Edge)
- ✅ **Quando** è successo (data e ora approssimativa)

**Esempio Email Supporto**:
```
Oggetto: Problema ricerca socio

Buongiorno,
sono Luca Bianchi (username: l.bianchi).

Stamattina alle 10:00 circa ho cercato "Rossi" nella 
barra ricerca ma non mi mostra risultati, invece ieri 
funzionava. 

Uso Chrome su Windows 10.
Potete verificare?

Grazie
```

### 7.4 Auto-Aiuto

Prima di contattare supporto, prova:
1. **Ricarica pagina**: F5 o Ctrl+R
2. **Svuota cache**: Ctrl+Shift+Canc
3. **Prova browser diverso**: Firefox se usavi Chrome
4. **Riprova dopo qualche minuto**: Potrebbe essere manutenzione temporanea

Se dopo questi step il problema persiste, allora contatta supporto.

---

## 📚 RISORSE UTILI

### Link Rapidi
- 🏠 **Sistema**: [URL fornito da admin]
- 📧 **Supporto**: supporto@associazione.it
- 📘 **Manuali**: (folder documenti interni)

### Glossario Termini

- **CF**: Codice Fiscale italiano (16 caratteri)
- **2FA**: Autenticazione a Due Fattori (password + codice app)
- **OTP**: Codice temporaneo usa-e-getta (6 cifre)
- **Dashboard**: Pagina principale con riassunto
- **GDPR**: Regolamento Europeo Privacy
- **Moroso**: Socio che deve pagare quota annuale
- **Matricola**: Numero identificativo progressivo socio
- **Read-Only**: Sola lettura (puoi vedere, non modificare)

### Formazione

Se hai bisogno di formazione aggiuntiva:
- Chiedi all'Amministratore sessioni formative
- Disponibile manuale PDF completo
- Video tutorial (se esistenti, chiedere)

---

## ✅ CONSIGLI PER L'USO

**Sicurezza**:
- ✅ NON condividere mai username e password
- ✅ Fai SEMPRE logout quando finisci
- ✅ NON lasciare computer incustodito con sistema aperto
- ✅ Cambia password periodicamente (ogni 3-6 mesi)

**Efficienza**:
- 💡 Usa ricerca rapida per singoli soci
- 💡 Usa filtri per categorie (morosi, attivi, ecc.)
- 💡 Salva nei preferiti browser la pagina login
- 💡 Impara shortcut tastiera (Ctrl+F per cercare in pagina)

**Privacy**:
- 🔒 Rispetta privacy dati visualizzati
- 🔒 NON condividere info soci con non autorizzati
- 🔒 NON fare screenshot con dati sensibili senza permesso
- 🔒 Segnala sospette violazioni privacy ad Amministratore

---

**Fine Manuale Utente Base**  
*Versione 2.0 - Gennaio 2026*  
*Per assistenza: supporto@associazione.it*

**Buona Consultazione! 👍**
