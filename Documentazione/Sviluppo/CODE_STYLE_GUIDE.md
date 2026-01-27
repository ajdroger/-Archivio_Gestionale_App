# 📝 CODE STYLE GUIDE MCAG
## Coding Standards & Best Practices

**Versione**: 1.0  
**Data**: 27 Gennaio 2026

---

## 1. PHP STANDARDS (PSR-12 Extended)

### 1.1 File Structure

```php
<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Service\ValidationService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Dashboard Controller for Admin Panel
 * 
 * Handles all admin dashboard operations including
 * statistics display and configuration management.
 * 
 * @package App\Controller\Admin
 * @author Soobadur Mohammad Ajmeer
 * @since 8.0.0
 */
final class DashboardController
{
    // Properties
    private ValidationService $validator;
    
    // Constructor
    public function __construct(ValidationService $validator)
    {
        $this->validator = $validator;
    }
    
    // Methods
    public function index(ServerRequestInterface $request): ResponseInterface
    {
        // Implementation
    }
}
```

### 1.2 Naming Conventions

**Classes**: PascalCase
```php
class UserRepository { }
class TwoFactorAuthService { }
```

**Methods**: camelCase
```php
public function getUserById(int $id): ?User
public function sendWelcomeEmail(User $user): void
```

**Properties**: camelCase
```php
private string $emailAddress;
protected int $maxRetries = 3;
```

**Constants**: UPPER_SNAKE_CASE
```php
public const MAX_LOGIN_ATTEMPTS = 5;
private const DEFAULT_TIMEOUT = 30;
```

### 1.3 Type Declarations (Always)

```php
// GOOD: Strict types
public function calculateTotal(array $items, float $taxRate): float
{
    return array_sum($items) * (1 + $taxRate);
}

// BAD: No types
public function calculateTotal($items, $taxRate)
{
    return array_sum($items) * (1 + $taxRate);
}
```

### 1.4 Return Types

```php
// GOOD
public function findUserById(int $id): ?User { }
public function getAllActive(): array { }
public function delete(int $id): void { }

// BAD
public function findUserById($id) { }  // No return type
```

---

## 2. CLEAN ARCHITECTURE PATTERNS

### 2.1 Dependency Injection

```php
// GOOD: Constructor injection
class SocioService
{
    public function __construct(
        private SocioRepository $repository,
        private ValidationService $validator,
        private EmailService $mailer
    ) {}
}

// BAD: Direct instantiation
class SocioService
{
    private $repository;
    
    public function __construct()
    {
        $this->repository = new PDOSocioRepository();  // ❌ Tight coupling
    }
}
```

### 2.2 Single Responsibility

```php
// GOOD: One responsibility
class PasswordHasher
{
    public function hash(string $password): string
    {
        return password_hash($password, PASSWORD_ARGON2ID);
    }
    
    public function verify(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }
}

// BAD: Multiple responsibilities
class UserManager
{
    public function hashPassword() { }
    public function sendEmail() { }
    public function validateInput() { }
    public function saveToDatabase() { }  // ❌ Too many concerns
}
```

---

## 3. JAVASCRIPT/ES6+ STANDARDS

### 3.1 Const/Let (Never Var)

```javascript
// GOOD
const API_ENDPOINT = '/api/soci';
let currentPage = 1;

// BAD
var endpoint = '/api/soci';  // ❌ Never use var
```

### 3.2 Arrow Functions

```javascript
// GOOD
const filterActive = (items) => items.filter(item => item.active);

// GOOD (multiline)
const processUsers = (users) => {
    return users
        .filter(u => u.active)
        .map(u => u.name);
};

// BAD
var filterActive = function(items) {
    return items.filter(function(item) {
        return item.active;
    });
};
```

### 3.3 Template Literals

```javascript
// GOOD
const message = `Welcome, ${user.name}!`;
const html = `
    <div class="card">
        <h3>${title}</h3>
    </div>
`;

// BAD
const message = 'Welcome, ' + user.name + '!';
```

---

## 4. SQL STYLE

### 4.1 Keywords UPPERCASE

```sql
-- GOOD
SELECT 
    id,
    nome,
    cognome,
    email
FROM soci
WHERE active = 1
    AND created_at >= '2026-01-01'
ORDER BY cognome ASC, nome ASC
LIMIT 100;

-- BAD
select id, nome from soci where active = 1;  -- ❌ Lowercase, no formatting
```

### 4.2 Avoid SELECT *

```sql
-- GOOD
SELECT id, email, nome FROM users;

-- BAD
SELECT * FROM users;  -- ❌ Unclear, inefficient
```

---

## 5. DOCUMENTATION (PHPDoc)

### 5.1 Class Documentation

```php
/**
 * Fiscal Code Calculator following Italian regulations
 * 
 * Implements the algorithm for calculating Italian fiscal codes
 * (Codice Fiscale) based on personal data. Supports both individuals
 * and foreign-born citizens.
 * 
 * @package App\Service
 * @author Soobadur Mohammad Ajmeer
 * @since 1.0.0
 * @see https://www.agenziaentrate.gov.it/
 */
class FiscalCodeCalculator
{
```

### 5.2 Method Documentation

```php
/**
 * Calculate fiscal code from user data
 * 
 * @param array $data User data containing:
 *                    - cognome (string): Last name
 *                    - nome (string): First name
 *                    - sesso (string): M or F
 *                    - data_nascita (string): YYYY-MM-DD format
 *                    - comune_nascita (string): City code
 * 
 * @return string The calculated 16-character fiscal code
 * 
 * @throws InvalidArgumentException If required fields missing
 * @throws \RuntimeException If calculation fails
 * 
 * @example
 * $cf = $calculator->calculate([
 *     'cognome' => 'Rossi',
 *     'nome' => 'Mario',
 *     'sesso' => 'M',
 *     'data_nascita' => '1990-01-15',
 *     'comune_nascita' => 'H501',  // Roma
 * ]);
 * // Returns: RSSMRA90A15H501Z
 */
public function calculate(array $data): string
{
```

---

## 6. ERROR HANDLING

### 6.1 Specific Exceptions

```php
// GOOD: Specific exceptions
throw new InvalidArgumentException("Email format invalid: {$email}");
throw new UserNotFoundException("User ID {$id} not found");
throw new InsufficientPermissionsException("Admin role required");

// BAD: Generic exception
throw new Exception("Error");  // ❌ Too vague
```

### 6.2 Try-Catch Specificity

```php
// GOOD
try {
    $socio = $this->repository->findById($id);
} catch (NotFoundException $e) {
    // Handle not found
} catch (DatabaseException $e) {
    // Handle DB error
}

// BAD
try {
    $socio = $this->repository->findById($id);
} catch (\Exception $e) {  // ❌ Too broad
    // Catches everything
}
```

---

## 7. TESTING CONVENTIONS

### 7.1 Test Naming

```php
// GOOD: Descriptive
it('returns null when user does not exist')
it('sends email after successful registration')
it('throws exception for invalid fiscal code format')

// BAD: Vague
it('test user') 
it('works')
```

---

## 8. CSS/SCSS CONVENTIONS

### 8.1 BEM Methodology

```css
/* GOOD: BEM naming */
.card { }
.card__header { }
.card__body { }
.card--highlighted { }

/* BAD: Unclear hierarchy */
.cardHeader { }
.card-body-text { }
```

### 8.2 Variables

```css
/* GOOD: CSS Custom Properties */
:root {
    --color-primary: hsl(220, 90%, 56%);
    --color-success: hsl(142, 71%, 45%);
    --spacing-base: 1rem;
}

.button {
    background: var(--color-primary);
    padding: var(--spacing-base);
}
```

---

## 9. SECURITY PRACTICES

### 9.1 Input Validation

```php
// GOOD: Validate then sanitize
public function createUser(array $data): User
{
    $validated = $this->validator->validate($data, [
        'email' => 'required|email',
        'password' => 'required|min:12',
    ]);
    
    $safe = $this->sanitizer->sanitize($validated);
    
    return $this->repository->create($safe);
}
```

### 9.2 SQL Injection Prevention

```php
// GOOD: Prepared statements
$stmt = $pdo->prepare('SELECT * FROM users WHERE email = :email');
$stmt->execute(['email' => $email]);

// BAD: String concatenation
$sql = "SELECT * FROM users WHERE email = '{$email}'";  // ❌ SQLi vulnerable
```

---

## 10. PERFORMANCE

### 10.1 Eager Loading

```php
// GOOD: Eager load relations
$soci = Socio::with('documenti', 'moduli')->get();

// BAD: N+1 queries
$soci = Socio::all();
foreach ($soci as $socio) {
    echo $socio->documenti->count();  // ❌ N queries
}
```

### 10.2 Caching

```php
// GOOD: Cache expensive operations
$stats = cache()->remember('dashboard_stats', 300, function () {
    return $this->calculateExpensiveStats();
});

// BAD: Recalculate every time
$stats = $this->calculateExpensiveStats();
```

---

## CONCLUSION

Seguire questi standard garantisce:
- ✅ **Codebase uniforme** (facilita onboarding)
- ✅ **Manutenibilità alta** (meno bug, refactor facile)
- ✅ **Performance ottimali** (best practices applicate)
- ✅ **Security by default** (pattern sicuri enforced)

**© 2026 Soobadur Mohammad Ajmeer**
