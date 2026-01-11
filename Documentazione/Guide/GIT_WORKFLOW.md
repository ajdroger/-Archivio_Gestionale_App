# 🔀 Strategia di Branching Git (Gitflow Model)
**Strategia Operativa per Fratellanza Militare - Archivio Digitale**

In seguito all'analisi del modello "A successful Git branching model" (Vincent Driessen), adottiamo questa strategia per garantire un flusso di lavoro professionale, stabile e ordinato, anche in un contesto "Single Developer".

## 🌳 Panoramica dei Rami (Branches)

### 1. Rami Principali
Questi due rami esistono sempre:

*   **`main`** (STABLE / PRODUCTION): **Sacro**. Contiene SOLO codice certificato, testato e pronto per il cliente. Nessun commit diretto. Solo merge da Release/Hotfix.
*   **`develop`** (NEXT / BETA): Il ramo di integrazione continuo. Qui arrivano le feature completate.
*   **`feature/tests`** (QUALITY GATE): Branch perenne o creato ad hoc prima di ogni release. **OBBLIGATORIO**: La suite di test deve passare qui al 100% prima di qualsiasi merge verso Stable.

### 2. Rami di Supporto (Temporanei)
Utilizzati per scopi specifici e poi rimossi dopo il merge.

*   **`feature/*`** (Nuove Funzionalità)
    *   **Parte DA**: `develop`
    *   **Torna IN**: `develop`
    *   **Naming**: `feature/nome-funzionalita` (es. `feature/login-2fa`)
    *   *Scopo*: Sviluppare nuove feature in isolamento senza instabilizzare `develop`.

*   **`release/*`** (Preparazione Rilascio)
    *   **Parte DA**: `develop`
    *   **Torna IN**: `develop` E `main`
    *   **Naming**: `release/vX.Y` (es. `release/v1.2`)
    *   *Scopo*: Congelare il codice per una release, permettendo solo bugfix minori e aggiornamenti di versione/changelog.

*   **`hotfix/*`** (Bugfix Critici in Produzione)
    *   **Parte DA**: `main`
    *   **Torna IN**: `main` E `develop`
    *   **Naming**: `hotfix/descrizione-fix` (es. `hotfix/fix-login-error`)
    *   *Scopo*: Risolvere bug critici scoperti in produzione senza aspettare la prossima release pianificata.

---

## 🛠️ Workflow Operativo (Single Developer)

Essendo l'unico sviluppatore, non abbiamo bisogno di Pull Requests complesse, ma seguiremo rigidamente la struttura dei branch per mantenere l'ordine.

### A. Iniziare una nuova Funzionalità
1.  Assicurarsi di essere aggiornati: `git checkout develop && git pull`
2.  Creare il branch: `git checkout -b feature/nuova-funzionalita`
3.  Sviluppare e committare.
4.  Al termine:
    ```bash
    git checkout develop
    git merge --no-ff feature/nuova-funzionalita
    # git branch -d feature/nuova-funzionalita  <-- NON ELIMINARE: I branch feature vengono mantenuti per storico/manutenzione
    ```
    *(L'uso di `--no-ff` crea un nodo di commit esplicito per la feature. Il branch non viene eliminato per mantenere traccia del lavoro svolto)*

### B. Creare una Release (es. v1.2)
1.  Quando `develop` è pronto: `git checkout -b release/v1.2 develop`
2.  Aggiornare numeri di versione, changelog.
3.  Fix minori se necessari.
4.  Terminare la release:
    ```bash
    # Merge su main
    # Merge su main (STABLE)
    git checkout main
    git merge --no-ff release/v1.2
    git tag -a v1.2 -m "Enterprise Stable Release 1.2"

    # Allinea develop
    git checkout develop
    git merge --no-ff release/v1.2

    # Pulizia
    git branch -d release/v1.2
    ```

### C. Gestire un Hotfix
1.  Bug critico su `main`: `git checkout -b hotfix/fix-critico main`
2.  Riparare il bug.
3.  Terminare l'hotfix:
    ```bash
    # Merge su main
    git checkout main
    git merge --no-ff hotfix/fix-critico
    git tag -a v1.2.1 -m "Hotfix 1.2.1"

    # Riportare fix su develop
    git checkout develop
    git merge --no-ff hotfix/fix-critico

    # Pulizia
    git branch -d hotfix/fix-critico
    ```

---

## 🚀 Stato Iniziale del Repository (Setup)

Per avviare questo modello nel progetto attuale:

1.  Consideriamo l'attuale branch **`main`** come la produzione stabile.
2.  Verrà creato immediatamente il branch **`develop`** a partire da main.
3.  Tutto il nuovo sviluppo avverrà su branch `feature` che partiranno da `develop`.

---

## 📜 Coding Standards & Definition of Done

Per garantire "Historical Rigor" e manutenibilità Enterprise, ogni commit DEVE rispettare **ADR-028**:

1.  **Separazione Netta**:
    *   ⛔ **MAI** Inline CSS (`<style>`) o Inline JS (`<script>`) in file PHP/HTML.
    *   ⛔ **MAI** Logica PHP complessa dentro le View.
    *   ✅ Usa file `.css`, `.js`, `.json` separati.
2.  **Commenti & Chiarezza**:
    *   Il codice deve essere auto-esplicativo.
    *   Aggiungere DocBlock a Classi e Metodi.
    *   Commentare logiche complesse ("Why", not "What").
3.  **Commit Message**:
    *   Formato: `type(scope): subject` (es. `feat(auth): implement 2fa strict check`).

