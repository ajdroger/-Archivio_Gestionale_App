# ✅ RELEASE CHECKLIST MCAG
## Pre-Release Verification

**Versione**: 1.0
**Data**: 27 Gennaio 2026

---

## 1-2 WEEKS BEFORE RELEASE

### Code Freeze
- [ ] Announce code freeze date to team (Slack #dev)
- [ ] Create release branch `release/v8.4.0` from `develop`
- [ ] Bump version in:
  - [ ] `composer.json`
  - [ ] `package.json`
  - [ ] `config/app.php` (VERSION constant)

### Documentation
- [ ] Update CHANGELOG.md (move [Unreleased] to [8.4.0])
- [ ] Update README.md (version badge, new features)
- [ ] Write Release Notes (marketing-friendly)
- [ ] Update API docs (if API changes)

### Testing
- [ ] All tests passing (`./vendor/bin/pest`)
  - [ ] Unit: 103 tests
  - [ ] Feature: 62 tests
  - [ ] Integration: 31 tests
  - [ ] E2E: 10 tests
- [ ] Code coverage ≥90% (`./vendor/bin/pest --coverage`)
- [ ] PHPStan level 7 zero errors
- [ ] Security audit clean:
  - [ ] `composer audit`
  - [ ] `npm audit`
  - [ ] OWASP ZAP scan (no HIGH/CRITICAL)
- [ ] Performance benchmarks met:
  - [ ] Dashboard load <50ms (p95)
  - [ ] API response <100ms (p95)
  - [ ] Database queries <20ms (avg)

---

## 1 WEEK BEFORE

### QA Testing
- [ ] Deploy to **staging environment**
- [ ] Run full regression suite (manual + automated)
- [ ] Cross-browser testing (Chrome, Firefox, Edge, Safari)
- [ ] Mobile responsiveness (iOS Safari, Android Chrome)
- [ ] Load testing (simulate 500 concurrent users)

### Migration Testing
- [ ] Test database migrations on staging clone
  - [ ] Forward migration (`php bin/console migrate`)
  - [ ] Rollback migration (`php bin/console migrate:rollback`)
  - [ ] Verify data integrity post-migration
- [ ] Test upgrade path from v8.3.0 → v8.4.0

### Security
- [ ] Review permissions/roles (no accidental privilege escalation)
- [ ] Verify encryption keys rotated (if scheduled)
- [ ] Check SSL cert expiry (>30 days remaining)
- [ ] Run penetration test (internal or external consultant)

---

## 3 DAYS BEFORE

### Infrastructure
- [ ] Check server capacity (disk space >20% free)
- [ ] Verify backup last successful (<24h ago)
- [ ] Test disaster recovery restore (from backup)
- [ ] Notify hosting provider (if major deploy, coordinate)

### Communication
- [ ] Draft customer email (release announcement)
- [ ] Schedule downtime window (if needed, off-peak hours)
- [ ] Update status page (https://status.mc ag.it)

---

## DAY BEFORE RELEASE

### Final Checks
- [ ] Review all open P0/P1 bugs (must be fixed or deferred)
- [ ] Merge release branch to `main`
- [ ] Create Git tag `v8.4.0`
- [ ] Build production assets:
  ```bash
  npm run build
  composer install --no-dev --optimize-autoloader
  ```
- [ ] Test production build locally (via Docker container)

### Team Briefing  
- [ ] Release meeting (15 min):
  - Deployment plan
  - Rollback procedure
  - On-call rotation (post-release 48h)

---

## RELEASE DAY (D-Day)

### Pre-Deploy (Morning)
- [ ] Final smoke test staging
- [ ] Database backup **production** (immediate pre-deploy)
- [ ] Enable maintenance mode:
  ```bash
  php bin/console down --message="Upgrading to v8.4.0, back in 15 min"
  ```

### Deploy (Execution)
- [ ] Pull latest code:
  ```bash
  git fetch --tags
  git checkout v8.4.0
  ```
- [ ] Install dependencies:
  ```bash
  composer install --no-dev
  npm ci && npm run build
  ```
- [ ] Run migrations:
  ```bash
  php bin/console migrate --force
  ```
- [ ] Clear caches:
  ```bash
  php bin/console cache:clear
  redis-cli FLUSHALL
  ```
- [ ] Restart services:
  ```bash
  systemctl restart php8.2-fpm
  systemctl restart nginx
  systemctl restart mcag-queue-worker
  ```

### Post-Deploy Validation (15 min)
- [ ] Disable maintenance mode:
  ```bash
  php bin/console up
  ```
- [ ] Run smoke tests:
  ```bash
  ./vendor/bin/pest --testsuite=Smoke
  ```
- [ ] Manual checks:
  - [ ] Login works (test user + admin)
  - [ ] Dashboard loads (<100ms)
  - [ ] CRUD socio works (create, read, update, delete)
  - [ ] Document upload/download works
  - [ ] Workshift create works
- [ ] Check logs (no errors):
  ```bash
  tail -f storage/logs/mcag.log | grep ERROR
  ```
- [ ] Monitor metrics (Grafana):
  - [ ] Error rate <1%
  - [ ] Response time normal
  - [ ] Database connection stable

---

## POST-RELEASE (24-48h)

### Monitoring Intensive
- [ ] Watch error rate (Sentry) – expect slight spike (< 2x baseline)
- [ ] Monitor support tickets – respond quickly to issues
- [ ] Check social (Twitter, forum) for user feedback

### Communication
- [ ] Send release announcement email to customers
- [ ] Post release notes on blog/website
- [ ] Update LinkedIn/Twitter
- [ ] Notify partners (if API changes)

### Retrospective (Within 7 days)
- [ ] Team retro meeting:
  - What went well?
  - What went wrong?
  - Action items for next release
- [ ] Document lessons learned (append to this checklist if applicable)

---

## ROLLBACK PROCEDURE (If Needed)

**Trigger**: Critical bug discovered, error rate >10%, data corruption

**Steps**:
1. Enable maintenance mode
2. Restore database from pre-deploy backup
3. Checkout previous tag `git checkout v8.3.0`
4. Rebuild dependencies
5. Restart services
6. Validate rollback successful
7. Communicate incident to users
8. Root cause analysis

**Decision Maker**: CTO or on-call engineer (for P0)

---

## SIGN-OFF

**Release Manager**: ________________ Date: ______  
**QA Lead**: ________________ Date: ______  
**CTO Approval**: ________________ Date: ______

---

**Use this checklist for EVERY release. Don't skip steps!**

**© 2026 Soobadur Mohammad Ajmeer**
