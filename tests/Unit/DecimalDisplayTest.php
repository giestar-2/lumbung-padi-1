<?php

namespace Tests\Unit;

use App\Decimal;
use PHPUnit\Framework\TestCase;

class DecimalDisplayTest extends TestCase
{
    public function test_weights_keep_meaningful_decimals_without_trailing_zeros(): void
    {
        $this->assertSame('1', Decimal::display('1.000', 3));
        $this->assertSame('1,5', Decimal::display('1.500', 3));
        $this->assertSame('1.800', Decimal::display('1800.000', 3));
        $this->assertSame('0,125', Decimal::display('0.125', 3));
        $this->assertSame('0', Decimal::display('0.000', 3));
        $this->assertSame('-1,25', Decimal::display('-1.250', 3));
    }

    public function test_money_uses_indonesian_separators_without_empty_decimals(): void
    {
        $this->assertSame('18.000', Decimal::display('18000.00'));
        $this->assertSame('18.000,5', Decimal::display('18000.50'));
        $this->assertSame('18.000,05', Decimal::display('18000.05'));
        $this->assertSame('1.234,57', Decimal::display('1234.56789'));
    }

    public function test_input_removes_padding_without_thousands_separators(): void
    {
        $this->assertSame('1', Decimal::input('1.000'));
        $this->assertSame('1.25', Decimal::input('1.250'));
        $this->assertSame('18000', Decimal::input('18000.00'));
        $this->assertSame('1000', Decimal::input('1000'));
        $this->assertSame('0', Decimal::input('0.00'));
    }
}
