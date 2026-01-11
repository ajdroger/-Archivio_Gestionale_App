
# 3. Metodologia Gitflow Rigorosa

Il progetto ha seguito un flusso di sviluppo professionale, garantendo stabilità in produzione e isolamento delle nuove feature.

<!-- IMMAGINE GITFLOW -->
![w:850](Documentazione/Architettura/Images_Diagram_Classe_flusso_git/diagram-git-brunching-2026-01-11-113332.png)

*   **Main:** Codice stabile "Gold Master".
*   **Develop:** Integrazione feature testate.
*   **Feature Branches:** Sviluppo isolato (es. `feature/devtools-v4`).

---

# 4. Architettura Enterprise (Clean Arch)

Struttura modulare che separa Logica, Dati e Infrastruttura (Dockerized).

<!-- IMMAGINE CLASSI -->
![w:850](Documentazione/Architettura/Images_Diagram_Classe_flusso_git/diagram-class.png)

*   **DevTools:** Modulo amministrativo isolato (Web Terminal, DB Manager).
*   **Presentation:** API REST e GraphQL coesistenti.
*   **Infrastructure:** MySQL Cluster e ProxySQL per alta affidabilità.

---

# 5. Flusso Operativo Sicuro

Il ciclo di vita di una richiesta, dalla 2FA alla transazione ACID sul Database.

<!-- IMMAGINE FLUSSO -->
![w:800](Documentazione/Architettura/Images_Diagram_Classe_flusso_git/diagram-flusso-2026-01-11-114113.png)

1.  **Security Gate:** Rate Limit, CSRF, 2FA Check.
2.  **Logic:** Validazione Input e Crittografia.
3.  **Persistenza:** Transazione Atomica (Commit o Rollback).

---