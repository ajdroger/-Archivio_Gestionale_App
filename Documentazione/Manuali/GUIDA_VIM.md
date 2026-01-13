# Guida Completa a VIM per Sviluppatori Mission-Critical

> **Versione**: 1.0  
> **Destinatari**: Sviluppatori Backend/DevOps  
> **Obiettivo**: Padronanza dell'editing modale per interventi rapidi su server remoti e coding efficiente.

---

## 1. Filosofia e Concetti Base
Vim (Vi IMproved) è un editor **modale**. A differenza degli editor classici (Notepad, VS Code), in Vim i tasti fanno cose diverse a seconda della "modalità" in cui ti trovi.

### Le 4 Modalità Principali
1.  **Normal Mode (ESC)**: La modalità di default. I tasti servono per muoversi, cancellare, copiare. Non scrivi testo qui, operi sul testo.
2.  **Insert Mode (i)**: La modalità classica "Notepad". I tasti scrivono caratteri.
3.  **Visual Mode (v)**: Per selezionare blocchi di testo (come fare click-and-drag col mouse).
4.  **Command Mode (:)**: Per salvare, uscire, cercare, configurare.

---

## 2. Navigazione (Normal Mode)
Dimentica le frecce direzionali. Usa la "Home Row".

### Movimento Base
| Tasto | Azione | Mnemotecnica |
| :--- | :--- | :--- |
| `h` | Sinistra | (Tasto a sx della mano) |
| `j` | Giù | (J scende come un amo) |
| `k` | Su | (K sale come il Re/King) |
| `l` | Destra | (Tasto a dx della mano) |

### Movimento Rapido
| Tasto | Azione |
| :--- | :--- |
| `w` | Salta all'inizio della **prossima parola** (Word) |
| `b` | Salta all'inizio della **parola precedente** (Back) |
| `e` | Salta alla **fine** della parola corrente (End) |
| `0` | Vai a **inizio riga** assoluto |
| `^` | Vai al primo carattere **non vuoto** della riga |
| `$` | Vai a **fine riga** |

### Navigazione nel File
| Tasto | Azione |
| :--- | :--- |
| `gg` | Vai alla **prima riga** del file |
| `G` | Vai all'**ultima riga** del file |
| `:n` | Vai alla riga numero `n` (es. `:42`) |
| `Ctrl+u` | Scorri su mezza pagina (Up) |
| `Ctrl+d` | Scorri giù mezza pagina (Down) |

---

## 3. Editing e Inserimento

### Entrare in Insert Mode
| Tasto | Dove inizia a scrivere? |
| :--- | :--- |
| `i` | **Prima** del cursore (Insert) |
| `a` | **Dopo** il cursore (Append) |
| `I` | A **inizio riga** |
| `A` | A **fine riga** (utilissimo!) |
| `o` | Apre una **nuova riga sotto** e entra in insert (Open) |
| `O` | Apre una **nuova riga sopra** e entra in insert |

### Cancellare e Modificare (Normal Mode)
| Tasto | Azione |
| :--- | :--- |
| `x` | Cancella il carattere sotto il cursore |
| `dd` | Cancella (taglia) l'intera riga |
| `dw` | Cancella fino alla fine della parola |
| `d$` | Cancella fino a fine riga |
| `u` | **Annulla** l'ultima modifica (Undo) |
| `Ctrl+r` | **Ripristina** l'annullamento (Redo) |
| `r` | Sostituisce un singolo carattere |

### Copia e Incolla (Yank & Put)
Vim usa i "registri". Quando cancelli (`d`), in realtà stai tagliando.
| Tasto | Azione |
| :--- | :--- |
| `yy` | Copia l'intera riga (Yank) |
| `yw` | Copia la parola corrente |
| `p` | Incolla **dopo** il cursore (Put) |
| `P` | Incolla **prima** del cursore |

---

## 4. Ricerca e Sostituzione

### Ricerca nel file
| Comando | Azione |
| :--- | :--- |
| `/testo` | Cerca "testo" in avanti |
| `?testo` | Cerca "testo" all'indietro |
| `n` | Vai al risultato successivo (Next) |
| `N` | Vai al risultato precedente |
| `*` | Cerca la parola sotto il cursore in avanti |
| `#` | Cerca la parola sotto il cursore indietro |

### Sostituzione (Find & Replace)
Sintassi: `:%s/vecchio/nuovo/flags`
*   `:` Entra in command mode
*   `%` Applica a tutto il file (senza `%` solo alla riga corrente)
*   `s` Substitute

Esempi:
*   `:%s/foo/bar/g` : Sostituisce tutti i "foo" con "bar" globalmente.
*   `:%s/foo/bar/gc` : Sostituisce globalmente ma **chiede conferma** ogni volta (Confirm).

---

## 5. Salvataggio e Uscita
Tutto si fa dalla Command Mode (premi `:`).

| Comando | Azione |
| :--- | :--- |
| `:w` | Salva il file (Write) |
| `:q` | Esci (Quit) |
| `:wq` | Salva ed esci |
| `:x` | Salva ed esci (stessa cosa di wq) |
| `:q!` | Esci **senza salvare** (forza l'uscita) |
| `:w nomefile` | Salva con nome (Save As) |

---

## 6. Visual Mode (Selezione)
Premi `v` in Normal Mode. Muoviti con `h/j/k/l`.
*   `d` : Cancella la selezione.
*   `y` : Copia la selezione.
*   `>` : Indenta a destra la selezione.
*   `<` : Indenta a sinistra.
*   `=` : Auto-indenta il blocco selezionato.

**Visual Line Mode (`V`)**: Seleziona intere righe.
**Visual Block Mode (`Ctrl+v`)**: Seleziona colonne (utilissimo per editare elenchi puntati verticali).

---

## 7. Comandi Power User (Meticolosi)

### Operatori + Movimenti
La potenza di Vim sta nel combinare Verbi (d, c, y) con Nomi (w, $, }).
*   `ciw` (Change Inner Word): Cancella la parola sotto il cursore e entra in Insert mode. **Il comando più usato dai pro.**
*   `ct;` (Change Till ;): Cancella tutto fino al prossimo `;` e entra in Insert.
*   `dt"` (Delete Till "): Cancella fino alle virgolette.
*   `ci(` (Change Inner Parenthesis): Cancella tutto ciò che sta dentro le parentesi `(...)` e ti fa scrivere.

### Split Screen
| Comando | Azione |
| :--- | :--- |
| `:sp` | Divide lo schermo orizzontalmente (Split) |
| `:vsp` | Divide lo schermo verticalmente (Vertical Split) |
| `Ctrl+w` poi `Navigazione` | Sposta il cursore tra i pannelli (es. `Ctrl+w` poi `l` va a destra) |

### Macros (Automazione)
Per ripetere azioni complesse N volte.
1.  Premi `q` seguito da una lettera (es. `a`) per iniziare a registrare nella macro 'a'.
2.  Esegui le azioni.
3.  Premi `q` per fermare la registrazione.
4.  Premi `@a` per rieseguire la macro.
5.  Premi `10@a` per eseguirla 10 volte.

---

## 8. Trasformare VIM in un IDE Moderno (.vimrc)

Per ottenere un'esperienza simile a VS Code, crea/modifica il file `~/.vimrc` con questa configurazione "Ultimate":

```vim
" --- CORE SETTINGS ---
set nocompatible            " Disabilita compatibilità Vi legacy (fondamentale)
filetype plugin indent on   " Abilita rilevamento avanzato tipo file

" --- UI & VISUALS ---
syntax on                   " Attiva evidenziazione sintassi
set number relativenumber   " Numeri Ibridi: riga corrente assoluta, altre relative (top per i salti)
set cursorline              " Evidenzia la riga dove si trova il cursore
set termguicolors           " Abilita colori TrueColor (24-bit) se il terminale supporta
set scrolloff=8             " Mantiene 8 righe di margine sopra/sotto quando scorri
set signcolumn=yes          " Colonna sinistra sempre visibile (evita flickr con git/lint)
set cmdheight=1             " Altezza riga comandi

" --- INDENTAZIONE & SPAZI ---
set expandtab               " Trasforma TAB in Spazi (Standard moderno)
set tabstop=4               " Visualizza TAB come 4 spazi
set shiftwidth=4            " Indentazione automatica di 4 spazi
set smartindent             " Capisce quando indentare (dopo {, if, ecc)
set nowrap                  " Non andare a capo automaticamente (meglio per codice)

" --- RICERCA ---
set ignorecase              " Ignora maiuscole nella ricerca...
set smartcase               " ...a meno che tu non scriva una maiuscola
set incsearch               " Cerca ed evidenzia mentre digiti
set hlsearch                " Mantieni evidenziati i risultati
" Premi F3 per pulire l'evidenziazione ricerca
nnoremap <F3> :noh<CR>

" --- SYSTEM INTEGRATION ---
set clipboard=unnamedplus   " Usa la clipboard di sistema! Copia in Vim -> Incolla fuori (e viceversa)
set mouse=a                 " Abilita il mouse per scroll e selezione (sì, si può!)
set undofile                " Persistenza Undo: puoi fare Undo anche dopo aver chiuso e riaperto il file
set undodir=~/.vim/undo     " Crea questa cartella: mkdir -p ~/.vim/undo
set updatetime=50           " Aggiornamento UI rapido (utile per git plugins)

" --- MAPPATURE MODERN (Quality of Life) ---
let mapleader = " "         " Imposta SPAZIO come tasto Leader (il tasto magico)

" Salvataggio rapido: Spazio + w
nnoremap <Leader>w :w<CR>
" Uscita rapida: Spazio + q
nnoremap <Leader>q :q<CR>

" Spostarsi tra finestre (Split) come un ninja usando Ctrl + hjkl
nnoremap <C-h> <C-w>h
nnoremap <C-j> <C-w>j
nnoremap <C-k> <C-w>k
nnoremap <C-l> <C-w>l

" Spostare righe su/giù con Alt+j/k (come VS Code)
nnoremap <M-j> :m .+1<CR>==
nnoremap <M-k> :m .-2<CR>==
inoremap <M-j> <Esc>:m .+1<CR>==gi
inoremap <M-k> <Esc>:m .-2<CR>==gi
```

## 9. Plugin Consigliati (Vim-Plug)
Per installare plugin, scarica [vim-plug](https://github.com/junegunn/vim-plug) e aggiungi questo al tuo `.vimrc`:

```vim
call plug#begin()
    " File Explorer (albero laterale)
    Plug 'preservim/nerdtree'
    
    " Status Bar figa
    Plug 'vim-airline/vim-airline'
    
    " Ricerca file (CTRL+P)
    Plug 'junegunn/fzf', { 'do': { -> fzf#install() } }
    
    " Git integration
    Plug 'airblade/vim-gitgutter'
    
    " Autocomplete
    Plug 'neoclide/coc.nvim', {'branch': 'release'}
call plug#end()
```

> **Nota**: Se vuoi il salto di qualità definitivo, considera **Neovim** (`nvim`), che è un fork moderno di Vim ottimizzato per la velocità e l'estensibilità con Lua.


