---
marp: true
theme: gaia
class: lead
backgroundColor: #ffffff
paginate: true
header: 'Fratellanza Militare | Archivio Mission-Critical v1.3.1'
footer: 'Soobadur Mohammad Ajmeer - Lead Architect | 21/12/2025'

style: |
  /* CONFIGURAZIONE VISIVA MISSION-CRITICAL */
  section { 
    font-family: 'Segoe UI', 'Helvetica Neue', Arial, sans-serif; 
    font-size: 23px; 
    padding: 30px 50px;
    letter-spacing: 0.2px;
    color: #2c3e50;
  }
  
  h1 { 
    color: #003366; 
    font-size: 1.5em; 
    border-bottom: 3px solid #b33900; 
    padding-bottom: 10px;
    margin-bottom: 20px;
  }
  h2 { color: #004488; font-size: 1.1em; margin-bottom: 10px; }
  
  strong { color: #b33900; font-weight: 700; }
  
  /* Tabella Comparativa */
  table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 0.85em; box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
  th { background-color: #003366; color: #fff; border: 1px solid #003366; text-align: left; padding: 8px; }
  td { border: 1px solid #ddd; padding: 8px; background-color: #fff; }
  tr:nth-child(even) td { background-color: #f9f9f9; }

  /* Timeline */
  .timeline-box {
    background: #f0f4f8;
    border-left: 6px solid #004488;
    padding: 10px 15px;
    margin-bottom: 10px;
    font-size: 0.9em;
  }
  
  /* Badge */
  .badge-acid { background: #d32f2f; color: white; padding: 2px 6px; border-radius: 4px; font-weight: bold; font-size: 0.8em; }
  .badge-docker { background: #0288d1; color: white; padding: 2px 6px; border-radius: 4px; font-weight: bold; font-size: 0.8em; }

---

<!-- 
_class: lead
_backgroundColor: #003366
_color: #ffffff
_header: ''
_footer: ''
-->
<style scoped>
h1 { border: none; color: #ffffff; text-shadow: 2px 2px 5px rgba(0,0,0,0.4); font-size: 2.2em; }
h2 { color: #dddddd; font-weight: normal; }
strong { color: #ffcc00; }
hr { border-color: #ffcc00; width: 60%; }
</style>

# Digitalizzazione Archivio Soci
## Evoluzione Mission-Critical

<hr>

### Versione 1.3.1 (Enterprise Edition)
**Relazione Storica e Tecnica**

<br>

**A cura di:** *Soobadur Mohammad Ajmeer*
Lead Developer & Architect

---

# 1. Visione e Obiettivi

Il progetto nasce per risolvere l'inefficienza cronica dell'archiviazione cartacea, evolvendosi in un **ecosistema digitale sicuro**.

### I Pilastri del Progetto
1.  **Integrità Assoluta:** Nessun dato deve andare perso (ACID).
2.  **Sicurezza Bancaria:** Protezione totale (2FA, Encryption).
3.  **Resilienza:** Il sistema deve auto-monitorarsi.
4.  **Eredità:** Preservare la storia della Fratellanza per decenni.

---

# 2. Cronologia Evolutiva (Fasi 1-2)

<div class="timeline-box">
<strong>FASE 1: Fondamenta (v1.0)</strong><br>
Definizione delle entità Core (Socio, Documento). Scelta di SQLite e Repository Pattern. Architettura MVC base.
</div>

<div class="timeline-box">
<strong>FASE 2: Robustezza (v1.2)</strong><br>
Introduzione Security Middleware. Hardening delle sessioni e implementazione della <strong>2FA (Due Fattori)</strong> per amministratori.
</div>

---

# 3. Cronologia Evolutiva (Fasi 3-4)

<div class="timeline-box">
<strong>FASE 3: DevOps & Qualità (v1.3.0)</strong><br>
Containerizzazione con <span class="badge-docker">DOCKER</span> per parità ambienti. Frontend engineering con <strong>Vite</strong>. Test Automation (PestPHP).
</div>

<div class="timeline-box" style="border-left-color: #d32f2f; background: #fff5f5;">
<strong>FASE 4: Mission-Critical (v1.3.1 - ATTUALE)</strong><br>
Integrità atomica con <span class="badge-acid">TRANSAZIONI ACID</span>. Osservabilità totale tramite <strong>Correlation IDs</strong> e Resilience Monitor.
</div>

---

# 4. Il Salto Tecnologico

Confronto diretto tra l'inizio del progetto e lo stato attuale.

| Area | Stato v1.0 (Start) | Stato v1.3.1 (Mission-Critical) |
| :--- | :--- | :--- |
| **Persistenza** | Query SQL Semplici | ✅ **Transazioni Atomiche & Integrità** |
| **Sicurezza** | Password Base | ✅ **2FA, Rate Limit, Session Hardened** |
| **Qualità** | Test Manuali | ✅ **71 Test Automatici (100% Pass)** |
| **Diagnosi** | Nessuna | ✅ **Resilience Monitor & Correlation IDs** |
| **Deploy** | Copia File | ✅ **Docker Container & Migrations** |

---

# 5. Architettura Aggiornata (v1.3.1)

Il sistema integra ora un layer di **Resilienza** e **Transazionalità**.

<!-- Assicurati che l'immagine diagramma sia aggiornata o usa quella v1.3.0 che va bene comunque -->
![w:900](../Architettura/Images_Diagram_Class/diagram-class.png)

*   **Kernel:** Gestisce Transazioni ACID per ogni scrittura.
*   **Monitor:** Traccia ogni richiesta con un ID univoco.
*   **Docker:** Isola l'applicazione dal sistema operativo host.

---

# 6. Sicurezza e Hardening

Timeline delle implementazioni di sicurezza:

1.  **Maggio 25:** Hashing **BCRYPT** per le password.
2.  **Giugno 25:** Protezione CSRF e Security Headers.
3.  **Ottobre 25:** Autenticazione a Due Fattori (**2FA**) e Audit Log.
4.  **Dicembre 25:**
    *   **Session Hardening:** SameSite Strict / HttpOnly.
    *   **Storage Lockdown:** Protezione `.htaccess`.
    *   **Correlation IDs:** Tracciabilità forense end-to-end.

---

# 7. Qualità Certificata

Metriche finali al 21 Dicembre 2025.

*   🧪 **Test Automation:** 71 test eseguiti (PestPHP).
*   COVERAGE: **100%** su logica critica.
*   🔍 **Analisi Statica:** PHPStan **Level 5** (Zero Errori).
*   🏗️ **Infrastruttura:** Ambiente replicabile via `docker-compose`.

> "Il codice è scritto per essere manutenibile anche tra 10 anni."

---

# 8. Analisi Costi/Benefici Finale

*   💰 **Economico:**
    *   Risparmio strutturale su materiali di consumo (-70%).
    *   Zero costi di licenza software (Open Source Stack).
*   ⚡ **Operativo:**
    *   Resilienza ai guasti (Backup + Transazioni).
    *   Tempi di ripristino < 15 minuti (grazie a Docker).
*   ⚖️ **Legale:**
    *   Audit Trail completo per conformità GDPR.

---

# 9. Conclusioni e Verdetto

Il passaggio allo standard **Mission-Critical** rappresenta il culmine di un percorso di ingegneria meticolosa.

### Stato Attuale:
Il sistema non è solo "funzionante", è **resiliente**.
La stabilità delle transazioni e la tracciabilità totale rendono l'archivio pronto per gestire dati sensibili reali.

**Prossimo Passo:** Avvio immediato in Produzione.

---

<!-- 
_class: lead
_backgroundColor: #003366
_color: #ffffff
_header: ''
_footer: ''
-->
<style scoped>
h1 { border: none; color: #ffffff; }
strong { color: #ffcc00; }
.final-verdict { border: 3px solid #ffcc00; padding: 30px; border-radius: 15px; margin-top: 30px; background: rgba(255,255,255,0.1); }
</style>

# Approvazione Finale

<div class="final-verdict">
<h1>✅ READY FOR PRODUCTION</h1>
<h3>Versione 1.3.1 (Stable)</h3>
<br>
Si rilascia il nulla osta tecnico per il deploy.
</div>

<br>

**Soobadur Mohammad Ajmeer**
*Lead Developer & Architect*