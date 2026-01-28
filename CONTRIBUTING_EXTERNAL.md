# Contributing to MCAG Enterprise

Thank you for your interest in contributing to the **MCAG Project**!
We follow strict Enterprise standards to maintain system integrity. Please read this guide carefully before submitting a Pull Request.

## 🛡️ The Golden Rules

1.  **No Simulations**: All code must be production-ready. No `return true;` mocks allowed in `main` branch.
2.  **Strict Typing**: All PHP code must use `declare(strict_types=1);` and full type hinting.
3.  **ADR Compliance**: Check `DECISION_LOG.md`. If your change violates an ADR, it will be rejected.
4.  **Tests Required**: Every new feature must have a corresponding Unit or Feature test.

## 🌿 Git Workflow

We use **Gitflow**.
*   `main`: Production releases only.
*   `develop`: Integration branch.
*   `feature/my-feature`: Your work area.
*   `hotfix/xxx`: Critical production fixes.

**Workflow**:
1.  Fork the repo.
2.  Create `feature/cool-stuff`.
3.  Commit often (Atomic Commits).
4.  Push and open a PR against `develop`.

## 🧪 Testing

Run almost all checks with one command:
```bash
composer test
```
This runs PHPUnit, PHPStan (Level 6), and CS Fixer.

## 🎨 Code Style

We follow **PSR-12**.
*   4 spaces indentation.
*   Method names in `camelCase`.
*   Class names in `PascalCase`.

## 🤝 Community
Join our Discord Server (Invite Only) or start a Discussion on GitHub.

*Welcome to the Team!* 🚀
