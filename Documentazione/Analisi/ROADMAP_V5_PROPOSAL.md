# 🚀 ROADMAP v5.0.0 "SINGULARITY EDITION"
**Data Proposta**: Gennaio 2026
**Target Release**: Q4 2026
**Analisi**: Soobadur Mohammad Ajmeer

---

## 🎯 VISIONE STRATEGICA
Portare MCAG System da "Enterprise Grade" a **"Future-Proof Ecosystem"**. 
La versione 5.0 non sarà solo un aggiornamento, ma una trasformazione in piattaforma intelligente, proattiva e universale.

---

## 1. 🧠 INTEGRAZIONE AI (L'ERA DELLA SINGOLARITÀ)
*Il differenziale competitivo definitivo.*

### A. "Archivio Parlante" (Local RAG)
Implementare un sistema RAG (Retrieval-Augmented Generation) locale (es. Ollama/Llama 3) per interrogare i documenti PDF e lo storico soci in linguaggio naturale.
*   **User Query**: "Dammi un riassunto delle decisioni del Consiglio Direttivo del 1998 riguardo la sede."
*   **System Action**: Scansione OCR/Text dei PDF -> Vector DB -> LLM Answer.

### B. Predictive Analytics
*   **Previsione Morosità**: Analisi pattern pagamenti per identificare soci a rischio abbandono *prima* che succeda.
*   **Trend Iscrizioni**: Forecasting basato su dati storici decennali.

---

## 2. 🏗️ ARCHITETTURA "MODULAR MONOLITH 2.0"
*Solidificare le fondamenta per supportare il peso dell'AI.*

### A. Refactoring Event-Driven
Passare da una logica procedurale a una **Event-Driven Architecture (EDA)** completa.
*   Ogni azione (es. `SocioCreato`, `QuotaPagata`) emette un evento.
*   Listener disaccoppiati gestiscono: Log, Email, Cache Invalidation, AI Indexing.

### B. PHP 8.4 & Fiber
Utilizzare le **Fiber** di PHP 8.4 per operazioni asincrone non bloccanti (es. generazione massiva PDF, invio email, indicizzazione AI) senza dipendere esclusivamente da code esterne.

---

## 3. ⚡ FRONTEND "HYPER-REACTIVE"
*Abbandonare il legacy per la fluidità nativa.*

### A. HTMX + Alpine.js
Sostituire jQuery con **HTMX** per interazioni server-side fluide e **Alpine.js** per lo stato UI locale. 
*   **Obiettivo**: Feeling da Single Page App (SPA) senza la complessità di build React/Vue.

### B. PWA (Progressive Web App) Offline-First
Rendere l'app installabile su Desktop/Mobile con capacità **Offline**.
*   Consultazione anagrafica di base cacheata localmente.
*   Sync automatico quando torna la connessione.

---

## 4. 🌐 API & INTEROPERABILITÀ

### A. GraphQL Full Coverage
Completare la transizione a GraphQL per offrire un'unica interfaccia flessibile per Frontend, Mobile App e Integrazioni Esterne (es. Sito Web Pubblico).

### B. Webhooks System
Permettere a sistemi esterni (es. CRM esterni, software contabilità) di sottoscriversi agli eventi del sistema (es. Webhook su `NuovoSocio`).

---

## 5. 🛡️ INFRASTRUTTURA & SCALABILITÀ "CLOUD-NATIVE"

### A. Multi-Tenancy Nativa
Supporto per gestire **multiple associazioni** con un'unica istanza software.
*   Isolamento dati via `tenant_id` su tutte le tabelle.
*   Database separati virtuali.

### B. Docker & Kubernetes Ready
Creare chart **Helm** ufficiali per deployment su cluster Kubernetes per clienti Enterprise su larga scala.

---

## 📅 TIMELINE PROPOSTA (2026)

| Fase | Focus | Milestone Key | Release Stimata |
|------|-------|---------------|-----------------|
| **Q1** | Architettura | Event Bus, PHP 8.4, Refactor Moduli | v4.2.0 (Apr 2026) |
| **Q2** | Frontend | HTMX Migration, PWA Support | v4.5.0 (Giu 2026) |
| **Q3** | AI & Data | Vector DB, RAG Integration, Analytics | v4.8.0 (Set 2026) |
| **Q4** | **Launch v5.0** | Multi-tenancy, Full GraphQL, AI Stable | **v5.0.0 (Dic 2026)** |

---

## 💰 IMPATTO COMMERCIALE PREVISTO
L'introduzione dell'AI giustifica un aumento del **Pricing Enterprise del 40%**.
*   **Licenza Enterprise v4**: €120.000
*   **Licenza Singularity v5**: €169.000 (include modulo AI)

---

**Approvazione Richiesta per Procedere alla Fase 1 (Architettura).**

