# SERVICE LEVEL AGREEMENT (SLA) - MANUTENZIONE
**Prodotto**: MCAG System v4.0 Enterprise
**Livello Servizio**: Standard/Professional

## 1. DEFINIZIONI
- **Ore Operative**: 09:00 - 18:00, Lunedì-Venerdì (festivi esclusi).
- **Tempo di Risposta (RTO)**: Tempo massimo entro cui viene presa in carico una segnalazione.
- **Tempo di Risoluzione (RPO)**: Tempo obiettivo per il ripristino del servizio.

## 2. CLASSIFICAZIONE INCIDENTI

| Severità | Descrizione | RTO (Presa in Carico) | RPO (Target Risoluzione) |
|----------|-------------|-----------------------|--------------------------|
| **Critica (S1)** | Sistema completamente indisponibile, perdita dati. | < 2 ore | < 8 ore lavorative |
| **Alta (S2)** | Funzionalità core degradate (es. no login), workaround non disponibile. | < 4 ore | < 24 ore lavorative |
| **Media (S3)** | Funzionalità secondarie non operative, workaround disponibile. | < 8 ore | < 3 giorni lavorativi |
| **Bassa (S4)** | Richieste info, bug estetici, enhancement. | < 24 ore | Next Release |

## 3. SERVIZI INCLUSI
- **Patching di Sicurezza**: Rilascio hotfix per vulnerabilità critiche entro 48h.
- **Aggiornamenti Minori**: Accesso a versioni x.Y.z (bugfix e ottimizzazioni).
- **Supporto Tecnico**: Helpdesk via Email/Ticket System.

## 4. ESCLUSIONI
Lo SLA non copre incidenti causati da:
- Modifiche al codice sorgente non autorizzate.
- Malfunzionamenti hardware o di rete del Cliente.
- Forza maggiore.

## 5. PENALI E RIMBORSI
In caso di mancato rispetto dell'RTO per incidenti S1, il Cliente ha diritto a un credito di servizio pari al 5% del canone annuale di manutenzione per ogni violazione, fino a un massimo del 20% annuo.
