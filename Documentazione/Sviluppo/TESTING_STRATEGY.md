# 🧪 TESTING STRATEGY MCAG
## Strategia Completa Quality Assurance

**Versione**: 1.0  
**Data**: 27 Gennaio 2026

---

## 1. TESTING PYRAMID

```
         /\
        /  \    E2E Tests (5%)
       /____\
      /      \  Integration (15%)
     /________\
    /          \ Feature Tests (30%)
   /____________\
  /              \ Unit Tests (50%)
 /________________\
```

### Target Distribution

- **Unit Tests**: 103 (50%) - Fast, isolated
- **Feature Tests**: 62 (30%) - HTTP workflows
- **Integration Tests**: 31 (15%) - DB, APIs
- **E2E Tests**: 10 (5%) - Browser (Playwright)

**Total**: 206 tests (current), Target 250+ by Q3 2026

---

## 2. UNIT TESTING

### 2.1 Pest Configuration

```php
// tests/Pest.php
<?php

uses(Tests\TestCase::class)->in('Feature');
uses(Tests\UnitTestCase::class)->in('Unit');

function mockUser(array $attributes = []): User
{
    return User::factory()->create($attributes);
}
```

### 2.2 Example Unit Test

```php
// tests/Unit/FiscalCodeCalculatorTest.php
<?php

use App\Service\FiscalCodeCalculator;

it('calculates correct fiscal code for male', function () {
    $calculator = new FiscalCodeCalculator();
    
    $cf = $calculator->calculate([
        'cognome' => 'Rossi',
        'nome' => 'Mario',
        'sesso' => 'M',
        'data_nascita' => '1990-05-15',
        'comune_nascita' => 'Roma',
    ]);
    
    expect($cf)->toBe('RSSMRA90E15H501');
});

it('throws exception for invalid date', function () {
    $calculator = new FiscalCodeCalculator();
    
    $calculator->calculate([
        'data_nascita' => '2030-13-45',  // Invalid
    ]);
})->throws(InvalidArgumentException::class);
```

### 2.3 Coverage Target

```bash
# Run with coverage
./vendor/bin/pest --coverage --min=90

# Generate HTML report
./vendor/bin/pest --coverage-html=coverage/

# View: coverage/index.html
```

**Minimum Coverage**:
- Overall: ≥ 90%
- New code: ≥ 95%
- Critical paths (auth, payment): 100%

---

## 3. FEATURE TESTING

### 3.1 HTTP Test Example

```php
// tests/Feature/SocioCreationTest.php
<?php

it('creates socio with valid data', function () {
    $admin = mockUser(['role' => 'admin']);
    
    $response = $this->actingAs($admin)
        ->post('/soci/create', [
            'nome' => 'Giovanni',
            'cognome' => 'Bianchi',
            'email' => 'giovanni.bianchi@example.com',
            'codice_fiscale' => 'BNCGNN85M10H501Z',
            'data_nascita' => '1985-08-10',
        ]);
    
    $response->assertStatus(201);
    $response->assertJson(['success' => true]);
    
    $this->assertDatabaseHas('soci', [
        'email' => 'giovanni.bianchi@example.com',
    ]);
});

it('rejects socio creation without authentication', function () {
    $response = $this->post('/soci/create', [
        'nome' => 'Test',
    ]);
    
    $response->assertStatus(401);
});
```

### 3.2 Database Transactions

```php
// tests/TestCase.php
<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

class TestCase extends BaseTestCase
{
    use RefreshDatabase;  // Rollback after each test
}
```

---

## 4. INTEGRATION TESTING

### 4.1 Email Integration

```php
// tests/Integration/EmailServiceTest.php
<?php

use App\Service\SmtpEmailService;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    Mail::fake();
});

it('sends welcome email on socio creation', function () {
    $socio = Socio::factory()->create();
    
    event(new SocioCreated($socio));
    
    Mail::assertSent(WelcomeEmail::class, function ($mail) use ($socio) {
        return $mail->hasTo($socio->email);
    });
});
```

### 4.2 External API Integration

```php
// tests/Integration/PaymentGatewayTest.php
<?php

use App\Service\StripePaymentService;

it('processes payment via Stripe', function () {
    $service = new StripePaymentService(
        apiKey: config('services.stripe.test_key')
    );
    
    $result = $service->charge([
        'amount' => 5000,  // €50.00
        'currency' => 'eur',
        'source' => 'tok_visa',  // Test token
    ]);
    
    expect($result->status)->toBe('succeeded');
});
```

---

## 5. E2E TESTING (Playwright)

### 5.1 Setup

```javascript
// playwright.config.js
module.exports = {
  testDir: './tests/e2e',
  use: {
    baseURL: 'http://localhost:8000',
    screenshot: 'only-on-failure',
    video: 'retain-on-failure',
  },
};
```

### 5.2 Example E2E Test

```javascript
// tests/e2e/socio-crud.spec.js
const { test, expect } = require('@playwright/test');

test('complete socio CRUD flow', async ({ page }) => {
  // Login
  await page.goto('/login');
  await page.fill('input[name="email"]', 'admin@mcag.test');
  await page.fill('input[name="password"]', 'password');
  await page.click('button[type="submit"]');
  
  // Navigate to soci
  await page.click('text=Anagrafica Soci');
  
  // Create new socio
  await page.click('text=Nuovo Socio');
  await page.fill('input[name="nome"]', 'Test E2E');
  await page.fill('input[name="cognome"]', 'Playwright');
  await page.fill('input[name="email"]', 'test.e2e@example.com');
  await page.click('button:has-text("Salva")');
  
  // Verify success message
  await expect(page.locator('.alert-success')).toContainText('Socio creato');
  
  // Search for created socio
  await page.fill('input[name="search"]', 'Test E2E');
  await page.press('input[name="search"]', 'Enter');
  
  await expect(page.locator('table')).toContainText('Test E2E Playwright');
});
```

---

## 6. SECURITY TESTING

### 6.1 CSRF Protection Test

```php
it('rejects request without CSRF token', function () {
    $response = $this->post('/soci/create', [
        'nome' => 'Hacker',
    ], [
        'X-CSRF-TOKEN' => 'invalid-token',
    ]);
    
    $response->assertStatus(419);  // CSRF token mismatch
});
```

### 6.2 SQL Injection Test

```php
it('prevents SQL injection in search', function () {
    $response = $this->get('/soci/search?q=' . urlencode("'; DROP TABLE soci; --"));
    
    $response->assertStatus(200);
    $this->assertDatabaseHas('soci', ['id' => 1]);  // Table still exists
});
```

### 6.3 XSS Prevention Test

```php
it('escapes HTML in soci name display', function () {
    $socio = Socio::factory()->create([
        'nome' => '<script>alert("XSS")</script>',
    ]);
    
    $response = $this->get('/soci/' . $socio->id);
    
    $response->assertDontSee('<script>', false);  // Raw HTML not present
    $response->assertSee('&lt;script&gt;');  // Escaped version present
});
```

---

## 7. PERFORMANCE TESTING

### 7.1 Response Time Test

```php
it('loads dashboard under 500ms', function () {
    $start = microtime(true);
    
    $this->actingAs(mockUser())
        ->get('/dashboard')
        ->assertStatus(200);
    
    $duration = (microtime(true) - $start) * 1000;
    
    expect($duration)->toBeLessThan(500);  // < 500ms
});
```

### 7.2 Database Query Count

```php
use Illuminate\Support\Facades\DB;

it('lists 100 soci with max 3 queries', function () {
    Socio::factory()->count(100)->create();
    
    DB::enableQueryLog();
    
    $this->get('/soci');
    
    $queries = DB::getQueryLog();
    expect(count($queries))->toBeLessThanOrEqual(3);
});
```

---

## 8. TEST DATA MANAGEMENT

### 8.1 Factories

```php
// database/factories/SocioFactory.php
<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class SocioFactory extends Factory
{
    public function definition(): array
    {
        return [
            'matricola' => 'SOC-' . $this->faker->unique()->numberBetween(10000, 99999),
            'nome' => $this->faker->firstName(),
            'cognome' => $this->faker->lastName(),
            'email' => $this->faker->unique()->safeEmail(),
            'codice_fiscale' => $this->faker->taxId(),
            'data_nascita' => $this->faker->date('Y-m-d', '-18 years'),
        ];
    }
}
```

### 8.2 Seeders (Test Environment)

```php
// database/seeders/TestSeeder.php
<?php

class TestSeeder extends Seeder
{
    public function run()
    {
        // Admin user
        User::factory()->create([
            'email' => 'admin@mcag.test',
            'role' => 'admin',
        ]);
        
        // Sample soci
        Socio::factory()->count(50)->create();
    }
}
```

---

## 9. CONTINUOUS INTEGRATION

### 9.1 GitHub Actions Workflow

```yaml
# .github/workflows/tests.yml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_DATABASE: mcag_test
          MYSQL_ROOT_PASSWORD: password
        ports:
          - 3306:3306
      redis:
        image: redis:7
        ports:
          - 6379:6379
    
    steps:
      - uses: actions/checkout@v3
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
          extensions: mbstring, pdo_mysql, redis
          coverage: xdebug
      
      - name: Install dependencies
        run: composer install
      
      - name: Run tests
        run: ./vendor/bin/pest --coverage --min=90
        env:
          DB_HOST: 127.0.0.1
          DB_DATABASE: mcag_test
          DB_USERNAME: root
          DB_PASSWORD: password
```

---

## 10. TESTING BEST PRACTICES

### 10.1 AAA Pattern (Arrange-Act-Assert)

```php
it('calculates total with discount', function () {
    // Arrange
    $calculator = new PriceCalculator();
    $items = [100, 200, 300];
    $discount = 0.1;  // 10%
    
    // Act
    $total = $calculator->calculate($items, $discount);
    
    // Assert
    expect($total)->toBe(540);  // (600 * 0.9)
});
```

### 10.2 Test Naming

```php
// GOOD: Descriptive, readable
it('sends reminder email 7 days before expiration')

// BAD: Vague
it('test email')
```

### 10.3 One Assertion Per Test (Prefer)

```php
// GOOD
it('returns 201 status', function () {
    expect($response->status())->toBe(201);
});

it('returns success JSON', function () {
    expect($response->json())->toHaveKey('success');
});

// ACCEPTABLE (related assertions)
it('creates socio successfully', function () {
    expect($response->status())->toBe(201);
    expect($response->json('success'))->toBeTrue();
    $this->assertDatabaseHas('soci', ['email' => 'test@example.com']);
});
```

---

## 11. MOCKING & SPIES

### 11.1 Mock External Service

```php
use App\Service\OllamaService;
use Mockery;

it('generates AI suggestion using mocked Ollama', function () {
    $mock = Mockery::mock(OllamaService::class);
    $mock->shouldReceive('generate')
        ->once()
        ->with('Optimize schedule for 10 employees')
        ->andReturn('Suggestion: Split into 2 shifts of 5');
    
    $this->app->instance(OllamaService::class, $mock);
    
    $result = (new WorkshiftOptimizer($mock))->optimize([...]);
    
    expect($result)->toContain('Split into 2 shifts');
});
```

### 11.2 Spy on Method Calls

```php
use Illuminate\Support\Facades\Log;

it('logs socio creation', function () {
    Log::spy();
    
    Socio::factory()->create();
    
    Log::shouldHaveReceived('info')
        ->once()
        ->with('Socio created', Mockery::type('array'));
});
```

---

## 12. TEST METRICS & REPORTING

### 12.1 Coverage Report

```bash
./vendor/bin/pest --coverage-html=coverage/

# Open coverage/index.html
# Green: >80%, Yellow: 50-80%, Red: <50%
```

### 12.2 Mutation Testing (Optional)

```bash
composer require --dev infection/infection

./vendor/bin/infection

# Mutation Score: 85%+ target
# Ensures tests catch actual bugs, not just execute code
```

---

## CONCLUSION

Testing strategy MCAG garantisce:
- ✅ **206+ tests** (100% pass rate)
- ✅ **92% coverage** (target 95%)
- ✅ **Pyramid distribution** (50-30-15-5)
- ✅ **CI/CD integration** (blocked on test failure)
- ✅ **Security testing** (CSRF, SQLi, XSS)

**Confidence**: Deploy to production con fiducia grazie a test suite robusta.

**© 2026 Soobadur Mohammad Ajmeer**
