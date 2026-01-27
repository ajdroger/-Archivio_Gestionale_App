# 📚 ONBOARDING MANUAL MCAG
## Guida Onboarding Nuovi Utenti

**Versione**: 1.0
**Data**: 27 Gennaio 2026

---

## BENVENUTO IN MCAG!

Questa guida ti accompagnerà nei primi passi con MCAG. In **30 minuti** imparerai le funzionalità base.

---

## 1. PRIMO ACCESSO

### Login
1. Apri browser: `https://your-domain.mcag.it`
2. Inserisci credenziali ricevute via email
3. **Primo accesso**: Verrà richiesto cambio password

### Setup 2FA (Obbligatorio)
1. Scarica **Google Authenticator** (iOS/Android)
2. Scansiona QR code mostrato
3. Inserisci codice 6 cifre per conferma
4. **Salva recovery codes** (10 codici backup)

---

## 2. DASHBOARD OVERVIEW

**Layout**:
```
┌─────────────────┬──────────────────┐
│  Sidebar        │   Main Content   │
│                 │                  │
│  - Dashboard    │   Cards/Stats    │
│  - Anagrafica   │                  │
│  - Documenti    │   Quick Actions  │
│  - Workshift    │                  │
│  - Settings     │                  │
└─────────────────┴──────────────────┘
```

**Key Metrics** (Dashboard):
- Total soci
- Soci attivi (ultimi 30gg)
- Documenti in scadenza
- Turni oggi (se Workshift attivo)

---

## 3. GESTIONE SOCI (CRUD Basics)

### Creare Nuovo Socio
1. Sidebar → **Anagrafica Soci**
2. Click **+ Nuovo Socio**
3. Compila form:
   - Nome, Cognome *(required)*
   - Email *(required, unique)*
   - Codice Fiscale *(auto-calcolato se fornisci dati nascita)*
   - Data Nascita
   - Indirizzo, Telefono
4. Click **Salva**

### Ricerca Soci
- Barra search: Digita nome/cognome/email
- Filtri avanzati: Click **Filtri** → Seleziona status, data iscrizione, etc.

### Modifica Socio
1. Trova socio (search)
2. Click su riga → Dettaglio
3. Click **Modifica** (icona matita)
4. Aggiorna campi
5. **Salva**

### Eliminare Socio
- Click **Elimina** (icona cestino)
- Conferma azione
- **Nota**: Soft delete (record flaggato, non rimosso)

---

## 4. DOCUMENT VAULT

### Upload Documento
1. Vai su profilo Socio
2. Tab **Documenti**
3. Click **+ Upload**
4. Seleziona file (PDF, DOC, JPG max 10MB)
5. Scegli **Tipo Documento** (Contratto, Certificato, Altro)
6. Click **Upload**

**Encryption**: Tutti i file sono criptati AES-256 at-rest

### Download Documento
1. Lista documenti socio
2. Click icona **Download**
3. File decrypt automatico, download starts

### Scadenza Documenti
- Imposta **Data Scadenza** al momento upload
- Sistema invia **reminder automatici** (30/15/7 giorni prima)

---

## 5. WORKSHIFT COMMANDER (Turni)

### Creare Turno
1. Sidebar → **Workshift**
2. Click **+ Nuovo Turno**
3. Seleziona:
   - Dipendente (dropdown)
   - Data  
   - Ora Inizio/Fine
   - Ruolo (se applicabile)
4. **Salva**

### Calendario Turni
- Vista **Mensile**: Overview mese
- Vista **Settimanale**: Dettaglio 7 giorni
- Vista **Giornaliera**: Tutti turni oggi

### AI Optimizer (Opzionale)
1. Click **Ottimizza Turni**
2. Sistema analizza:
   - Disponibilità dipendenti
   - Ore lavorate cumulative
   - Skills/ruoli  
3. Ricevi **suggerimenti** automatici
4. Applica o ignora suggerimenti

---

## 6. PROFILO UTENTE

### Modificare Dati Profilo
1. Click **Avatar** (top-right)
2. **Impostazioni Profilo**
3. Aggiorna Nome, Email, Password
4. **Salva**

### Preferenze
- **Lingua**: Italiano/English
- **Timezone**: Europe/Rome
- **Notifiche**: Email sì/no

---

## 7. SHORTCUTS TASTIERA

| Shortcut | Azione |
|----------|--------|
| `Ctrl+K` | Quick search (global) |
| `Ctrl+N` | Nuovo Socio |
| `Ctrl+S` | Salva form corrente |
| `Esc` | Chiudi modal |
| `/` | Focus search bar |

*(Mac: Sostituisci Ctrl con Cmd)*

---

## 8. SUPPORTO

### Help Center
- Click **?** icon (top-right)
- FAQ, video tutorials, guide

### Contattare Support
- **Email**: support@mcag.it (response < 12h business hours)
- **Phone**: +39-XXX-XXXXXXX (lun-ven 9-18)
- **Ticket**: Dashboard → Help → **Apri Ticket**

---

## 9. BEST PRACTICES

✅ **Backup dati**: Sistema fa backup automatici, ma esporta CSV periodicamente se vuoi copia locale  
✅ **Password forte**: Min 12 caratteri, mix upper/lower/numbers/symbols  
✅ **2FA sempre attivo**: Non disabilitare mai  
✅ **Logout shared computer**: Se usi PC condiviso, sempre logout  
✅ **Aggiorna browser**: Chrome/Firefox/Edge ultima versione  

---

## 10. NEXT STEPS

**Week 1**: Pratica CRUD soci giornaliero  
**Week 2**: Upload documenti, imposta scadenze  
**Week 3**: Crea turni workshift  
**Week 4**: Esplora report/statistiche avanzate

**Advanced Training** (opzionale): Contatta training@mcag.it per sessioni avanzate (API, custom fields, etc.)

---

## CONCLUSIONE

Congratulazioni! Hai completato l'onboarding MCAG basic. Continua a esplorare - il sistema è intuitivo.

**Happy Managing! 🚀**

**© 2026 Soobadur Mohammad Ajmeer**
