# Test Manifesto - v2.0.0 Enterprise Stable
**Data**: 2026-01-10
**Branch**: feature/tests
**Target**: main (Stable)

## 1. Copertura
- [x] Unit Tests (PHPUnit/Pest)
- [x] Feature Tests (HTTP Flow)
- [x] Security Tests (Middleware, Headers, CSRF)
- [x] Architecture Tests (Arch)
- [x] Performance Tests (k6 Lite)

## 2. Stato CI/CD
- [x] GitHub Actions Workflow (Pinned SHA-1)
- [x] Static Analysis (PHPStan Level 6)
- [x] Dependency Audit (Composer/NPM)

## 3. Certificazione
Questo branch serve come "Quality Gate" obbligatorio prima del merge in Stable.
Tutti i test devono passare qui.
