# 🤝 Contributing to MCAG Enterprise

Thank you for your interest in contributing to the MCAG Project!
While the core system is "Solo Dev" driven, we welcome community contributions for adapters, translations, and plugins.

## 🚀 How to Contribute

### 1. The Workflow
We use the **Feature Branch Workflow**.
1. Fork the repository.
2. Create a branch: `git checkout -b feature/amazing-feature`.
3. Commit changes (Atomic commits please!).
4. Push to your fork.
5. Open a Pull Request against `develop`.

### 2. Standards & Style
- **PHP**: PSR-12 Standard.
- **JS**: ESLint standard config.
- **Commits**: Conventional Commits (`feat: add capability`, `fix: resolve bug`).
- **Tests**: Every PULL REQUEST must include tests. Coverage must not drop below 90%.

### 3. Review Process
- All PRs are reviewed by the Core Team (Solo Dev + AI Assistants).
- CI/CD will run automatically (PHPStan Level 7 check).

## 🏗️ Architecture Guide
- **Domain Layer**: Pure PHP, no dependencies.
- **Infra Layer**: Heavy implementation details (SQL, API calls).
- **Controller**: Thin logic, delegates to Services.

## 📜 Code of Conduct
Please be respectful. We are building a professional enterprise tool.
Harassment or unprofessional behavior will result in a ban from the community.

---
*Welcome to the Resistance against Legacy Software.*
