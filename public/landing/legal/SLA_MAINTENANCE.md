# SERVICE LEVEL AGREEMENT (SLA) & MANUTENZIONE

**Prodotto**: MCAG System - Militare Civile Archivio Gestionale (v4.0)
**Fornitore**: AJ Developing & Coding
**Validità**: Allegato al Contratto di Licenza / Contratto SaaS
**Versione**: 2.0 (Enterprise)

## 1. LIVELLI DI SERVIZIO (SERVICE TIERS)

| Caratteristica | SLA Standard | SLA Professional | SLA Enterprise |
|----------------|--------------|------------------|----------------|
| **Ore Copertura** | Lun-Ven, 09:00-18:00 | Lun-Sab, 08:00-20:00 | 24/7/365 |
| **Canale Supporto** | Email / Web Ticket | Email / Telefono Priority | Canale Dedicato / Slack |
| **Tempi Risposta (S1)** | < 8 ore lav. | < 4 ore lav. | < 1 ora |
| **Uptime Garantito** | ND (On-Prem) / 99.0% (SaaS) | ND (On-Prem) / 99.5% (SaaS) | ND (On-Prem) / 99.9% (SaaS) |
| **Manutenzione** | Aggiornamenti Sicurezza | + Minor Updates (Features) | + Major Upgrades & Consulting |

## 2. DEFINIZIONE INCIDENTI E PRIORITÀ

Il Fornitore classifica le richieste di assistenza secondo la seguente matrice di severità:

### Severità 1: CRITICA (Bloccante)
- **Definizione**: Il sistema è inaccessibile o inutilizzabile per la totalità degli utenti; perdita di dati o corruzione critica.
- **RTO (Response Time Objective)**: Vedi Service Tier.
- **RPO (Resolution Plan Objective)**: Workaround entro 4h (Ent), 8h (Pro), 24h (Std).
- **Esempio**: Errore 500 su tutte le pagine, Database down, Login impossibile.

### Severità 2: ALTA (Grave)
- **Definizione**: Funzionalità core (es. Inserimento Soci, Export PDF) non funzionanti; nessun workaround ragionevole disponibile.
- **RTO**: Entro 8h lavorative.
- **Esempio**: Impossibilità caricare documenti, fallimento generazione report annuale.

### Severità 3: MEDIA (Normale)
- **Definizione**: Malfunzionamento di funzionalità secondarie; workaround disponibile; degrado prestazioni non critico.
- **RTO**: Entro 24h lavorative.
- **Esempio**: Errore visualizzazione grafico dashboard, bug minoredi interfaccia (CSS).

### Severità 4: BASSA (Minore)
- **Definizione**: Richieste di informazioni, errori estetici, typo, suggerimenti feature.
- **RTO**: Best effort / Next release.

## 3. MATRICE DI ESCALATION

Se i tempi di risoluzione target non vengono rispettati, la problematica scala automaticamente:

| Livello | Ruolo Responsabile | Tempistica Escalation (S1) |
|---------|--------------------|---------------------------|
| **L1** | Helpdesk Operator | Immediata |
| **L2** | Senior Developer | Dopo 2 ore |
| **L3** | CTO / Lead Architect | Dopo 4 ore |

## 4. PENALI E CREDITI DI SERVIZIO (Solo SaaS/Enterprise)

In caso di mancato rispetto dell'Uptime Garantito (calcolato su base mensile), il Fornitore riconoscerà i seguenti crediti di servizio:

- **Uptime < 99.9%** (ma > 99.5%): 5% di credito sul canone mensile.
- **Uptime < 99.5%** (ma > 99.0%): 10% di credito sul canone mensile.
- **Uptime < 99.0%**: 25% di credito sul canone mensile.

## 5. MANUTENZIONE PROGRAMMATA
Il Fornitore si riserva finestre di manutenzione programmata per aggiornamenti infrastrutturali:
- **Preavviso**: Minimo 48 ore (salvo emergenze di sicurezza).
- **Finestra**: Di norma tra le 22:00 e le 06:00 (CET).
- **Impatto SLA**: I tempi di manutenzione programmata sono esclusi dal calcolo dell'uptime.

## 6. ESCLUSIONI
Lo SLA decade o non si applica in caso di:
1. Modifiche non autorizzate al codice sorgente o configurazione server (On-Prem).
2. Malfunzionamenti causati da provider terzi (es. connettività ISP cliente, datacenter cliente).
3. Forza maggiore (calamità naturali, guerre, attacchi DDoS massivi).
4. Utilizzo del software difforme dalla documentazione (misuse).

---
**Approvazione SLA**:
L'adesione a un piano di supporto (Standard, Professional o Enterprise) implica l'accettazione integrale del presente SLA.
