<?php

namespace Tests\Unit;

use App\Support\Format;
use PHPUnit\Framework\TestCase;

class FormatTest extends TestCase
{
    public function test_money(): void
    {
        $this->assertSame('1.250.000 ₭', Format::money(1250000));
        $this->assertSame('0 ₭', Format::money(0));
        $this->assertSame('− 6.150.000 ₭', Format::money(-6150000));
        $this->assertSame('+ 1.000.000 ₭', Format::money(1000000, true));
    }

    public function test_percent(): void
    {
        $this->assertSame('61,5%', Format::percent(61.5));
        $this->assertSame('100%', Format::percent(100.0));
        $this->assertSame('—', Format::percent(null));
    }

    public function test_phone_normalisation_and_display(): void
    {
        $this->assertSame('+8562055123344', Format::normalizePhone('020 5512 3344'));
        $this->assertSame('+8562055123344', Format::normalizePhone('+856 20 5512 3344'));
        $this->assertSame('+8562055123344', Format::normalizePhone('00856 20 55123344'));
        $this->assertSame('+856 20 5512 3344', Format::phone('02055123344'));
        $this->assertSame('+856 30 512 3456', Format::phone('030 512 3456'));
        $this->assertTrue(Format::isValidPhone('+8562055123344'));
        $this->assertFalse(Format::isValidPhone('+856205512'));
        $this->assertNull(Format::normalizePhone(''));
    }

    public function test_date(): void
    {
        $this->assertSame('15/11/2026', Format::date('2026-11-15'));
        $this->assertSame('—', Format::date(null));
    }

    public function test_lao_initial_skips_leading_vowels(): void
    {
        $this->assertSame('ປ', Format::initial('ເປັງ ຊົ່ງ'));
        $this->assertSame('ຊ', Format::initial('ໄຊ ຊົ່ງ'));
        $this->assertSame('ທ', Format::initial('ທອງ'));
        $this->assertSame('X', Format::initial('xyooj'));
    }
}
