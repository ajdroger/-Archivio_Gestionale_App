# Guida Completa alla Git Shell per Sviluppatori Mission-Critical

> **Versione**: 1.0  
> **Destinatari**: Development Team  
> **Obiettivo**: Padronanza assoluta della Command Line Interface (CLI) di Git per gestire il versionamento con precisione chirurgica.

---

## 1. Configurazione Iniziale (Setup)
Prima di iniziare, è fondamentale configurare la propria identità e le preferenze.

| Comando | Descrizione |
| :--- | :--- |
| `git config --global user.name "Nome Cognome"` | Imposta il nome utente per i commit. |
| `git config --global user.email "email@example.com"` | Imposta l'email (deve corrispondere a GitHub). |
| `git config --global core.editor "vim"` | Imposta l'editor di default (es. Vim, Nano, Code). |
| `git config --global init.defaultBranch main` | Imposta `main` come nome default per i nuovi repo. |
| `git config --list` | Mostra tutta la configurazione attuale. |

### Alias Utili (Productivity Boost)
Per velocizzare il lavoro, configura questi alias nel tuo `.gitconfig`:
```bash
git config --global alias.co checkout
git config --global alias.br branch
git config --global alias.ci commit
git config --global alias.st status
git config --global alias.lg "log --graph --abbrev-commit --decorate --format=format:'%C(bold blue)%h%C(reset) - %C(bold green)(%ar)%C(reset) %C(white)%s%C(reset) %C(dim white)- %an%C(reset)%C(bold yellow)%d%C(reset)' --all"
```
*Ora puoi usare `git lg` per vedere un grafo storico bellissimo.*

---

## 2. Workflow Quotidiano (The Cycle)
Il ciclo di vita base di ogni modifica.

### Status e Staging
| Comando | Descrizione |
| :--- | :--- |
| `git status` | **Il comando più importante.** Mostra cosa è cambiato. |
| `git add <file>` | Aggiunge un file specifico alla Staging Area. |
| `git add .` | Aggiunge **tutti** i file modificati (nella cartella corrente). |
| `git diff` | Mostra le differenze tra Working Directory e Staging. |
| `git diff --staged` | Mostra le differenze tra Staging e l'ultimo Commit (cosa stai per committare). |

### Committing
| Comando | Descrizione |
| :--- | :--- |
| `git commit -m "messaggio"` | Crea un commit con un messaggio breve. |
| `git commit -am "messaggio"` | Aggiunge (file già tracciati) e committa in un colpo solo. |
| `git commit --amend` | Modifica l'ultimo commit (utile se hai scordato un file o sbagliato il messaggio). **Non fare mai push prima!** |

---

## 3. Branching & Merging (Strategia)
Git brilla nella gestione dei branch.

### Gestione Branch
| Comando | Descrizione |
| :--- | :--- |
| `git branch` | Lista i branch locali. |
| `git branch -a` | Lista tutti i branch (locali e remoti). |
| `git branch <nome>` | Crea un nuovo branch. |
| `git checkout <nome>` | Passa al branch specificato (Legacy). |
| `git switch <nome>` | Passa al branch specificato (Moderno). |
| `git checkout -b <nome>` | Crea E passa al nuovo branch. |
| `git branch -d <nome>` | Cancella un branch (se già mergiato). |
| `git branch -D <nome>` | Forza la cancellazione di un branch (anche se non mergiato). |

### Merging (Unire il lavoro)
| Comando | Descrizione |
| :--- | :--- |
| `git merge <branch>` | Unisce il branch specificato in quello corrente. |
| `git merge --no-ff <branch>` | **Consigliato**. Crea sempre un commit di merge (preserva la storia del branch). |
| `git merge --abort` | Annulla un merge in caso di conflitti ingestibili. |

---

## 4. Sincronizzazione Remota (Remote)
Lavorare con GitHub/GitLab.

| Comando | Descrizione |
| :--- | :--- |
| `git remote -v` | Mostra i repository remoti collegati. |
| `git fetch` | Scarica le modifiche dal remoto MA non le unisce. |
| `git pull` | Scarica E unisce le modifiche (`fetch` + `merge`). |
| `git push` | Carica i tuoi commit sul remoto. |
| `git push -u origin <branch>` | Carica il branch e imposta il tracking upstream (la prima volta). |

---

## 5. Operazioni Avanzate (Pro Tools)

### Stash (Il cassetto temporaneo)
Utile quando devi cambiare branch ma hai lavoro a metà.
| Comando | Descrizione |
| :--- | :--- |
| `git stash` | Salva le modifiche temporanee e pulisce la directory. |
| `git stash pop` | Ripristina le ultime modifiche salvate e le rimuove dallo stash. |
| `git stash list` | Mostra tutti gli stash salvati. |

### Rebase (Riscrivere la storia)
**Attenzione:** Mai fare rebase su branch condivisi/pubblici.
| Comando | Descrizione |
| :--- | :--- |
| `git rebase <base>` | Scrive i tuoi commit sopra quelli della base (storia lineare). |
| `git rebase -i HEAD~3` | **Interactive**. Permette di unire (squash), modificare o riordinare gli ultimi 3 commit. |

### Cherry Pick (Il cecchino)
| Comando | Descrizione |
| :--- | :--- |
| `git cherry-pick <hash>` | Prende un singolo commit specifico da un altro branch e lo applica qui. |

---

## 6. Undo & Recovery (Sicurezza)
Come rimediare ai disastri.

| Comando | Descrizione |
| :--- | :--- |
| `git checkout -- <file>` | Scarta le modifiche locali a un file (torna all'ultimo commit). |
| `git reset HEAD <file>` | Rimuove un file dalla Staging Area (unstage). |
| `git reset --soft HEAD~1` | Annulla l'ultimo commit ma mantiene le modifiche in Staging. |
| `git reset --hard HEAD~1` | **Pericoloso**. Annulla commit e modifiche. Torna indietro nel tempo. |
| `git revert <hash>` | Crea un NUOVO commit che è l'opposto di quello specificato (Safe Undo). |
| `git reflog` | **Salva-vita**. Mostra il log di TUTTI i movimenti della HEAD, anche quelli cancellati con reset. Permette di recuperare branch o commit persi. |

---

## 7. Convenzioni Commit Message (Semantic)
Per mantenere un changelog pulito.

*   `feat: ...` : Nuova funzionalità
*   `fix: ...` : Correzione bug
*   `docs: ...` : Modifiche documentazione
*   `style: ...` : Formattazione (spazi, virgole, no logica)
*   `refactor: ...` : Modifica codice (né fix né feat)
*   `test: ...` : Aggiunta/Modifica test
*   `chore: ...` : Manutenzione build/tool

---

## 8. Visualizzazione Grafica & GUI (Tools)
Sebbene la CLI sia imbattibile per velocità, una GUI è superiore per visualizzare la complessità della storia.

### Software Professionali (GUI Clients)
Strumenti scaricabili per navigare graficamente il repository.

| Software | Link | Descrizione |
| :--- | :--- | :--- |
| **GitKraken** | [Scarica qui](https://www.gitkraken.com/) | **Il migliore per visualizzazione.** Grafo bellissimo, drag-and-drop merge/rebase. (Freemium) |
| **SourceTree** | [Scarica qui](https://www.sourcetreeapp.com/) | Classico, gratuito (Atlassian). Molto robusto per workflow complessi. |
| **Sublime Merge** | [Scarica qui](https://www.sublimemerge.com/) | Velocissimo, stessa interfaccia di Sublime Text. |
| **GitHub Desktop** | [Scarica qui](https://desktop.github.com/) | Semplice e pulito, integrazione perfetta con GitHub. |

### Web Tools & Estensioni
Per visualizzare la storia direttamente online o nell'editor.

*   **VS Code Git Graph**: [Extension Marketplace](https://marketplace.visualstudio.com/items?itemName=mhutchie.git-graph). **Consigliatissimo**. Aggiunge un tab "Git Graph" in VS Code che sostituisce molti client esterni.
*   **GitHub Network Graph**: Vai sul repo GitHub -> tab *Insights* -> *Network*. Mostra tutti i branch e fork in tempo reale.
*   **Learn Git Branching**: [Visualizer Online](https://learngitbranching.js.org/). Ottimo per simulare e capire visivamente come funzionano i comandi prima di eseguirli.
*   **Visualizing Git**: [Git School](https://git-school.github.io/visualizing-git/). Sandbox interattiva per visualizzare l'effetto dei comandi sul grafo.

---
Compilato per **MCAG - Archivio Digitale**

