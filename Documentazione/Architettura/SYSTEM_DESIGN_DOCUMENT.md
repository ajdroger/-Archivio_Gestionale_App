# System Design Document
**Project Name:** Digitalizzazione e Dematerializzazione Archivio Soci

| Document ID | SYSTEM DESIGN-v2.0 (Enterprise) |
| :--- | :--- |
| **Version Number** | 2.0.1 |
| **Issue Date** | December 27, 2025 |
| **Author** | Soobadur Mohammad Ajmeer © |
| **Classification** | Private (Internal Use Only) |
| **Architecture** | Cloud-Ready Hybrid (MySQL/Local) |

**Copyright Notice**
Soobadur Mohammad Ajmeer ©
Oragnizzazione: Fratellanza Militare di Firenze

---

## 1. Introduction

### 1.1 Methodology & Standards (v2.0)
The system has been elevated to **Mission-Critical Enterprise** standards:
*   **Database**: Migrated to **MySQL 8.0** with strict ACID transactions via InnoDB.
*   **Observability**: Full stack correlation via **Request Correlation ID** injected in every log/header.
*   **Deployment**: CI/CD ready structure (GitHub Actions compatible) and Cloud-Native adapters (Railway/Docker).
*   **Security Hub**: Centralized dashboard for 2FA, Anomalies and Session tracking.

---

## 2. Design Overview

### 2.1 Logical Architecture
```mermaid
graph TD
    User[Client Browser]
    WAF[Firewall / Rate Limit]
    App[Apache/PHP Application]
    Auth[Security Layer (2FA)]
    DB[(MySQL Database)]
    FS[File System (Encrypted)]
    Mail[SMTP Service]

    User --> WAF
    WAF --> App
    App --> Auth
    Auth --> App
    App --> DB
    App --> FS
    App --> Mail
```

### 2.2 Security Architecture (Layered Defense)
1.  **Transport**: HTTPS Only + HSTS.
2.  **Session**: HttpOnly, SameSite=Strict, Rotation on Privilege Change.
3.  **Authentication**: Strong Password Policy (Bcrypt) + TOTP (2FA).
4.  **Authorization**: RBAC (Role Based Access Control) via Middleware.
5.  **Audit**: Immutable Audit Log table tracing `user_id`, `action`, `ip`, `user_agent`.

---

## 3. Data Integrity & Resilience

### 3.1 Transaction Management
All write operations are wrapped in `PDO::beginTransaction()` ... `PDO::commit()`.
Rollbacks occur automatically on any exception, ensuring zero partial data states.

### 3.2 Backup Strategy
- **Hot Backup**: Daily MySQL dump via `bin/maintenance/backup_daily.php`.
- **Cold Storage**: Local ZIP archive with 7-day retention.
- **Verification**: Integrity checks (`backup_verify.php`) run post-backup to ensure restoration capability.

---

## 4. Operational Tools (Mission Control)

The `bin/tools/` directory contains the "Black Box" diagnostics suite:
- `health_check.php`: 360° system scan.
- `security_audit_cli.php`: File permissions & config inspector.
- `test_smtp.php`: Email pipeline verifier.

---

**Appendix**
*   **API Reference**: See `../Manuali/API_REFERENCE.md`.
*   **Structure Index**: See `Structure_Index.md`.
