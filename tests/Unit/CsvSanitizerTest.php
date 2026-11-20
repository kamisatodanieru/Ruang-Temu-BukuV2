<?php

namespace Tests\Unit;

use App\Services\CsvSanitizer;
use PHPUnit\Framework\TestCase;

class CsvSanitizerTest extends TestCase
{
    public function test_it_neutralizes_csv_formula_injection_payloads(): void
    {
        $this->assertSame("'=SUM(A1:A10)", CsvSanitizer::sanitize('=SUM(A1:A10)'));
        $this->assertSame("'+cmd|' /C calc'!A0", CsvSanitizer::sanitize("+cmd|' /C calc'!A0"));
        $this->assertSame("'-2+3", CsvSanitizer::sanitize('-2+3'));
        $this->assertSame("'@SUM(1+1)", CsvSanitizer::sanitize('@SUM(1+1)'));
        $this->assertSame("' =HYPERLINK(\"http://evil.example\")", CsvSanitizer::sanitize(' =HYPERLINK("http://evil.example")'));
    }

    public function test_it_preserves_safe_values(): void
    {
        $this->assertSame('Pemrograman Web Aman', CsvSanitizer::sanitize('Pemrograman Web Aman'));
        $this->assertSame('125', CsvSanitizer::sanitize(125));
        $this->assertSame('', CsvSanitizer::sanitize(null));
    }
}
