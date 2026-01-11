
## [ADR-026] Strict Branch Retention & Mandatory Auditing
**Data**: 2026-01-11
**Stato**: ✅ Attivo
**Contesto**:
La cancellazione dei branch dopo il merge, sebbene pulita, distrugge il contesto storico granulare dei tentativi, dei test falliti/passati e delle iterazioni di sviluppo. In un contesto Enterprise, l'Audit Trail è prioritario sulla pulizia visiva.
**Decisione**:
1.  **Retention Totale**: Nessun branch (`feature/*`, `tests/*`, `hotfix/*`) viene mai cancellato.
2.  **Stato "Chiuso"**: I branch mergiati vengono considerati "chiusi" (archiviati) semplicemente spostando l'HEAD su `develop` o `main`, ma rimangono nel reflog/repo.
3.  **Logging Sincrono**: È vietato chiudere un branch senza aver aggiornato `CHANGELOG.md` e `DECISION_LOG.md`.

**Conseguenze**:
- (+) **Auditabilità Totale**: Possibile ricostruire intera storia di sviluppo.
- (+) **Non-Repudiation**: Chi ha fatto cosa e quando (inclusi i test) è scolpito nella pietra.
- (-) **Dimensioni Repo**: Aumento numero references (gestibile con `git gc` se necessario).
