# 🐳 Guida Completa a Docker - MCAG v2.0 Enterprise

Questa guida spiega passo passo come eseguire l'intera applicazione utilizzando **Docker**.
L'uso di Docker garantisce che l'app giri in un ambiente isolato, sicuro e identico per tutti gli sviluppatori, senza dover installare PHP o XAMPP sul tuo PC.

---

## 1. Prerequisiti

Prima di iniziare, assicurati di avere installato:
*   **Docker Desktop** (per Windows/Mac) o **Docker Engine** (Linux).
    *   [Scarica Docker Desktop qui](https://www.docker.com/products/docker-desktop/)

---

## 2. Struttura del Progetto

Il progetto è stato configurato per essere "Docker-Ready". Tutti i file di configurazione si trovano nella cartella `docker/`:

```text
MCAG_Militare-Civile-Archivio-Gestionale/
├── docker/
│   ├── config/              # Configurazioni (PHP, Nginx, Supervisor)
│   ├── Dockerfile           # Ricetta per costruire l'immagine
│   └── docker-compose.yml   # Orchestratore dei servizi
├── src/                     # Codice sorgente
├── public/                  # Entry point
└── ...
```

---

## 3. Avvio Rapido

### Passo 1: Spostati nella cartella Docker
Apri il tuo terminale (PowerShell, CMD, o Terminale VS Code) e vai nella cartella `docker`:

```bash
cd docker
```

### Passo 2: Avvia i Container
Esegui questo comando per costruire e avviare l'applicazione:

```bash
docker-compose up --build -d
```
*   `--build`: Forza la ricostruzione dell'immagine (utile se hai cambiato codice o requisiti).
*   `-d`: Detached mode (avvia in background, lasciando libero il terminale).

### Passo 3: Accedi all'Applicazione
Una volta avviato, apri il browser e vai su:

👉 **http://localhost:8080**

---

## 4. Comandi Utili

Ecco i comandi principali che userai spesso (da eseguire sempre dentro la cartella `docker/`):

| Azione | Comando | Descrizione |
| :--- | :--- | :--- |
| **Stop** | `docker-compose down` | Ferma e rimuove i container. |
| **Stop & Clean** | `docker-compose down -v` | Ferma tutto E cancella anche i volumi (non il database su file). |
| **Log in Tempo Reale** | `docker-compose logs -f` | Vedi cosa sta succedendo (errori PHP, accessi Nginx). |
| **Entra nel Container** | `docker-compose exec app sh` | Apre una shell dentro il container (come SSH). |
| **Riavvia** | `docker-compose restart` | Riavvia i servizi senza ricostruire. |

---

## 5. Dati e Persistenza

Non preoccuparti di perdere i dati quando spegni Docker. Abbiamo configurato i **Volumi** in modo che i dati importanti siano salvati sul tuo PC, non nel container:

*   **Database**: Il file `database.sqlite` nel tuo PC è sincronizzato con il container.
*   **Uploads**: La cartella `storage/` è sincronizzata.
*   **Logs**: La cartella `logs/` è sincronizzata.

Ogni modifica che fai al codice in VS Code si riflette **immediatamente** nel container (Hot Reload), senza dover riavviare.

---

## 6. Troubleshooting (Risoluzione Problemi)

### Porta 8080 Occupata?
Se ricevi un errore tipo *"Bind for 0.0.0.0:8080 failed: port is already allocated"*:
1.  Apri `docker/docker-compose.yml`.
2.  Cerca la sezione `ports:`.
3.  Cambia `"8080:80"` in `"8081:80"` (o un'altra porta libera).
4.  Riavvia con `docker-compose up -d`.

### Permessi File (Linux/Mac)
Su Windows non succede, ma su Linux potresti avere problemi di permessi. Esegui:
```bash
docker-compose exec app chown -R www-data:www-data /var/www/html/storage
```

### Clean Reset
Se qualcosa sembra "incastrato", prova un reset completo:
```bash
docker-compose down
docker system prune -f
docker-compose up --build
```

