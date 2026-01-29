# 🧪 Manual Verification Tutorial - MCAG v9.1 Enterprise

Questa guida ti accompagna passo-passo nella verifica manuale delle nuove funzionalità "Acceleration" implementate (Partner Hub, ERP, Global, Database).

---

## 🟢 1. Verifica Partner Dashboard & Sales Kit
**Obiettivo**: Confermare che il modulo rivenditori, il grafico trends e i download funzionino.

1.  **Login**: Accedi come Amministratore (es. `Admin / password`).
2.  **Navigazione**: Dal menu utente (in alto a destra), clicca su **Partner Dashboard** (oppure vai a `/partner/dashboard`).
3.  **Check Grafico**:
    *   Guarda il riquadro **"Growth Trend"** (ultimo a destra).
    *   [ ] Verifica che ci sia un grafico a linea curva (viola/indaco).
    *   [ ] Passa il mouse sopra i punti: deve apparire il tooltip con il valore in Euro (es. `€ 5900`).
4.  **Check Download**:
    *   Trova la card gialla **"Reseller Sales Kit"**.
    *   Clicca su **"Scarica Pitch Deck"**.
    *   [ ] Verifica che si apra/scarichi il file `MCAG_PARTNER_PITCH_2026.md`.
5.  **Check Loop Login**:
    *   Ricarica la pagina. Clicca di nuovo sul logo o naviga avanti/indietro.
    *   [ ] Verifica di RYANERE dentro la dashboard senza essere buttato fuori al Login (Fix Sessione applicato).

---

## 🌍 2. Verifica Motore Globale (Traduzioni)
**Obiettivo**: Verificare il selettore lingua e la struttura i18n.

1.  **Header**: Guarda la barra di navigazione in alto.
2.  **Selettore**: Trova l'icona del Mappamondo (🌐) o la bandierina "IT".
3.  **Azione**:
    *   Clicca sul menu.
    *   Seleziona **"English (Beta)"**.
4.  **Verifica**:
    *   Osserva l'URL: dovrebbe appendere `?lang=en`.
    *   [ ] Verifica che il sistema non dia errori (Nota: la traduzione effettiva delle stringhe dipende dal file `lang/en.json` generato).

---

## 🔌 3. Verifica Connettori ERP (Visual Status)
**Obiettivo**: Confermare che il sistema riconosca i driver Enterprise (Zucchetti, SAP, Odoo).

1.  **Navigazione**: Vai a **Report Center** (nel menu principale) o `/statistiche`.
2.  **Pannello Admin**:
    *   Se sei Admin, vedrai un blocco scuro in alto chiamato **"Enterprise Integrations"**.
3.  **Check Indicatori**:
    *   [ ] **Zucchetti**: Badge Verde "CONNECTED (SOAP)".
    *   [ ] **Odoo**: Badge Verde "CONNECTED (XML-RPC)".
    *   [ ] **SAP B1**: Badge Verde "CONNECTED (OData)".
    *   [ ] **Database**: Badge Azzurro "HYBRID" (MySQL/PgSQL).

---

## 💾 4. Verifica Database Independence (Codice)
**Obiettivo**: Verificare che il codice sia pronto per PostgreSQL.

1.  **DevTools**: Dal menu utente, clicca su **DevTools Hub**.
2.  **File Editor**: Vai alla sezione "File System" (o usa il tuo editor).
3.  **Apri File**: `src/InfrastrutturaIT/Persistence/DatabaseConnection.php`.
4.  **Cerca**: Vai alla riga **64-75**.
5.  **Verifica**:
    *   [ ] Leggi: `if ($driver === 'pgsql')`.
    *   Questo conferma che il sistema può cambiare motore semplicemente modificando il file `.env`.

---

## ✅ Checklist Finale
Se hai spuntato tutte le caselle sopra, la versione **v9.1.0** è:
*   Visualmente completa.
*   Funzionalmente stabile.
*   Pronta per il rilascio.
