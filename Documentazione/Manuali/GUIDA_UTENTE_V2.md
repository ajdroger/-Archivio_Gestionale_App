# Guida Utente - Digitalizzazione Archivio v2.0

**Fratellanza Militare di Firenze**
*Versione Documento: 2.0.0 (Mission-Critical Release)*
*Aggiornato al: 25/12/2025*

---

## 1. Benvenuto

Benvenuti nel sistema di gestione digitale dell'archivio soci v2.0.
Questa piattaforma sostituisce interamente i registri cartacei, permettendo una gestione sicura, veloce e conforme al GDPR di tutti i dati associativi.

### Requisiti di Sistema
*   PC connesso alla rete interna (Intranet).
*   Browser moderno (Google Chrome, Microsoft Edge, Firefox).
*   Non è richiesta installazione di software locale.

---

## 2. Accesso al Sistema

### Login
1.  Aprire il browser all'indirizzo comunicato dal reparto IT (es. `http://127.0.0.1/fratellanza-militare-archivio/public`).
2.  Inserire le credenziali fornite:
    *   **Username**: Il vostro identificativo (es. `admin` o `utente.segreteria`).
    *   **Password**: La password temporanea fornita.
3.  Fare clic su **Accedi**.

> [!IMPORTANT]
> Al primo accesso, il sistema potrebbe richiedere di configurare l'autenticazione a due fattori (2FA). Seguire le istruzioni a video scansionando il QR Code con l'app Authenticator.

---

## 3. Panoramica Interfaccia

L'interfaccia è divisa in tre aree principali:

*   **Barra Superiore (Header)**: Contiene il logo, il nome dell'utente connesso e il pulsante di Logout.
*   **Menu Laterale (Sidebar)**:
    *   `Dashboard`: Statistiche generali (Soci Totali, Morosi, Attivi).
    *   `Soci`: Elenco completo e ricerca.
    *   `Documenti`: Archivio file recenti.
*   **Area Centrale**: Dove vengono mostrati i dati e i moduli di lavoro.

---

## 4. Gestione Soci

Questa è la funzione principale del software.

### A. Ricerca di un Socio
1.  Cliccare su **Soci** nel menu.
2.  Nella barra "Cerca...", digitare **Cognome**, **Nome** o **Codice Fiscale**.
3.  Premere Invio o cliccare la lente d'ingrandimento.
4.  Il sistema mostrerà i risultati in tempo reale (< 50ms).

### B. Inserimento Nuovo Socio
1.  Cliccare su **+ Nuovo Socio** in alto a destra.
2.  Compilare i campi obbligatori indicati con asterisco (*):
    *   *Codice Fiscale* (verrà validato automaticamente).
    *   *Nome* e *Cognome*.
    *   *Data di Nascita*.
3.  Cliccare su **Salva**.
4.  Se l'operazione ha successo, si verrà reindirizzati alla scheda del nuovo socio.

### C. Modifica Dati
1.  Aprire la scheda del socio (dalla ricerca).
2.  Cliccare su **Modifica Dati**.
3.  Aggiornare le informazioni necessarie (es. cambio indirizzo).
4.  **Nota**: Il Codice Fiscale non è modificabile per integrità dati. Se errato, contattare l'amministratore.

### D. Stati del Socio
Ogni socio ha uno stato che determina i suoi diritti:
*   🟢 **ATTIVO**: Socio in regola.
*   🟡 **SOSPESO**: Socio non in regola con i pagamenti (Moroso).
*   🔴 **DECADUTO**: Socio non più facente parte dell'associazione.

---

## 5. Gestione Documentale

Il sistema permette di archiviare digitalmente moduli di iscrizione, privacy e documenti d'identità.

### Caricamento Documento
1.  Nella scheda del socio, scorrere fino alla sezione **Documenti**.
2.  Cliccare su **Carica File**.
3.  Selezionare il file dal PC (PDF o JPG, max 5MB).
4.  Specificare il tipo (es. "Modulo Iscrizione 2025").
5.  Confermare.

### Integrità e Sicurezza
Ogni file caricato viene "firmato" digitalmente dal sistema con un codice univoco (Hash SHA-256). Questo garantisce che il documento non possa essere alterato successivamente senza che il sistema se ne accorga.

---

## 6. Sicurezza e Privacy

### Logout Sicuro
Ricordarsi sempre di effettuare il **Logout** (icona in alto a destra) quando ci si allontana dalla postazione, specialmente se il PC è condiviso.

### Sessione
Per sicurezza, la sessione scade automaticamente dopo **30 minuti** di inattività. In tal caso, sarà necessario effettuare nuovamente il login.

### Supporto
Per problemi tecnici o reset password, contattare il Responsabile IT:
*   Email: `supporto@fratellanzamilitare.it`
*   Interno: 202

---
*Manuale generato automaticamente dal sistema di documentazione intelligente.*
