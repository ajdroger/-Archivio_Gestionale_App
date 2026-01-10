# 📐 GERARCHIA COMPLETA DEL SISTEMA
## Archivio Gestionale Enterprise v2.3

**Autore**: Soobadur Mohammad Ajmeer ©  
**Data**: 06 Gennaio 2026  
**Versione Sistema**: 2.3 (Production-Ready Enterprise)  
**Tipo Documento**: Documentazione Tecnica Architetturale Completa

---

## 🎯 EXECUTIVE SUMMARY

Questo documento fornisce una **mappatura completa e meticolosa** dell'intera architettura del sistema gestionale "Fratellanza Militare Archivio", includendo:
- Gerarchia completa di **224 file sorgente** (~1MB di codice produzione)
- Analisi dettagliata di **80+ classi** distribuite su **13 namespace**
- Documentazione **layer-by-layer** dell'architettura enterprise
- Commenti esplicativi su ogni componente critico

### Metriche Chiave
| Metrica | Valore |
|---------|--------|
| **Files Sorgente** | 224 files (.php, .mustache, .ts, .js) |
| **Dimensione Codebase** | ~1.057 MB |
| **Classi Totali** | 80+ classi PHP |
| **Namespace Principali** | 13 namespace |
| **Test Suite** | 146+ test automatizzati |
| **Coverage Architettura** | 8 layer enterprise |
| **Livello PHPStan** | Level 6 (Strict) |

---

## 📁 STRUTTURA ROOT DEL PROGETTO

```
fratellanza-militare-archivio/
│
├── 📂 src/                      # SORGENTI APPLICAZIONE (80+ classi)
├── 📂 public/                   # WEB ROOT & ENTRY POINTS
├── 📂 config/                   # CONFIGURAZIONI DI SISTEMA
├── 📂 templates/                # TEMPLATE MUSTACHE (UI)
├── 📂 tests/                    # TEST SUITE (146+ tests)
├── 📂 Documentazione/           # TUTTA LA DOCUMENTAZIONE
├── 📂 db/                       # MIGRAZIONI & SEEDS
├── 📂 bin/                      # SCRIPT UTILITÀ & TOOL
├── 📂 docker/                   # CONTAINERIZZAZIONE
├── 📂 resources/                # ASSETS STATICI
├── 📂 storage/                  # FILE UPLOADS DINAMICI
├── 📂 logs/                     # LOG APPLICATIVI
├── 📂 backups/                  # BACKUP AUTOMATICI DB
├── 📂 migrazione_totale/        # SCRIPT MIGRAZIONE PROD
│
├── 📄 composer.json             # Dipendenze PHP
├── 📄 package.json              # Dipendenze Node.js
├── 📄 phpunit.xml               # Config Test Suite
├── 📄 vite.config.js            # Build Tool Frontend
├── 📄 .env                      # Variabili Ambiente
└── 📄 README.md                 # Documentazione Main
```

---

## 🏗️ LAYER 1: SRC/ - SORGENTI APPLICAZIONE

### 📦 Namespace: Controller/ (23 classi)

**Responsabilità**: Gestione richieste HTTP, routing, orchestrazione business logic

#### Controller Principali

##### 🔐 AuthController.php
```
SCOPO: Autenticazione, 2FA, Gestione Sessioni
ENDPOINTS:
  - GET  /login         → Mostra form login
  - POST /login         → Valida credenziali
  - POST /2fa/verify    → Verifica codice 2FA TOTP
  - GET  /2fa/setup     → QR Code provisioning
  - POST /logout        → Distrugge sessione
DIPENDENZE:
  - TotpProvider        → Generazione/validazione OTP
  - SessionManager      → Redis session handling
  - AuditTrail          → Log accessi
SICUREZZA:
  - Bcrypt password hash (cost 12)
  - RFC 6238 TOTP implementation
  - Session regeneration on auth
```

##### 👥 SocioController.php
```
SCOPO: CRUD Soci, Upload Documenti, Ricerca
ENDPOINTS:
  - GET    /soci              → Lista paginata
  - GET    /soci/{cf}         → Dettaglio socio
  - POST   /soci/nuovo        → Crea socio
  - PUT    /soci/{cf}         → Aggiorna dati
  - DELETE /soci/{cf}         → Soft delete
  - POST   /soci/{cf}/upload  → Upload documento
  - GET    /soci/search       → Ricerca full-text
BUSINESS LOGIC:
  - Validazione Codice Fiscale (regex + Luhn)
  - Generazione PDF modulo iscrizione
  - Integrity hash SHA-256 documenti
  - Audit trail tutte operazioni
DIPENDENZE:
  - ISocioRepository         → Persistenza
  - RegistrationService      → Business logic
  - ValidationService        → Input validation
  - PdfGenerationService     → PDF moduli
```

##### 📈 StatisticsController.php
```
SCOPO: Dashboard Metriche, Export CSV/PDF
ENDPOINTS:
  - GET /dashboard/stats         → Metriche real-time
  - GET /export/csv              → Export CSV
  - GET /export/pdf              → Export PDF
CACHING:
  - Redis cache (TTL 5min)
  - Cache-aside pattern
  - Invalidazione on CRUD
METRICHE:
  - Tot. soci / Attivi / Morosi
  - Distribuzione per anno
  - Metriche pagamento
```

##### 🛠️ DevToolsController.php
```
SCOPO: Dashboard Admin, DB Tools, Monitoring
SICUREZZA: Solo ruolo 'admin'
FEATURES:
  - Database query inspector
  - Log file analyzer
  - System health monitor
  - Script runner (safe whitelist)
  - Performance profiler
ENDPOINTS:
  - GET  /devtools               → Dashboard
  - POST /devtools/query         → SQL query
  - GET  /devtools/logs          → Log viewer
  - POST /devtools/script/{id}   → Run script
```

##### 🔷 GraphQLController.php
```
SCOPO: API GraphQL Endpoint
ENDPOINT: POST /api/graphql
QUERY TYPES:
  - socio(cf: String!): Socio
  - soci(limit: Int, offset: Int): [Socio]
MUTATIONS:
  - createSocio(input: SocioInput!): Socio
  - updateSocio(cf: String!, input: SocioInput!): Socio
  - deleteSocio(cf: String!): Boolean
FEATURES:
  - GraphiQL interface
  - Query batching
  - Dataloader pattern
SCHEMA: GraphQL/Schema.php
```

##### ⚙️ SettingsController.php
```
SCOPO: Impostazioni Utente, Cambio Password, 2FA
ENDPOINTS:
  - GET  /settings            → UI impostazioni
  - POST /settings/password   → Cambio password
  - POST /settings/2fa/enable → Attiva 2FA
  - POST /settings/2fa/disable→ Disattiva 2FA
```

*Altri Controller*:
- `ErrorController.php` → Custom error pages
- `HomeController.php` → Dashboard principale
- `DocumentoController.php` → Gestione documenti
- `ExportController.php` → Export multi-formato
- `ApiController.php` → REST API v1
- ... (17 controller specializzati)

---

### 📦 Namespace: Middleware/ (10 classi)

**Responsabilità**: Pipeline sicurezza, request/response transformation

#### Middleware Stack (ordine applicazione)

##### 1️⃣ SecurityHeadersMiddleware.php
```
SCOPO: Protezione HTTP Headers
HEADERS APPLICATI:
  - Content-Security-Policy: script-src 'self'
  - X-Frame-Options: DENY
  - X-Content-Type-Options: nosniff
  - Strict-Transport-Security: max-age=31536000
  - Referrer-Policy: no-referrer
STANDARD: OWASP Security Headers
```

##### 2️⃣ CsrfViewMiddleware.php
```
SCOPO: CSRF Token Injection
LIBRERIA: Slim/csrf
MECCANISMO:
  - Genera token per sessione
  - Valida POST/PUT/DELETE
  - Template injection automatica
```

##### 3️⃣ AuthMiddleware.php
```
SCOPO: Verifica Autenticazione
LOGICA:
  - Check session attiva
  - Verifica ruolo utente
  - Redirect /login se non auth
ESCLUSIONI:
  - /login, /api/*, /public/*
```

##### 4️⃣ RateLimitMiddleware.php
```
SCOPO: Throttling Richieste
STORAGE: File-based (logs/rate_limit/)
LIMITI:
  - Login: 5 req/min per IP
  - API: 100 req/min per key
  - Export: 20 req/min per user
  - Global: 200 req/min per IP
```

##### 5️⃣ ApiKeyMiddleware.php
```
SCOPO: Autenticazione API
HEADER: X-API-Key
VALIDAZIONE:
  - Hash SHA-256 comparison
  - Check scadenza
  - Rate limit per key
  - Audit log
```

##### 6️⃣ SentryMiddleware.php
```
SCOPO: Error Tracking & Monitoring
INTEGRAZIONE: Sentry.io
FEATURES:
  - Exception capture
  - Performance tracing
  - Breadcrumbs trail
  - User context enrichment
```

*Altri Middleware*:
- `RoleMiddleware.php` → RBAC enforcement
- `AdminMiddleware.php` → Admin-only guard
- `RequestIdMiddleware.php` → Request tracking
- `BasePathMiddleware.php` → Base path handling

---

### 📦 Namespace: SecurityLayer/ (9 classi)

**Responsabilità**: Autenticazione, autorizzazione, crittografia, audit

##### 🔢 TotpProvider.php
```
SCOPO: 2FA Time-based OTP
STANDARD: RFC 6238
LIBRERIA: spomky-labs/otphp
METODI:
  + generateSecret(): string
  + getQrCodeUrl(user, secret): string
  + verifyCode(secret, code): bool
CONFIG:
  - Window: ±1 period (30s)
  - Algorithm: SHA1
  - Digits: 6
```

##### 🔐 TotpEncryptionService.php
```
SCOPO: Crittografia TOTP Secrets
LIBRERIA: defuse/php-encryption
ALGORITMO: AES-256-GCM
KEY MANAGEMENT:
  - Key derivation da TOTP_KEY (.env)
  - Rotation support
METODI:
  + encrypt(plaintext): string
  + decrypt(ciphertext): string
```

##### 🔄 RedisSessionHandler.php
```
SCOPO: Session Storage su Redis
INTERFACCIA: SessionHandlerInterface
FEATURES:
  - Distributed sessions
  - Auto-expiration (TTL)
  - Multi-server compatible
REDIS KEYS:
  - Formato: sess:{session_id}
  - TTL: 1800s (30min)
```

##### 🔒 SessionManager.php
```
SCOPO: Gestione Sessioni Sicure
FEATURES:
  - HttpOnly cookies
  - SameSite=Strict
  - Regenerate ID on auth
  - Session timeout check
  - IP binding
METODI:
  + start(): void
  + regenerate(): void
  + destroy(): void
  + get(key): mixed
  + set(key, value): void
```

##### 📜 AuditTrail.php
```
SCOPO: Log Immutabile Eventi
PERSISTENZA: audit_log table (MySQL)
FORMATO: JSON structured logs
EVENTI TRACCIATI:
  - Login/Logout
  - CRUD operations
  - Export dati
  - Config changes
  - Failed auth attempts
GDPR COMPLIANCE:
  - Pseudonimizzazione dati sensibili
  - Retention policy
  - Export capability
```

##### 🛡️ AccessControlList.php
```
SCOPO: RBAC (Role-Based Access Control)
RUOLI:
  - admin: Full access
  - operatore: CRUD soci + read stats
  - utente: Solo lettura
PERMISSIONS:
  - soci.create, soci.read, soci.update, soci.delete
  - documents.upload, documents.delete
  - stats.view, stats.export
  - devtools.access
  - system.config
METODI:
  + can(user, permission): bool
  + hasRole(user, role): bool
```

*Altri SecurityLayer*:
- `Amministratore.php` → Admin entity
- `Operatore.php` → Operator entity
- `UtenteSistema.php` → Base user entity

---

### 📦 Namespace: GestioneSoci/ (8 classi)

**Responsabilità**: Domain models, business logic soci

##### 👤 Socio.php (Aggregato Root)
```
SCOPO: Entità Socio (DDD)
PROPERTIES:
  - CodiceFiscale: string (PK)
  - DatiPersonali: DatiAnagrafici (VO)
  - DatiIscrizione: ModuloIscrizione (VO)
  - DocumentiAssociati: Documento[] (collection)
  - Consensi: ConsensoGDPR (VO)
  - deleted_at: DateTime (Soft Delete)
BUSINESS METHODS:
  + isMoroso(anno): bool
  + aggiornaDati(nuovi): void
  + aggiungiDocumento(doc): void
  + restore(): void (from soft delete)
```

##### 📋 DatiAnagrafici.php (Value Object)
```
PROPERTIES:
  - Nome: string
  - Cognome: string
  - DataNascita: DateTime
  - LuogoNascita: string
  - Sesso: string (M/F)
  - Indirizzo: string
  - Citta: string
  - CAP: string
  - Provincia: string
  - Email: string
  - Telefono: string
METHODS:
  + getNomeCompleto(): string
  + calcolaEta(): int
  + __toString(): string
```

##### 📝 ModuloIscrizione.php (Value Object)
```
PROPERTIES:
  - Matricola: string (auto-generated)
  - AnnoSolare: int
  - QuotaVersata: float (€)
  - MetodoPagamento: string (enum)
  - DataIscrizione: DateTime
  - StatoIscrizione: Enum (attivo/moroso/sospeso)
METHODS:
  + isPagato(): bool
  + calcolaQuotaResidua(): float
```

##### 📄 Documento.php
```
PROPERTIES:
  - IdUnivoco: UUID
  - SocioCF: string (FK)
  - TipoDocumento: string (enum)
  - NomeFile: string
  - PercorsoFile: string
  - HashFile: string (SHA-256)
  - DataCaricamento: DateTime
  - deleted_at: DateTime
METHODS:
  + verificaIntegrita(): bool
  + getUrl(): string
  + delete(): void (soft delete)
```

##### ⚖️ ConsensoGDPR.php (Value Object)
```
PROPERTIES:
  - TrattamentoDati: bool
  - CessioneTerzi: bool
  - Marketing: bool
  - DataFirma: DateTime
  - IPAddress: string
METHODS:
  + isValid(): bool
  + revoca(): void
COMPLIANCE: GDPR Art. 6-7
```

*Altri GestioneSoci*:
- `ISocioRepository.php` → Repository interface
- `IDocumentoRepository.php` → Repository interface
- `SocioFactory.php` → Factory pattern

---

### 📦 Namespace: InfrastrutturaIT/ (9 classi)

**Responsabilità**: Persistenza, database, adattatori esterni

##### 💾 PDOSocioRepository.php
```
SCOPO: Implementazione Repository Socio
INTERFACE: ISocioRepository
DATABASE: MySQL 8.0+
METODI:
  + findAll(filters): Socio[]
  + findByCodiceFiscale(cf): Socio|null
  + save(socio): void
  + delete(cf): void (soft delete)
  + search(query): Socio[]
  + restore(cf): void
  - hydrate(row): Socio
FEATURES:
  - Prepared statements
  - Query Builder fluent
  - Soft delete WHERE clauses
  - Transaction support
```

##### 🔧 QueryBuilder.php
```
SCOPO: SQL Query Builder Fluente
PATTERN: Fluent Interface
METODI:
  + select(cols): self
  + from(table): self
  + where(col, op, val): self
  + orderBy(col, dir): self
  + limit(n): self
  + offset(n): self
  + get(): array
  + toSql(): string
SICUREZZA: SQL Injection safe
```

##### 🗄️ DatabaseConnection.php
```
SCOPO: DB Connection Singleton
PATTERN: Singleton
DATABASE: MySQL via PDO
FEATURES:
  - Connection pooling
  - Lazy initialization
  - Transaction management
  - Error handling
CONFIG:
  - Charset: utf8mb4
  - Collation: utf8mb4_unicode_ci
  - Mode: STRICT_ALL_TABLES
METODI:
  + getInstance(): PDO
  + beginTransaction(): void
  + commit(): void
  + rollback(): void
```

##### 📄 PDODocumentoRepository.php
```
SCOPO: Repository Documenti
STORAGE: File system + metadata DB
METODI:
  + save(documento): void
  + findById(id): Documento
  + findBySocio(cf): Documento[]
  + delete(id): void
  + verifyIntegrity(id): bool
INTEGRITY:
  - Hash SHA-256 on upload
  - Periodic verification job
```

*Altri InfrastrutturaIT*:
- `Archive/GoogleDriveAdapter.php` → Google Drive API
- `Archive/SharePointAdapter.php` → SharePoint API
- `OCREngine.php` → Tesseract OCR wrapper
- `Persistence/` → Data access layer

---

### 📦 Namespace: Service/ (15 classi)

**Responsabilità**: Business services, utilities, integrations

##### 📝 RegistrationService.php
```
SCOPO: Orchestrazione Registrazione Socio
DIPENDENZE:
  - ISocioRepository
  - IDocumentoRepository
  - PdfGenerationService
  - ValidationService
  - EmailService (opt)
WORKFLOW:
  1. Valida dati input
  2. Verifica univocità CF
  3. Crea entity Socio
  4. Genera PDF modulo
  5. Salva in transaction
  6. Invia email benvenuto (async)
  7. Audit log
METODI:
  + registerSocio(data): Socio
  + generateMembershipPdf(socio): string
  - validateUniqueness(cf): void
```

##### ✅ ValidationService.php
```
SCOPO: Validazione Input & Business Rules
REGEX PATTERNS:
  - FISCAL_CODE: /^[A-Z]{6}\\d{2}[A-Z]\\d{2}[A-Z]\\d{3}[A-Z]$/
  - EMAIL: RFC 5322 compliant
  - TELEFONO: /^\\+?\\d{10,15}$/
  - CAP: /^\\d{5}$/
METODI:
  + validateSocioData(data): array (errors)
  + validateEmail(email): bool
  + validateFiscalCode(cf): bool
  + sanitizeInput(input): string
```

##### 📊 PdfGenerationService.php
```
SCOPO: Generazione PDF Documenti
LIBRERIA: dompdf/dompdf
TEMPLATES:
  - modulo_iscrizione.mustache
  - documento_socio.mustache
  - export_elenco.mustache
METODI:
  + generateModuloIscrizione(socio): string
  + generateElencoSoci(soci): string
  - renderTemplate(template, data): string
CONFIG:
  - DPI: 96
  - Default Font: DejaVu Sans
  - Paper Size: A4
```

##### 💾 CacheService.php
```
SCOPO: Cache Layer (Redis)
CLIENT: Predis\\Client
PATTERN: Cache-Aside
METODI:
  + get(key): mixed
  + set(key, val, ttl): bool
  + delete(key): bool
  + flush(): bool
  + remember(key, callback, ttl): mixed
FALLBACK: File-based cache se Redis down
CONFIG:
  - Default TTL: 300s (5min)
  - Prefix: archivio:
```

##### 💿 BackupService.php
```
SCOPO: Backup Automatico Database
TOOL: mysqldump wrapper
METODI:
  + createBackup(): string (path)
  + verifyBackup(file): bool
  + restoreBackup(file): bool
  + rotateOldBackups(days): void
SCHEDULE:
  - Daily: 02:00 AM
  - Retention: 30 giorni
  - Storage: backups/
INTEGRITY:
  - Test restore automatico
  - Checksum verification
```

##### 🔑 ApiKeyManager.php
```
SCOPO: Gestione API Keys
STORAGE: api_keys table
METODI:
  + generate(userId): string
  + validate(key): bool
  + revoke(key): void
  + rotate(oldKey): string
  - hashKey(plain): string (SHA-256)
FEATURES:
  - Scadenza automatica
  - Rate limit tracking
  - Last used timestamp
```

*Altri Service*:
- `SmtpEmailService.php` → PHPMailer wrapper
- `RedisService.php` → Redis client
- `HealthCheckService.php` → System monitoring
- `QueueService.php` → Job queue
- `FiscalCodeCalculator.php` → CF calculator
- ... (9 servizi specializzati)

---

### 📦 Namespace: GraphQL/ (3 classi)

**Responsabilità**: API GraphQL

##### 📐 Schema.php
```
SCOPO: GraphQL Schema Definition
LIBRERIA: webonyx/graphql-php
TYPES:
  - Socio
  - DatiAnagrafici
  - ModuloIscrizione
  - Documento
  - ConsensoGDPR
QUERIES:
  - socio(cf: String!): Socio
  - soci(limit: Int = 10, offset: Int = 0): [Socio]
MUTATIONS:
  - createSocio(input: SocioInput!): Socio
  - updateSocio(cf: String!, input: SocioInput!): Socio
  - deleteSocio(cf: String!): Boolean
```

##### 🔍 Resolvers.php
```
SCOPO: GraphQL Resolvers
PATTERN: Dataloader (N+1 problem)
METODI:
  - resolveSocio(cf): Socio
  - resolveSoci(args): Socio[]
  - createSocio(args): Socio
  - updateSocio(args): Socio
```

##### 📘 Types.php
```
SCOPO: GraphQL Type Definitions
IMPLEMENTAZIONE:
  - Lazy loading types
  - Custom scalars (DateTime, UUID)
  - Input validation
```

---

### 📦 Namespace: Jobs/ (5 classi)

**Responsabilità**: Background jobs, async tasks

##### 📧 SendEmailJob.php
```
SCOPO: Invio Email Async
QUEUE: Redis
RETRY: 3 attempts
METODI:
  + handle(): void
  + fail(Exception): void
```

##### 📄 GeneratePdfJob.php
```
SCOPO: Generazione PDF Async
TIMEOUT: 60s
STORAGE: storage/pdfs/
```

##### 💿 BackupDatabaseJob.php
```
SCOPO: Backup Scheduled
CRON: 0 2 * * * (02:00 daily)
```

*Altri Jobs*:
- `AbstractJob.php` → Base class
- `CleanupOldLogsJob.php` → Log rotation

---

### 📦 Namespace: Debug/ (8 classi)

**Responsabilità**: Debugging, profiling, diagnostics

##### 🔍 LogAnalyzer.php
```
SCOPO: Analisi Log Files
FEATURES:
  - Pattern matching errors
  - Time-based filtering
  - Performance bottleneck detection
```

##### 📊 SystemCheck.php
```
SCOPO: Health Check System
CHECKS:
  - DB connection
  - Redis connection
  - Disk space
  - PHP extensions
  - File permissions
```

*Altri Debug*:
- `GlobalExceptionHandler.php` → Exception handler
- `PerformanceProfiler.php` → Profiling
- `SecurityAudit.php` → Security scanner
- ... (5 tool diagnostici)

---

### 📦 Namespace: DTO/ (1 classe)

##### 📦 PaginationResponse.php
```
SCOPO: DTO Paginazione
PROPERTIES:
  - data: array
  - total: int
  - page: int
  - perPage: int
  - totalPages: int
```

---

### 📦 Namespace: Enum/ (2 classi)

##### 🏷️ TipoDocumento.php
```
VALUES:
  - CARTA_IDENTITA
  - CODICE_FISCALE
  - MODULO_ISCRIZIONE
  - RICEVUTA_PAGAMENTO
  - ALTRO
```

##### 🏷️ StatoIscrizione.php
```
VALUES:
  - ATTIVO
  - MOROSO
  - SOSPESO
  - CANCELLATO
```

---

### 📦 Namespace: Helper/ (1 classe)

##### 🧮 PaginationHelper.php
```
SCOPO: Utility Paginazione
METODI:
  + paginate(query, page, perPage): array
  + buildMetadata(total, page, perPage): array
```

---

### 📦 Namespace: View/ (1 classe)

##### 🎨 CascadingLoader.php
```
SCOPO: Mustache Template Loader
FEATURES:
  - Multi-directory support
  - Template inheritance
  - Caching
```

---

## 🏗️ LAYER 2: PUBLIC/ - WEB ROOT

```
public/
├── index.php              # 🚪 Entry Point Principale
├── api/
│   └── index.php          # 🔷 REST API v1 Entry Point
├── assets/
│   ├── css/
│   │   └── app.min.css    # 🎨 CSS Compilato (Vite)
│   ├── js/
│   │   └── app.min.js     # ⚡ JS Compilato (Vite)
│   └── images/
│       └── logo.png       # 🖼️ Assets Statici
├── uploads/               # 📁 File Upload Utenti (non versionati)
└── favicon.ico            # 🌐 Favicon
```

### 📄 index.php (Entry Point)
```php
// Bootstrap sequence:
1. Autoloader Composer
2. Load .env variables
3. Build DI Container
4. Initialize Slim App
5. Register Middleware Stack
6. Register Routes
7. Run Application
```

---

## 🏗️ LAYER 3: CONFIG/ - CONFIGURAZIONI

```
config/
├── container.php           # 🔧 DI Container Main
├── definitions/            # 📦 Modular DI Definitions
│   ├── core.php           #   → Core services
│   ├── auth.php           #   → Auth services
│   ├── services.php       #   → Business services
│   ├── anagrafica.php     #   → Domain services
│   ├── intelligence.php   #   → AI/ML services
│   └── devtools.php       #   → Dev utilities
├── routes.php              # 🛣️ Routing Definition
├── middleware.php          # 🛡️ Middleware Stack
├── phinx.php               # 🗄️ DB Migrations Config
├── phpstan.neon            # 🔍 Static Analysis Config
├── phpunit.xml             # 🧪 Test Suite Config
└── .php-cs-fixer.dist.php  # 📐 Code Style Config
```

---

## 🏗️ LAYER 4: TEMPLATES/ - UI

```
templates/
├── layouts/
│   ├── base.mustache       # 🖼️ Layout Base
│   └── dashboard.mustache  # 📊 Layout Dashboard
├── pages/
│   ├── login.mustache      # 🔐 Login Page
│   ├── dashboard.mustache  # 🏠 Dashboard
│   ├── soci/
│   │   ├── index.mustache  # 📋 Lista Soci
│   │   ├── show.mustache   # 👤 Dettaglio Socio
│   │   └── form.mustache   # ✏️ Form Socio
│   └── statistics/
│       └── dashboard.mustache # 📈 Statistiche
├── partials/
│   ├── header.mustache     # 🎯 Header
│   ├── footer.mustache     # 🦶 Footer
│   ├── sidebar.mustache    # 📑 Sidebar
│   └── navigation.mustache # 🧭 Nav Menu
└── errors/
    ├── 401.mustache        # 🚫 Unauthorized
    ├── 403.mustache        # ⛔ Forbidden
    ├── 404.mustache        # ❓ Not Found
    ├── 419.mustache        # 🔒 CSRF Token Error
    ├── 429.mustache        # ⏱️ Rate Limit
    ├── 500.mustache        # 💥 Server Error
    └── 503.mustache        # 🔧 Maintenance
```

---

## 🏗️ LAYER 5: DB/ - DATABASE

```
db/
├── migrations/             # 🗄️ Schema Migrations (Phinx)
│   ├── 20251221000000_initial_schema.php
│   ├── 20251221193304_add_audit_log_table.php
│   ├── 20251224102314_add_performance_indices.php
│   ├── 20251226150344_create_jobs_table.php
│   ├── 20251227013541_update_totp_secret_length.php
│   ├── 20251228000001_create_api_keys_table.php
│   └── 20251228000002_add_soft_delete.php
└── seeds/                  # 🌱 Data Seeds
    └── SocioSeeder.php
```

### Schema Database (MySQL 8.0+)

```sql
-- 👥 TABELLA SOCI (Main entity)
soci (
  codice_fiscale VARCHAR(16) PK,
  nome VARCHAR(100),
  cognome VARCHAR(100),
  data_nascita DATE,
  luogo_nascita VARCHAR(100),
  sesso CHAR(1),
  indirizzo VARCHAR(255),
  citta VARCHAR(100),
  cap VARCHAR(5),
  provincia VARCHAR(2),
  email VARCHAR(255),
  telefono VARCHAR(20),
  matricola VARCHAR(20) UNIQUE,
  anno_solare INT,
  quota_versata DECIMAL(10,2),
  metodo_pagamento VARCHAR(50),
  data_iscrizione DATETIME,
  stato_iscrizione ENUM(...),
  consenso_gdpr_dati BOOLEAN,
  consenso_gdpr_terzi BOOLEAN,
  consenso_marketing BOOLEAN,
  data_firma_consenso DATETIME,
  ip_firma_consenso VARCHAR(45),
  created_at TIMESTAMP,
  updated_at TIMESTAMP,
  deleted_at TIMESTAMP NULL -- Soft Delete
)

-- 📄 TABELLA DOCUMENTI
documenti (
  id UUID PK,
  socio_cf VARCHAR(16) FK,
  tipo_documento VARCHAR(50),
  nome_file VARCHAR(255),
  percorso_file VARCHAR(512),
  hash_file CHAR(64), -- SHA-256
  data_caricamento DATETIME,
  deleted_at TIMESTAMP NULL
)

-- 👤 TABELLA UTENTI SISTEMA
utenti (
  id INT AUTO_INCREMENT PK,
  username VARCHAR(50) UNIQUE,
  password_hash VARCHAR(255),
  ruolo ENUM('admin','operatore','utente'),
  totp_secret VARCHAR(255) ENCRYPTED,
  totp_enabled BOOLEAN DEFAULT 0,
  email VARCHAR(255),
  created_at TIMESTAMP,
  updated_at TIMESTAMP
)

-- 📜 TABELLA AUDIT LOG
audit_log (
  id BIGINT AUTO_INCREMENT PK,
  user_id INT,
  action VARCHAR(100),
  resource VARCHAR(100),
  resource_id VARCHAR(255),
  ip_address VARCHAR(45),
  user_agent TEXT,
  request_data JSON,
  response_status INT,
  created_at TIMESTAMP,
  INDEX idx_user_id (user_id),
  INDEX idx_created_at (created_at),
  INDEX idx_action (action)
)

-- 🔑 TABELLA API KEYS
api_keys (
  id INT AUTO_INCREMENT PK,
  user_id INT FK,
  key_hash VARCHAR(64), -- SHA-256
  name VARCHAR(100),
  expires_at DATETIME,
  last_used_at DATETIME,
  created_at TIMESTAMP,
  INDEX idx_key_hash (key_hash)
)

-- 📋 TABELLA JOBS (Queue)
jobs (
  id BIGINT AUTO_INCREMENT PK,
  queue VARCHAR(255),
  payload JSON,
  attempts INT DEFAULT 0,
  reserved_at DATETIME NULL,
  available_at DATETIME,
  created_at TIMESTAMP
)
```

---

## 🏗️ LAYER 6: TESTS/ - TEST SUITE

```
tests/
├── Unit/                   # 🧪 Unit Tests (50+ tests)
│   ├── Domain/
│   │   ├── SocioTest.php
│   │   ├── DatiAnagraficiTest.php
│   │   └── DocumentoTest.php
│   ├── Service/
│   │   ├── ValidationServiceTest.php
│   │   ├── RegistrationServiceTest.php
│   │   └── CacheServiceTest.php
│   └── SecurityLayer/
│       ├── TotpProviderTest.php
│       └── AuditTrailTest.php
│
├── Feature/                # 🎯 Feature Tests (60+ tests)
│   ├── Auth/
│   │   ├── LoginTest.php
│   │   ├── TwoFactorTest.php
│   │   └── LogoutTest.php
│   ├── Soci/
│   │   ├── CreateSocioTest.php
│   │   ├── UpdateSocioTest.php
│   │   ├── DeleteSocioTest.php
│   │   └── SearchSocioTest.php
│   ├── Documents/
│   │   ├── UploadTest.php
│   │   └── IntegrityTest.php
│   ├── API/
│   │   ├── RestApiTest.php
│   │   ├── GraphQLTest.php
│   │   └── RateLimitTest.php
│   └── Security/
│       ├── CsrfTest.php
│       ├── RoleTest.php
│       └── AuditTest.php
│
├── Integration/            # 🔗 Integration Tests (25+ tests)
│   ├── Database/
│   │   ├── SocioRepositoryTest.php
│   │   └── TransactionTest.php
│   ├── Cache/
│   │   └── RedisIntegrationTest.php
│   └── Queue/
│       └── JobExecutionTest.php
│
└── E2E/                    # 🎭 End-to-End Tests (11+ tests)
    └── Playwright/
        ├── LoginFlowTest.ts
        ├── RegistrationFlowTest.ts
        └── DashboardTest.ts
```

### Metriche Test Suite
- **146+ test totali**
- **Coverage**: ~85% codebase
- **PHPStan Level**: 6 (Strict)
- **Framework**: PestPHP + PHPUnit
- **E2E**: Playwright (TypeScript)

---

## 🏗️ LAYER 7: BIN/ - UTILITÀ & SCRIPT

```
bin/
├── debug_tools/            # 🔍 Tool Debugging (30+ scripts)
│   ├── debug_2fa_user.php
│   ├── redis_check.php
│   ├── graphql_debug.php
│   └── ...
├── maintenance/            # 🔧 Manutenzione Sistema (15+ scripts)
│   ├── backup.php
│   ├── check_integrity.php
│   ├── clear_cache.php
│   └── ...
├── setup/                  # ⚙️ Setup Iniziale (5 scripts)
│   ├── create_test_users.php
│   ├── populate_soci.php
│   └── ...
├── tools/                  # 🛠️ Utilità Varie (10+ scripts)
│   ├── health_check.php
│   ├── generate_docs.php
│   └── ...
└── workers/                # ⚙️ Background Workers
    └── queue_worker.php
```

---

## 🏗️ LAYER 8: DOCUMENTAZIONE/

```
Documentazione/
├── Analisi/                # 📊 Analisi Tecniche (10 docs)
│   ├── ANALISI_COMPLETA_PROGETTO_2025.md
│   ├── ultra_deep_audit_report.md
│   └── ...
├── Architettura/           # 🏛️ Documentazione Architettura
│   ├── SYSTEM_DESIGN_DOCUMENT.md
│   ├── diagram-class-v2.3-enterprise.mmd
│   └── ...
├── Manuali/                # 📖 Manuali Utente (12 guides)
│   ├── MANUALE_AMMINISTRATORE.md
│   ├── MANUALE_OPERATORE.md
│   ├── API_REFERENCE.md
│   └── ...
├── Presentazioni/          # 🎤 Presentazioni (4 files)
│   ├── presentazione.md
│   └── presentazione.pdf
└── Report/                 # 📈 Report Tecnici (8 reports)
    ├── CERTIFICAZIONE_100_100_FINALE.md
    ├── VALUTAZIONE_TECNICA_COMMERCIALE_FINALE.md
    └── ...
```

---

## 🎨 TECNOLOGIE & STACK

### Backend PHP
| Componente | Tecnologia | Versione |
|------------|------------|----------|
| **Language** | PHP | 8.2+ |
| **Framework** | Slim Framework | 4.15 |
| **DI Container** | PHP-DI | 7.1 |
| **Template Engine** | Mustache | 3.0 |
| **ORM** | Custom Repository Pattern | - |
| **Validation** | Custom Service | - |
| **Testing** | PestPHP + PHPUnit | 3.8 / 11.5 |
| **Static Analysis** | PHPStan | Level 6 |
| **Code Style** | PHP-CS-Fixer | 3.92 |

### Sicurezza
| Componente | Tecnologia | Standard |
|------------|------------|----------|
| **2FA** | spomky-labs/otphp | RFC 6238 |
| **Encryption** | defuse/php-encryption | AES-256-GCM |
| **Password Hash** | PHP password_hash() | Bcrypt (cost 12) |
| **CSRF** | Slim/csrf | Token-based |
| **Sessions** | Redis Custom Handler | Distributed |
| **Audit** | Custom AuditTrail | GDPR-compliant |

### Database & Cache
| Componente | Tecnologia | Versione |
|------------|------------|----------|
| **Database** | MySQL | 8.0+ |
| **Migrations** | Phinx | 0.16 |
| **Cache** | Redis (Predis client) | 2.2 |
| **Session Store** | Redis | - |
| **Query Builder** | Custom Fluent | - |

### APIs & Integrations
| Componente | Tecnologia | Versione |
|------------|------------|----------|
| **REST API** | Slim PSR-7 | 1.8 |
| **GraphQL** | webonyx/graphql-php | 15.29 |
| **Monitoring** | Sentry | 4.0 |
| **PDF** | DomPDF | 3.1 |
| **Email** | PHPMailer | 7.0 |

### Frontend
| Componente | Tecnologia | Versione |
|------------|------------|----------|
| **Build Tool** | Vite | 6.0 |
| **CSS Framework** | Custom + Vanilla CSS | - |
| **Icons** | Bootstrap Icons | 1.11 |
| **Charts** | Chart.js | 4.4 |
| **E2E Testing** | Playwright | Latest |

### DevOps
| Componente | Tecnologia | Uso |
|------------|------------|-----|
| **Container** | Docker + Docker Compose | Dev/Prod |
| **CI/CD** | GitHub Actions | Automated testing |
| **Deploy** | Railway / Vercel | Cloud hosting |
| **Reverse Proxy** | Nginx | Production |
| **Process Manager** | Supervisor | Workers |

---

## 📊 PATTERN ARCHITETTURALI IMPLEMENTATI

### Design Patterns

1. **Repository Pattern** → Astrazione persistenza
2. **Factory Pattern** → Creazione entity
3. **Strategy Pattern** → Email service switch
4. **Singleton Pattern** → DB Connection
5. **Middleware Pattern** → Security pipeline
6. **Observer Pattern** → Event listeners
7. **Dependency Injection** → Tutto il container
8. **Value Object** → DatiAnagrafici, etc.
9. **Aggregate Root** → Socio entity
10. **Query Object** → QueryBuilder

### Architectural Patterns

1. **Clean Architecture** → Layer separation
2. **Domain-Driven Design (DDD)** → Domain layer
3. **CQRS (parziale)** → Read/Write separation
4. **Event Sourcing (soft)** → Audit trail
5. **Hexagonal Architecture** → Adapters (Google Drive, etc.)

---

## 🔒 SICUREZZA IMPLEMENTATA

### OWASP Top 10 Mitigations

| Vulnerabilità | Mitigazione | Implementazione |
|---------------|-------------|-----------------|
| **A01: Broken Access Control** | ✅ | RBAC + ACL + Role Middleware |
| **A02: Cryptographic Failures** | ✅ | AES-256, Bcrypt, SHA-256 |
| **A03: Injection** | ✅ | Prepared Statements + Input Sanitization |
| **A04: Insecure Design** | ✅ | Clean Architecture + Code Review |
| **A05: Security Misconfiguration** | ✅ | Security Headers + Strict PHP.ini |
| **A06: Vulnerable Components** | ✅ | Composer audit + Dependabot |
| **A07: Auth Failures** | ✅ | 2FA + Rate Limiting + Audit Log |
| **A08: Software/Data Integrity** | ✅ | File Hash + Code Signing |
| **A09: Logging Failures** | ✅ | Sentry + Audit Trail + Monolog |
| **A10: Server-Side Request Forgery** | ✅ | Input Validation + Whitelist |

### Security Features

- ✅ **Two-Factor Authentication (2FA)** - RFC 6238 TOTP
- ✅ **CSRF Protection** - Token-based
- ✅ **Rate Limiting** - Per-IP, Per-User, Per-API-Key
- ✅ **Audit Logging** - Immutable trail
- ✅ **Encryption at Rest** - TOTP secrets
- ✅ **Secure Sessions** - Redis + HttpOnly + SameSite
- ✅ **API Key Management** - Rotation + Expiration
- ✅ **RBAC Authorization** - Granular permissions
- ✅ **Input Validation** - Comprehensive sanitization
- ✅ **Security Headers** - CSP, HSTS, X-Frame-Options
- ✅ **SQL Injection Prevention** - Prepared statements
- ✅ **XSS Prevention** - Template escaping
- ✅ **Password Policy** - Bcrypt cost 12

---

## 📈 SCALABILITÀ & PERFORMANCE

### Caching Strategy
- ✅ **Redis Cache** - Statistics, queries
- ✅ **Template Cache** - Mustache compiled
- ✅ **Session Cache** - Distributed sessions
- ✅ **Query Cache** - MySQL query cache

### Database Optimization
- ✅ **Indices** - Performance indices migration
- ✅ **Query Builder** - Optimized queries
- ✅ **Soft Delete** - No hard delete overhead
- ✅ **Connection Pooling** - ProxySQL ready

### Async Processing
- ✅ **Job Queue** - Redis-based
- ✅ **Email Async** - Background jobs
- ✅ **PDF Generation Async** - Timeout handling
- ✅ **Backup Async** - Scheduled jobs

### Monitoring
- ✅ **Sentry** - Error tracking
- ✅ **Health Checks** - System monitoring
- ✅ **Performance Profiler** - Bottleneck detection
- ✅ **Audit Analytics** - Usage patterns

---

## 🌐 DEPLOYMENT & INFRASTRUTTURA

### Containerizzazione (Docker)

```yaml
# docker-compose.yml
services:
  app:           # PHP 8.2-FPM + Nginx
  mysql:         # MySQL 8.0
  redis:         # Redis 7.0
  proxysql:      # ProxySQL (optional)
  worker:        # Queue worker
```

### CI/CD (GitHub Actions)

```yaml
# .github/workflows/ci.yml
- ✅ Composer install
- ✅ PHPStan Level 6
- ✅ PHP-CS-Fixer check
- ✅ PestPHP test suite
- ✅ Code coverage report
- ✅ Security audit
```

### Hosting Options
1. **Railway** - Recommended (auto-deploy)
2. **Vercel** - Serverless (con adattamenti)
3. **VPS** - Full control (Docker)

---

## 📦 DIPENDENZE PRINCIPALI

### PHP Composer (Production)
```json
{
  "slim/slim": "^4.15",           // Framework
  "php-di/php-di": "^7.1",        // DI Container
  "mustache/mustache": "^3.0",    // Templates
  "slim/psr7": "^1.8",            // PSR-7
  "slim/csrf": "^1.5",            // CSRF
  "monolog/monolog": "^3.9",      // Logging
  "spomky-labs/otphp": "^11.3",   // 2FA
  "predis/predis": "^2.2",        // Redis
  "sentry/sentry": "^4.0",        // Monitoring
  "webonyx/graphql-php": "^15.29",// GraphQL
  "dompdf/dompdf": "^3.1",        // PDF
  "phpmailer/phpmailer": "^7.0",  // Email
  "defuse/php-encryption": "^2.4" // Encryption
}
```

### PHP Composer (Dev)
```json
{
  "pestphp/pest": "^3.8",         // Testing
  "phpstan/phpstan": "^2.1",      // Static Analysis
  "friendsofphp/php-cs-fixer": "^3.92", // Code Style
  "robmorgan/phinx": "^0.16.10",  // Migrations
  "mockery/mockery": "^1.6"       // Mocking
}
```

### Node.js (Frontend)
```json
{
  "vite": "^6.0.0",               // Build tool
  "@playwright/test": "latest",   // E2E testing
  "sass": "^1.83.1"               // CSS preprocessor
}
```

---

## 🎓 CARATTERISTICHE ENTERPRISE

### ✅ Production-Ready Features

1. **Multi-Layer Architecture** - 8 layer separati
2. **Comprehensive Testing** - 146+ test automatizzati
3. **Static Analysis** - PHPStan Level 6
4. **Error Monitoring** - Sentry integration
5. **Audit Trail** - Compliance GDPR
6. **2FA Authentication** - RFC 6238
7. **API GraphQL** - Modern API layer
8. **Caching Strategy** - Redis multi-purpose
9. **Async Jobs** - Background processing
10. **Soft Delete** - Data retention
11. **Backup System** - Automated + verification
12. **Health Monitoring** - System checks
13. **Rate Limiting** - DDoS protection
14. **RBAC** - Granular permissions
15. **Docker Ready** - Full containerization

---

## 📝 NOTE FINALI

### Punti di Forza

1. **Architettura Scalabile** - Clean Architecture + DDD
2. **Sicurezza Enterprise** - OWASP Top 10 + 2FA
3. **Test Coverage** - 85%+ con 146 test
4. **Code Quality** - PHPStan Level 6
5. **Documentation** - 50+ documenti tecnici
6. **Modern Stack** - PHP 8.2, GraphQL, Redis
7. **API Dual** - REST + GraphQL
8. **Monitoring** - Sentry + Health Checks
9. **Maintainability** - DI + Repository Pattern
10. **Deployment** - Docker + CI/CD ready

### Aree di Miglioramento Futuro

1. **Prometheus Metrics** - Esposizione `/metrics`
2. **ProxySQL** - Connection pooling attivo
3. **Elasticsearch** - Full-text search avanzato
4. **Kubernetes** - Orchestrazione container
5. **API Versioning** - v2 REST API
6. **WebSocket** - Real-time notifications
7. **Mobile App** - React Native client
8. **AI/ML** - OCR avanzato + predictions

---

## 📜 CONFORMITÀ & STANDARD

### Standard Rispettati

- ✅ **PSR-1** - Basic Coding Standard
- ✅ **PSR-4** - Autoloading
- ✅ **PSR-7** - HTTP Messages
- ✅ **PSR-11** - Container Interface
- ✅ **PSR-15** - HTTP Handlers
- ✅ **RFC 6238** - TOTP Algorithm
- ✅ **RFC 5322** - Email Validation
- ✅ **OWASP Top 10** - Security
- ✅ **GDPR** - Data Protection
- ✅ **ISO 8601** - DateTime Format

### Quality Metrics

- **PHPStan**: Level 6 (no errors)
- **Test Coverage**: ~85%
- **Code Duplication**: < 5%
- **Cyclomatic Complexity**: < 10 (avg)
- **Maintainability Index**: 85+ (excellent)

---

**© 2026 Soobadur Mohammad Ajmeer. All Rights Reserved.**  
**Fratellanza Militare di Firenze - Archivio Gestionale Enterprise v2.3**
