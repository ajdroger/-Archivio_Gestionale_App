# Changelog

Tutte le modifiche notevoli a questo progetto saranno documentate in questo file.

Il formato è basato su [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
e questo progetto aderisce al [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---



---

## [Unreleased]

### Pianificato
- Redis-based caching per query frequenti
- API versioning esplicito (`/api/v1/`)
- Background jobs system con queue
- Monitoring con Prometheus + Grafana

---

## [2.5.0] - 2026-01-11 "**Historical Rigor**"
### Aggiunto
- **Policy Retention Totale**: Regola obbligatoria per il NON-cancellamento di qualsiasi branch (`feature/*`, `tests/*`, `hotfix/*`) per garantire audit trail storico completo.
- **Workflow Update**: Aggiornamento guide (`GUIDA_GIT_BASH.md`, `feature_development.md`) con istruzioni di chiusura/riapertura branch.
- **Mandatory Logging**: Obbligo di aggiornamento `CHANGELOG` e `DECISION_LOG` prima della chiusura di ogni feature.

### Modificato
- **Git Workflow**: I branch di test (`tests/*`) ora sono perenni (chiusi ma esistenti).
- **Documentazione**: Refactoring guide per riflettere il nuovo approccio "History-First".

---

## [4.0.0] - 2026-01-11 "**Ultimate Upgrade**"
