# DATA PROCESSING AGREEMENT (DPA)

**Ai sensi dell'Art. 28 del Regolamento (UE) 2016/679 (GDPR)**

Tra:
**Il Cliente** (di seguito "Titolare del Trattamento")
e
**Il Fornitore Software** (di seguito "Responsabile del Trattamento")

## 1. PREMESSE E AMBITO DI APPLICAZIONE
Il presente Accordo di Trattamento Dati ("DPA") regola i termini secondo i quali il Responsabile tratterà i Dati Personali per conto del Titolare nell'ambito della fornitura del software MCAG System ("Servizi").

## 2. DURATA DEL TRATTAMENTO
La durata del trattamento corrisponde alla durata del Contratto di Servizio/Licenza principale. Al termine, i dati dovranno essere restituiti o cancellati secondo l'Art. 9.

## 3. NATURA E FINALITÀ DEL TRATTAMENTO
- **Natura**: Elaborazione, archiviazione, visualizzazione e backup di dati anagrafici associativi.
- **Finalità**: Erogazione del servizio di gestionale soci, supporto tecnico, manutenzione.
- **Tipo di Dati**: Anagrafiche (Nome, Cognome, CF, Indirizzo), Contatti (Email, Tel), Dati Amministrativi (Quote, Pagamenti), Documenti (PDF carta identità, moduli), Log di accesso.
- **Categorie Interessati**: Soci, Dipendenti, Collaboratori del Titolare.

## 4. OBBLIGHI DEL RESPONSABILE (Art. 28 GDPR)
Il Responsabile si impegna a:
1. **Istruzioni**: Trattare i dati solo su istruzione documentata del Titolare.
2. **Riservatezza**: Garantire che le persone autorizzate al trattamento (dipendenti, collaboratori) abbiano sottoscritto accordi di riservatezza.
3. **Sicurezza (Art. 32)**: Adottare tutte le misure tecniche e organizzative adeguate per garantire un livello di sicurezza commisurato al rischio.
4. **Sub-responsabili**: Non ricorrere a un altro responsabile senza previa autorizzazione scritta specifica o generale del Titolare.
5. **Diritti Interessati**: Assistere il Titolare con misure tecniche adeguate per soddisfare le richieste degli interessati (es. accesso, rettifica, oblio, portabilità).
6. **Assistenza**: Assistere il Titolare negli obblighi di sicurezza, notifica violazioni e valutazione di impatto (DPIA).
7. **Cancellazione**: Cancellare o restituire tutti i dati personali al termine del servizio.
8. **Audit**: Mettere a disposizione tutte le informazioni necessarie per dimostrare la conformità e consentire audit/ispezioni.

## 5. MISURE DI SICUREZZA (Allegato Tecnico)
Il Responsabile dichiara di implementare le seguenti misure minime (Software Standard MCAG):
- **Cifratura**: Dati sensibili criptati a riposo (AES-256) e in transito (TLS 1.2+).
- **Autenticazione**: Supporto nativo per autenticazione a due fattori (2FA/TOTP).
- **Access Control**: Sistema RBAC (Role Based Access Control) granulare.
- **Audit Logging**: Tracciamento immutabile di accessi e modifiche (Chi, Cosa, Quando).
- **Pseudonimizzazione**: Hashing degli indirizzi IP nei log di sistema.
- **Backup**: Procedure di backup automatizzato e test di ripristino regolari.

## 6. GESTIONE DATA BREACH (Art. 33)
Il Responsabile notificherà al Titolare qualsiasi violazione dei dati personali senza ingiustificato ritardo e comunque entro **48 ore** dalla conoscenza della stessa, fornendo dettagli su natura, conseguenze e misure correttive.

## 7. ELENCO SUB-RESPONSABILI AUTORIZZATI
Il Titolare autorizza i seguenti sub-responsabili (se SaaS):
1. **Hosting Provider**: [Inserire Nome, es. AWS/Vercel/Railway] - Infrastruttura Cloud.
2. **Email Provider**: [Inserire Nome, es. SMTP Server] - Invio notifiche.
*(In caso di installazione On-Premise, non vi sono sub-responsabili infrastrutturali del Fornitore)*.

## 8. TRASFERIMENTI EXTRA-UE
I dati saranno trattati esclusivamente all'interno dello Spazio Economico Europeo (SEE), salvo diversa pattuizione basata su Clausole Contrattuali Standard (SCC) approvate dalla Commissione UE.

---
**Per Accettazione**:

_________________________            _________________________
Il Titolare del Trattamento          Il Responsabile del Trattamento
(Firma e Data)                       (Firma e Data)

