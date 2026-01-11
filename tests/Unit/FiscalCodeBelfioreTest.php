<?php

namespace Tests\Unit;

use FratellanzaMilitare\Service\FiscalCodeCalculator;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class FiscalCodeBelfioreTest extends TestCase
{
    private FiscalCodeCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new FiscalCodeCalculator();
    }

    #[Test]
    public function it_calculates_fiscal_code_for_complex_cities()
    {
        // Forlì -> D704 (Test accenti)
        // Rossi Mario, 01/01/1980, M, Forlì
        // RSS MRA 80A01 D704 D (Calcolato: 107 % 26 = 3 -> D)
        $cf = $this->calculator->calculate('Mario', 'Rossi', '1980-01-01', 'M', 'Forlì');
        $this->assertEquals('RSSMRA80A01D704D', $cf);

        // Reggio nell'Emilia -> H223 (Test spazi e apostrofi)
        // Rossi Mario, 01/01/1980, M, Reggio nell'Emilia
        // Verifichiamo solo che non esploda e il codice comune sia H223
        $cf = $this->calculator->calculate('Mario', 'Rossi', '1980-01-01', 'M', "Reggio nell'Emilia");
        $this->assertStringContainsString('H223', $cf);
    }

    #[Test]
    public function it_calculates_fiscal_code_for_foreign_countries()
    {
        // Francia -> Z110
        $cf = $this->calculator->calculate('Mario', 'Rossi', '1980-01-01', 'M', 'FRANCIA');
        $this->assertStringContainsString('Z110', $cf);

        // Stati Uniti d'America -> Z404
        $cf = $this->calculator->calculate('Mario', 'Rossi', '1980-01-01', 'M', "Stati Uniti d'America");
        $this->assertStringContainsString('Z404', $cf);
    }

    #[Test]
    public function it_is_case_insensitive_for_places()
    {
        $cf1 = $this->calculator->calculate('Mario', 'Rossi', '1980-01-01', 'M', 'Milano');
        $cf2 = $this->calculator->calculate('Mario', 'Rossi', '1980-01-01', 'M', 'MILANO');

        $this->assertEquals($cf1, $cf2);
        $this->assertStringContainsString('F205', $cf1);
    }
}
