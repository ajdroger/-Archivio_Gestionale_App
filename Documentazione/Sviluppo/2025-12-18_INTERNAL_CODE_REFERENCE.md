# Riferimento Tecnico API

Generato automaticamente via `bin/generate_docs.php` il 18/12/2025 22:23:28

## HomeController
**Namespace:** `FratellanzaMilitare\Controller`

*Classe*

### Metodi
- **dashboard**(Psr\Http\Message\ServerRequestInterface $request, Psr\Http\Message\ResponseInterface $response): Psr\Http\Message\ResponseInterface

---

## SocioController
**Namespace:** `FratellanzaMilitare\Controller`

*Classe*

### Metodi
- **list**(Psr\Http\Message\ServerRequestInterface $request, Psr\Http\Message\ResponseInterface $response): Psr\Http\Message\ResponseInterface
- **detail**(Psr\Http\Message\ServerRequestInterface $request, Psr\Http\Message\ResponseInterface $response, array $args): Psr\Http\Message\ResponseInterface

---

## LoginController
**Namespace:** `FratellanzaMilitare\Controller`

*Classe*

### Metodi
- **form**(Psr\Http\Message\ServerRequestInterface $request, Psr\Http\Message\ResponseInterface $response): Psr\Http\Message\ResponseInterface
- **verifyCredentials**(Psr\Http\Message\ServerRequestInterface $request, Psr\Http\Message\ResponseInterface $response): Psr\Http\Message\ResponseInterface
- **form2fa**(Psr\Http\Message\ServerRequestInterface $request, Psr\Http\Message\ResponseInterface $response): Psr\Http\Message\ResponseInterface
- **verify2fa**(Psr\Http\Message\ServerRequestInterface $request, Psr\Http\Message\ResponseInterface $response): Psr\Http\Message\ResponseInterface
- **logout**(Psr\Http\Message\ServerRequestInterface $request, Psr\Http\Message\ResponseInterface $response): Psr\Http\Message\ResponseInterface

---

## DatabaseInspector
**Namespace:** `FratellanzaMilitare\Debug`

*Classe*

### Metodi
- **getTablesSummary**(): array
- **checkIntegrity**(): string
- **getDatabaseSize**(): string

---

## GlobalExceptionHandler
**Namespace:** `FratellanzaMilitare\Debug`

*Classe*

### Metodi
- **handleCli**(Throwable $t): void
- **__invoke**(Psr\Http\Message\ServerRequestInterface $request, Throwable $exception, bool $displayErrorDetails, bool $logErrors, bool $logErrorDetails): Psr\Http\Message\ResponseInterface
- **registerGlobalHandlers**(): void

---

## LogViewer
**Namespace:** `FratellanzaMilitare\Debug`

*Classe*

### Metodi
- **listLogs**(): array
- **readTail**(string $filename, [int $lines]): string

---

## QueryLogger
**Namespace:** `FratellanzaMilitare\Debug`

*Classe*

### Metodi
- **log**(string $query, [array $params], [float $executionTime]): void
- **clearLog**(): void

---

## SystemCheck
**Namespace:** `FratellanzaMilitare\Debug`

*Classe*

### Metodi
- **runDiagnostics**(): array
- **checkRoutes**(array $routes): array
- **printReport**(): void

---

## UserErrorLogger
**Namespace:** `FratellanzaMilitare\Debug`

*Classe*

### Metodi
- **logError**(string $errorCode, string $userId, string $message, [array $context]): void

---

## StatoDocumento
**Namespace:** `FratellanzaMilitare\Enum`

*Enum*

### Metodi
- **cases**(): array

---

## StatoIscrizione
**Namespace:** `FratellanzaMilitare\Enum`

*Enum*

### Metodi
- **cases**(): array

---

## ConsensoGDPR
**Namespace:** `FratellanzaMilitare\GestioneSoci`

*Classe*

### Metodi
- **aggiornaConsensi**(bool $trattamento, bool $cessione, bool $marketing, string $versione, FratellanzaMilitare\SecurityLayer\UtenteSistema $operatore): void
- **revoca**(string $motivo, FratellanzaMilitare\SecurityLayer\UtenteSistema $operatore): void
- **getMetadati**(): string
- **verificaIntegrita**([string $content]): bool

---

## DatiAnagrafici
**Namespace:** `FratellanzaMilitare\GestioneSoci`

*Classe*

---

## Documento
**Namespace:** `FratellanzaMilitare\GestioneSoci`

*Classe Astratta*

### Metodi
- **getMetadati**(): string
- **verificaIntegrita**([string $content]): bool

---

## DocumentoRepository
**Namespace:** `FratellanzaMilitare\GestioneSoci`

*Interfaccia*

### Metodi
- **save**(FratellanzaMilitare\GestioneSoci\Documento $documento, string $socioCf): void
- **findById**(string $uuid): FratellanzaMilitare\GestioneSoci\Documento
- **findBySocio**(string $socioCf): array

---

## ModuloIscrizione
**Namespace:** `FratellanzaMilitare\GestioneSoci`

*Classe*

### Metodi
- **getMetadati**(): string
- **verificaIntegrita**([string $content]): bool

---

## Socio
**Namespace:** `FratellanzaMilitare\GestioneSoci`

*Classe*

### Metodi
- **aggiornaAnagrafica**(FratellanzaMilitare\GestioneSoci\DatiAnagrafici $nuoviDati): void
- **aggiungiDocumento**(FratellanzaMilitare\GestioneSoci\Documento $doc): void
- **verificaMorosita**(): bool

---

## SocioRepository
**Namespace:** `FratellanzaMilitare\GestioneSoci`

*Interfaccia*

### Metodi
- **save**(FratellanzaMilitare\GestioneSoci\Socio $socio): void
- **findByCodiceFiscale**(string $cf): FratellanzaMilitare\GestioneSoci\Socio
- **findAll**(): array
- **delete**(string $cf): void

---

## GoogleDriveAdapter
**Namespace:** `FratellanzaMilitare\InfrastrutturaIT`

*Classe*

### Metodi
- **scan**([string $path]): void
- **upload**(string $fileName, string $content): string
- **download**(string $uuid)
- **delete**(string $uuid): void

---

## ICloudStorage
**Namespace:** `FratellanzaMilitare\InfrastrutturaIT`

*Interfaccia*

### Metodi
- **upload**(string $fileName, string $content): string
- **download**(string $uuid)
- **delete**(string $uuid): void

---

## OCREngine
**Namespace:** `FratellanzaMilitare\InfrastrutturaIT`

*Classe*

### Metodi
- **processaImmagine**(string $bitmap): string
- **estraiCampiChiave**(string $text): array
- **estraiDatiDaImmagine**(string $path): array

---

## DatabaseConnection
**Namespace:** `FratellanzaMilitare\InfrastrutturaIT\Persistence`

*Classe*

### Metodi
- **getConnection**(): PDO

---

## SQLiteDocumentoRepository
**Namespace:** `FratellanzaMilitare\InfrastrutturaIT\Persistence`

*Classe*

### Metodi
- **save**(FratellanzaMilitare\GestioneSoci\Documento $documento, string $socioCf): void
- **findById**(string $uuid): FratellanzaMilitare\GestioneSoci\Documento
- **findBySocio**(string $socioCf): array

---

## SQLiteSocioRepository
**Namespace:** `FratellanzaMilitare\InfrastrutturaIT\Persistence`

*Classe*

### Metodi
- **save**(FratellanzaMilitare\GestioneSoci\Socio $socio): void
- **findByCodiceFiscale**(string $cf): FratellanzaMilitare\GestioneSoci\Socio
- **findAll**(): array
- **delete**(string $cf): void

---

## SharePointAdapter
**Namespace:** `FratellanzaMilitare\InfrastrutturaIT`

*Classe*

### Metodi
- **upload**(string $fileName, string $content): string
- **download**(string $uuid): string
- **delete**(string $uuid): void

---

## AuthMiddleware
**Namespace:** `FratellanzaMilitare\Middleware`

*Classe*

### Metodi
- **process**(Psr\Http\Message\ServerRequestInterface $request, Psr\Http\Server\RequestHandlerInterface $handler): Psr\Http\Message\ResponseInterface

---

## SecurityHeadersMiddleware
**Namespace:** `FratellanzaMilitare\Middleware`

*Classe*

### Metodi
- **process**(Psr\Http\Message\ServerRequestInterface $request, Psr\Http\Server\RequestHandlerInterface $handler): Psr\Http\Message\ResponseInterface

---

## AccessControlList
**Namespace:** `FratellanzaMilitare\SecurityLayer`

*Classe*

### Metodi
- **verificaPermesso**(FratellanzaMilitare\SecurityLayer\UtenteSistema $utente, string $risorsa): bool
- **grant**(string $ruolo, string $permesso): void
- **getPermessi**(string $ruolo): array

---

## Amministratore
**Namespace:** `FratellanzaMilitare\SecurityLayer`

*Classe*

### Metodi
- **creaUtente**(string $username, string $password, [string $tipo]): int
- **revocaPermessi**(int $userId, string $permesso): void
- **visualizzaAuditLog**([array $filters]): array
- **generaReportAudit**([string $periodo]): array
- **autentica**(string $password, [string $codice2FA]): bool
- **cambiaPassword**(string $oldPassword, string $newPassword): void
- **hasPermission**(string $permesso): bool
- **impostaPassword**(string $password): void

---

## AuditTrail
**Namespace:** `FratellanzaMilitare\SecurityLayer`

*Classe*

### Metodi
- **getInstance**(): FratellanzaMilitare\SecurityLayer\AuditTrail
- **logEvento**(FratellanzaMilitare\SecurityLayer\UtenteSistema $utente, string $azione, string $resourceId): void
- **esportaLog**([string $formato]): string
- **ricercaAzioni**([array $filters]): array
- **generaReport**([string $periodo]): array

---

## Operatore
**Namespace:** `FratellanzaMilitare\SecurityLayer`

*Classe*

### Metodi
- **caricaPratica**(string $codiceFiscale, string $tipoPratica, array $dati): bool
- **ricercaSocio**(string $criterio, string $valore): array
- **stampaReport**(string $tipoReport, [array $parametri]): string
- **autentica**(string $password, [string $codice2FA]): bool
- **cambiaPassword**(string $oldPassword, string $newPassword): void
- **hasPermission**(string $permesso): bool
- **impostaPassword**(string $password): void

---

## UtenteSistema
**Namespace:** `FratellanzaMilitare\SecurityLayer`

*Classe Astratta*

### Metodi
- **autentica**(string $password, [string $codice2FA]): bool
- **cambiaPassword**(string $oldPassword, string $newPassword): void
- **hasPermission**(string $permesso): bool
- **impostaPassword**(string $password): void

---


