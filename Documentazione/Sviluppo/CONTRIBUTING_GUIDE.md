# 🛠️ CONTRIBUTING GUIDE MCAG
## Come Contribuire al Progetto

**Versione**: 1.0  
**Data**: 27 Gennaio 2026

---

## WELCOME CONTRIBUTORS!

Grazie per l'interesse a contribuire a MCAG! Questo documento spiega come:
- Setup ambiente sviluppo
- Segnalare bug
- Proporre features
- Submit pull requests
- Code review process

---

## 1. CODE OF CONDUCT

Ci aspettiamo che tutti i contributor:
✅ Siano rispettosi e inclusivi  
✅ Accettino feedback costruttivo  
✅ Focussino su ciò che è meglio per la community  
❌ NO harassment, discriminazione, trolling

Violazioni → Warning (1st), Ban (repeated)

---

## 2. GETTING STARTED

### Prerequisites
- PHP 8.2+
- Composer 2.6+
- MySQL 8.0+
- Redis 7+
- Node.js 20+
- Git

###  Fork & Clone

```bash
# Fork repo su GitHub (click "Fork" button)

# Clone tuo fork
git clone https://github.com/YOUR_USERNAME/mcag.git
cd mcag

# Add upstream remote
git remote add upstream https://github.com/mcag-official/mcag.git
```

### Install Dependencies

```bash
# PHP
composer install

# JavaScript
npm install

# Copy environment
cp .env.example .env

# Generate key
php bin/console key:generate

# Run migrations
php bin/console migrate

# Seed test data
php bin/console db:seed --env=development
```

### Run Development Server

```bash
# PHP server
php -S localhost:8000 -t public/

# Frontend watch (separate terminal)
npm run dev

# Queue worker (separate terminal)
php bin/console queue:work
```

---

## 3. REPORTING BUGS

### Before Submitting
- [ ] Search existing issues (avoid duplicates)
- [ ] Verify bug on latest version
- [ ] Check if already fixed in `develop` branch

### Bug Report Template

```markdown
**Describe the bug**
[Clear description]

**To Reproduce**
1. Go to...
2. Click on...
3. See error

**Expected behavior**
[What should happen]

**Screenshots**
[If applicable]

**Environment**
- MCAG Version: [e.g. v8.3.0]
- PHP Version: [e.g. 8.2.15]
- OS: [e.g. Ubuntu 22.04]
- Browser: [e.g. Chrome 120]

**Additional context**
[Logs, stack traces]
```

Submit at: https://github.com/mcag-official/mcag/issues

---

## 4. SUGGESTING FEATURES

### Feature Request Template

```markdown
**Problem to solve**
[What pain point this addresses]

**Proposed solution**
[How feature would work]

**Alternatives considered**
[Other approaches evaluated]

**Additional context**
[Mockups, examples from other tools]
```

**Decision Process**:
1. Community discussion (GitHub Discussions)
2. Core team review (weekly triage)
3. Approved → Roadmap (labeled "accepted")
4. Assigned milestone/sprint

---

## 5. DEVELOPMENT WORKFLOW

### Gitflow Branches

```
main (production)
  ↑
stable (pre-production)
  ↑
develop (integration)
  ↑
feature/xyz (your work)
```

### Creating Feature Branch

```bash
# Update develop
git checkout develop
git pull upstream develop

# Create feature branch
git checkout -b feature/add-fiscal-code-validator

# Work on feature
# ... make changes ...

# Commit (see section 6)
git add .
git commit -m "feat: add fiscal code validator"

# Push to your fork
git push origin feature/add-fiscal-code-validator
```

### Pull Request Process

1. **Open PR** against `develop` branch (NOT `main`)
2. **Fill PR template**:
   ```markdown
   **What does this PR do?**
   [Summary]

   **Related Issue**
   Closes #123

   **Testing**
   - [ ] Unit tests added/updated
   - [ ] Feature tests added/updated
   - [ ] Manually tested

   **Screenshots** (if UI change)
   [Before/After]
   ```

3. **CI Checks** must pass:
   - ✅ All tests (206+) passing
   - ✅ PHPStan level 7 (zero errors)
   - ✅ Code coverage ≥90%
   - ✅ Coding standards (PSR-12)

4. **Code Review** (1-2 reviewers)
   - Address feedback
   - Push updates (same branch)

5. **Merge** (squash & merge by maintainer)

---

## 6. COMMIT CONVENTIONS

Use [Conventional Commits](https://www.conventionalcommits.org/):

```
<type>(<scope>): <subject>

<body>

<footer>
```

**Types**:
- `feat`: New feature
- `fix`: Bug fix
- `docs`: Documentation only
- `style`: Formatting (no code change)
- `refactor`: Code restructure (no behavior change)
- `perf`: Performance improvement
- `test`: Add/update tests
- `chore`: Build/tooling

**Examples**:
```bash
feat(auth): add two-factor authentication via TOTP

Implement TOTP-based 2FA using Google Authenticator.
Includes QR code generation and recovery codes.

Closes #234
```

```bash
fix(workshift): resolve schedule optimizer race condition

Race condition caused duplicate shifts when AI optimizer
ran concurrently. Added mutex lock.

Fixes #456
```

---

## 7. CODING STANDARDS

### Follow Project Style

- **PHP**: PSR-12 extended (see `CODE_STYLE_GUIDE.md`)
- **JavaScript**: ESLint config
- **CSS**: BEM methodology

### Run Linters

```bash
# PHP
./vendor/bin/phpstan analyze

# JavaScript
npm run lint

# Fix auto-fixable issues
./vendor/bin/php-cs-fixer fix
npm run lint:fix
```

### Write Tests

**Every PR should include tests!**

```php
// tests/Feature/FiscalCodeTest.php
it('validates correct fiscal code', function () {
    $validator = new FiscalCodeValidator();
    
    expect($validator->isValid('RSSMRA90E15H501Z'))->toBeTrue();
});
```

Run tests before submitting:
```bash
./vendor/bin/pest
```

---

## 8. CODE REVIEW

### As Author
- Respond to feedback promptly (within 48h)
- Be open to suggestions
- Explain rationale for decisions

### As Reviewer
- Be constructive and kind
- Point to docs/examples when suggesting changes
- Approve when:
  - ✅ Code quality high
  - ✅ Tests comprehensive
  - ✅ Documentation updated (if needed)

**Review Time SLA**: 2 business days

---

## 9. DOCUMENTATION

Update docs when:
- Adding new feature (update README, relevant guides)
- Changing API (update API docs)
- Fixing bug that wasn't obvious (add to troubleshooting guide)

Docs live in `Documentazione/` directory.

---

## 10. RECOGNITION

Contributors recognized via:
- **Contributors.md**: Listed with GitHub profile
- **Release Notes**: Credited in changelog
- **Swag**: T-shirt for 5+ merged PRs
- **Committer Status**: Top contributors invited to core team

---

## QUESTIONS?

- 💬 **GitHub Discussions**: https://github.com/mcag-official/mcag/discussions
- 📧 **Email**: contributors@mcag.it  
- 💬 **Slack**: #contributors channel (request invite)

**Happy Coding! 🚀**

**© 2026 Soobadur Mohammad Ajmeer**
