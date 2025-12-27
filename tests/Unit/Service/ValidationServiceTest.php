<?php

namespace Tests\Unit\Service;

use FratellanzaMilitare\Service\ValidationService;
use PHPUnit\Framework\TestCase;

class ValidationServiceTest extends TestCase
{
    private $validator;

    protected function setUp(): void
    {
        $this->validator = new ValidationService();
    }

    // --- CODICE FISCALE TESTS ---

    public function testValidCodiceFiscale()
    {
        // Example Valid CF (Generated)
        $this->assertTrue($this->validator->isValidCodiceFiscale('RSSMRA80A01H501U'));
    }

    public function testInvalidCodiceFiscaleLength()
    {
        $this->assertFalse($this->validator->isValidCodiceFiscale('RSSMRA80A01H501')); // Short
        $this->assertFalse($this->validator->isValidCodiceFiscale('RSSMRA80A01H501UU')); // Long
    }

    public function testInvalidCodiceFiscaleFormat()
    {
        // Wrong characters in specific positions
        $this->assertFalse($this->validator->isValidCodiceFiscale('12345680A01H501U')); // Numbers in Name
        $this->assertFalse($this->validator->isValidCodiceFiscale('RSSMRA80A01H5011')); // Last char must be letter
    }

    // --- EMAIL TESTS ---

    public function testValidEmail()
    {
        $this->assertTrue($this->validator->isValidEmail('test@example.com'));
        $this->assertTrue($this->validator->isValidEmail('user.name+tag@domain.co.uk'));
    }

    public function testInvalidEmail()
    {
        $this->assertFalse($this->validator->isValidEmail('plainaddress'));
        $this->assertFalse($this->validator->isValidEmail('@missingusername.com'));
        $this->assertFalse($this->validator->isValidEmail('user@.com.my'));
    }

    // --- FILE UPLOAD TESTS ---

    public function testValidPdfUpload()
    {
        // 1MB PDF
        $this->assertTrue($this->validator->isValidFileUpload('application/pdf', 1024 * 1024));
    }

    public function testValidImageUpload()
    {
        // 2MB JPG
        $this->assertTrue($this->validator->isValidFileUpload('image/jpeg', 2 * 1024 * 1024));
    }

    public function testInvalidMimeType()
    {
        $this->assertFalse($this->validator->isValidFileUpload('application/x-executable', 1024));
    }

    public function testFileTooLarge()
    {
        // 6MB PDF (Limit is 5MB)
        $this->assertFalse($this->validator->isValidFileUpload('application/pdf', 6 * 1024 * 1024));
    }
}
