# Guida Completa ai Comandi Git Bash

Questa guida fornisce una spiegazione dettagliata dei comandi Git Bash fondamentali e delle procedure specifiche adottate per il progetto "Fratellanza Militare".

## Indice
1. [Concetti Base](#concetti-base)
2. [Navigazione e File System](#navigazione-e-file-system)
3. [Comandi Git Essenziali](#comandi-git-essenziali)
4. [Enterprise Workflow (Obbligatorio)](#enterprise-workflow-obbligatorio)

---

## 1. Concetti Base
**Git Bash** è un terminale che permette di eseguire comandi Git e comandi Unix-like su Windows.
- **Repository (Repo)**: La cartella del progetto tracciata da Git.
- **Branch**: Una linea di sviluppo parallela (es. `develop`, `main`, `feature/nuova-funzione`).
- **Commit**: Un salvataggio puntuale delle modifiche con un messaggio descrittivo.

## 2. Navigazione e File System
Comandi per muoversi tra le cartelle.

| Comando | Descrizione | Esempio |
| :--- | :--- | :--- |
| `ls` | Lista i file nella cartella corrente. | `ls` |
| `cd <cartella>` | Entra in una cartella. | `cd Documentazione` |
| `cd ..` | Torna alla cartella superiore. | `cd ..` |
| `pwd` | Mostra il percorso completo attuale. | `pwd` |
| `mkdir <nome>` | Crea una nuova cartella. | `mkdir NuovaCartella` |

## 3. Comandi Git Essenziali

### Configurazione
Verifica lo stato del repository.
```bash
git status
```
Mostra:
- Branch attuale.
- File modificati (rossi/verdi).
- File pronti per il commit.

### Branching
Crea e sposta il lavoro su nuovi branch.
```bash
# Lista tutti i branch locali
git branch

# Crea un nuovo branch e ci entra subito
git checkout -b nome-del-nuovo-branch

# Cambia branch esistente
git checkout nome-branch
```

### Salvataggio (Staging & Committing)
```bash
# 1. Aggiungi i file all'area di stage (preparazione)
git add nomefile.php    # Un solo file
git add .               # Tutti i file modificati

# 2. Crea il commit (salvataggio)
git commit -m "tipo(ambito): descrizione breve ma utile"
```
*Esempio messaggio*: `feat(auth): aggiunto login 2FA`

## 4. Enterprise Workflow (Obbligatorio)
Per questo progetto seguiamo un flusso rigoroso.

### A. Inizio Lavoro
Parti **SEMPRE** da `develop`.
```bash
git checkout develop
git checkout -b feature/nome-task
```

### B. Sviluppo
Modifica i file, salva con `git add` e `git commit`.

### C. Fase di Test (CRUCIALE)
Prima di chiudere, **DEVI** testare su un branch di test.
```bash
# Crea un branch di test temporaneo
git checkout -b tests/verifica-nome-task

# Unisci il tuo lavoro
git merge feature/nome-task

# Esegui i test automatici
vendor/bin/pest
```
*Se i test falliscono: correggi sul branch feature e ripeti il merge sul branch test.*

### D. Conclusione
Se tutto è verde (Test Passed):
1. Unisci su `develop` per condividere il lavoro.
```bash
git checkout develop
git merge feature/nome-task
```
git checkout develop
git merge feature/nome-task
```

2. **CHIUDERE IL BRANCH** (Archiviazione)
Dopo il merge, il branch rimane esistente ma "chiuso" (non attivo).
Per chiuderlo basta spostarsi su `develop` (fatto sopra).

3. **RIAPRIRE IL BRANCH**
Se in futuro devi rimettere mano a quel codice specifico:
```bash
git checkout feature/nome-task
```
*Non cancellare mai i branch feature.*

### E. Hotfix (Solo Emergenze)
Se c'è un bug critico in produzione (`stable`):
```bash
git checkout -b hotfix/descrizione-bug
# ... fix ...
# ... test ...
git checkout main
git merge hotfix/descrizione-bug
git checkout stable
git merge main
```
