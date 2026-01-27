# 📝 CHANGELOG GUIDE MCAG
## Come Scrivere Changelog

**Versione**: 1.0
**Data**: 27 Gennaio 2026

---

## FORMATO (Keep a Changelog)

Seguiamo [Keep a Changelog](https://keepachangelog.com/) standard:

```markdown
# Changelog

## [Unreleased]
### Added
- Nuova funzionalità X

### Changed
- Modificato comportamento Y

## [8.3.0] - 2026-01-27
### Added
- God Mode Protocol emergency access
...
```

---

## CATEGORIE

### Added ✨
Nuove funzionalità/features

**Example**:
```markdown
### Added
- AI Workshift Optimizer con predictive analytics
- TOTP 2FA obbligatorio per tutti gli utenti
- Export CSV multi-formato (Excel, LibreOffice compatible)
```

### Changed 🔄
Modifiche comportamento esistente

**Example**:
```markdown
### Changed
- Dashboard layout: Widget drag-and-drop enabled
- Email templates: Migrated to modern responsive design
- Database schema: `soci.status` ora ENUM invece di VARCHAR
```

### Deprecated ⚠️
Features marcate obsolete (will be removed in future)

**Example**:
```markdown
### Deprecated
- API v1 endpoints (use v2)  → Rimozione v9.0  (Q4 2026)
- Legacy Excel import format → Usare CSV standard
```

### Removed 🗑️
Features rimosse

**Example**:
```markdown
### Removed
- Support IE11 (end of life)
- Old /login-legacy route (migrated to /auth/login)
```

### Fixed 🐛
Bug risolti

**Example**:
```markdown
### Fixed
- Workshift: Race condition causava duplicate shifts (#456)
- PDF generation: Timeout per documenti >50 pagine (#478)
- 2FA: Token validation falliva se server timezone != UTC (#490)
```

### Security 🔒
Correzioni vulnerabilità

**Example**:
```markdown
### Security
- Fixed SQL injection in search endpoint (CVE-2026-XXXX)
- Updated Composer dependencies (patched XSS in lib/foo v2.3)
- Strengthened CSRF token generation (now 256-bit random)
```

---

## VERSIONING (SemVer)

**Format**: `MAJOR.MINOR.PATCH`

- **MAJOR** (8.x.x): Breaking changes, API incompatibile
- **MINOR** (x.3.x): New features, backward-compatible
- **PATCH** (x.x.0): Bug fixes only

**Examples**:
- `8.2.5` → `8.2.6`: Solo bug fixes (PATCH)
- `8.2.6` → `8.3.0`: Nuove features (MINOR), backward compatible
- `8.3.0` → `9.0.0`: Breaking changes (MAJOR), migration needed

---

## ISSUE REFERENCES

Linka sempre issue/PR GitHub:

```markdown
### Fixed
- Workshift optimizer race condition (#456)
  Thanks @contributor-name for reporting!
```

**Format**: `(#issue-number)` auto-link GitHub

---

## RELEASE NOTES vs CHANGELOG

**CHANGELOG.md**: Technical, dettagliato, per developer  
**Release Notes** (marketing): User-focused, highlights, benefits

**Example Changelog**:
```markdown
### Added
- Implemented RAG-based AI assistant using Ollama + ChromaDB
```

**Example Release Note**:
```markdown
🎉 **AI Assistant**: Ask  questions in natural language, get instant answers!  
"How many active members?" → "182 active members as of today"
```

---

## BEST PRACTICES

✅ **Write for users**, not yourself  
✅ **Group similar changes** (all bug fixes together)  
✅ **Link issues/PRs** for context  
✅ **Date format**: ISO 8601 (YYYY-MM-DD)  
✅ **Keep it updated**: Add to `Unreleased` durante sviluppo  
❌ **Don't** dump git log directly (troppo tecnico)  
❌ **Don't** skip versions (ogni release ha changelog entry)

---

## AUTOMATION

### Git Hook (pre-commit)
```bash
#!/bin/bash
# Verifica che [Unreleased] section esista
if ! grep -q "## \[Unreleased\]" CHANGELOG.md; then
    echo "ERROR: CHANGELOG.md missing  [Unreleased] section"
    exit 1
fi
```

### Release Script
```bash
# scripts/release.sh v8.4.0
VERSION=$1
DATE=$(date +%Y-%m-%d)

# Replace [Unreleased] con [8.4.0] - 2026-XX-XX
sed -i "s/## \[Unreleased\]/## [Unreleased]\n\n## [$VERSION] - $DATE/" CHANGELOG.md

git add CHANGELOG.md
git commit -m "chore: prepare release $VERSION"
git tag -a "v$VERSION" -m "Release $VERSION"
```

---

## TEMPLATE ENTRY

```markdown
## [X.Y.Z] - YYYY-MM-DD

### Added
- New feature A (#123)
- New feature B (#124)

### Changed
- Updated behavior X (#125)

### Deprecated
- Feature Y will be removed in Z.0.0

### Removed
- Old feature Z (deprecated since X.Y-1.0)

### Fixed
- Bug #126: Description
- Bug #127: Description

### Security
- Patched vulnerability CVE-YYYY-XXXXX (#128)
```

---

**PRO TIP**: Leggi changelog competitor (es. Laravel, Symfony) per inspiration su clarity!

**© 2026 Soobadur Mohammad Ajmeer**
