<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Platform\Enums\ValueFormat;
use Tests\TestCase;

final class ValueFormatTest extends TestCase
{
    public function testFormatValue(): void
    {
        $this->assertEquals("Value", ValueFormat::toString(ValueFormat::Value));

        $this->assertEquals("12,345", ValueFormat::format(12345, ValueFormat::Value));
        $this->assertEquals("-12,345", ValueFormat::format(-12345, ValueFormat::Value));
        $this->assertEquals("0", ValueFormat::format(0, ValueFormat::Value));
        $this->assertEquals("1", ValueFormat::format(1, ValueFormat::Value));
        $this->assertEquals("-1", ValueFormat::format(0xFFFFFFFF, ValueFormat::Value));
    }

    public function testFormatUnsignedValue(): void
    {
        $this->assertEquals("Value (Unsigned)", ValueFormat::toString(ValueFormat::ValueUnsigned));

        $this->assertEquals("12,345", ValueFormat::format(12345, ValueFormat::ValueUnsigned));
        $this->assertEquals("4,294,954,951", ValueFormat::format(-12345, ValueFormat::ValueUnsigned));
        $this->assertEquals("0", ValueFormat::format(0, ValueFormat::ValueUnsigned));
        $this->assertEquals("1", ValueFormat::format(1, ValueFormat::ValueUnsigned));
        $this->assertEquals("4,294,967,295", ValueFormat::format(0xFFFFFFFF, ValueFormat::ValueUnsigned));
    }

    public function testFormatScore(): void
    {
        $this->assertEquals("Score", ValueFormat::toString(ValueFormat::Score));

        $this->assertEquals("012345", ValueFormat::format(12345, ValueFormat::Score));
        $this->assertEquals("-12345", ValueFormat::format(-12345, ValueFormat::Score));
        $this->assertEquals("000000", ValueFormat::format(0, ValueFormat::Score));
        $this->assertEquals("000001", ValueFormat::format(1, ValueFormat::Score));
        $this->assertEquals("-00001", ValueFormat::format(0xFFFFFFFF, ValueFormat::Score));
    }

    public function testFormatFrames(): void
    {
        $this->assertEquals("Time (Frames)", ValueFormat::toString(ValueFormat::TimeFrames));

        $this->assertEquals("3:25.75", ValueFormat::format(12345, ValueFormat::TimeFrames));
        $this->assertEquals("5h42:56.11", ValueFormat::format(1234567, ValueFormat::TimeFrames));
        $this->assertEquals("0:00.00", ValueFormat::format(0, ValueFormat::TimeFrames));
        $this->assertEquals("0:00.01", ValueFormat::format(1, ValueFormat::TimeFrames));
    }

    public function testFormatCentiseconds(): void
    {
        $this->assertEquals("Time (Centiseconds)", ValueFormat::toString(ValueFormat::TimeCentiseconds));

        $this->assertEquals("2:03.45", ValueFormat::format(12345, ValueFormat::TimeCentiseconds));
        $this->assertEquals("3h25:45.67", ValueFormat::format(1234567, ValueFormat::TimeCentiseconds));
        $this->assertEquals("0:03.45", ValueFormat::format(345, ValueFormat::TimeCentiseconds));
        $this->assertEquals("0:00.00", ValueFormat::format(0, ValueFormat::TimeCentiseconds));
        $this->assertEquals("0:00.01", ValueFormat::format(1, ValueFormat::TimeCentiseconds));
    }

    public function testFormatSeconds(): void
    {
        $this->assertEquals("Time (Seconds)", ValueFormat::toString(ValueFormat::TimeSeconds));

        $this->assertEquals("3h25:45", ValueFormat::format(12345, ValueFormat::TimeSeconds));
        $this->assertEquals("5:45", ValueFormat::format(345, ValueFormat::TimeSeconds));
        $this->assertEquals("0:00", ValueFormat::format(0, ValueFormat::TimeSeconds));
        $this->assertEquals("0:01", ValueFormat::format(1, ValueFormat::TimeSeconds));
    }

    public function testFormatMinutes(): void
    {
        $this->assertEquals("Time (Minutes)", ValueFormat::toString(ValueFormat::TimeMinutes));

        $this->assertEquals("205h45", ValueFormat::format(12345, ValueFormat::TimeMinutes));
        $this->assertEquals("5h45", ValueFormat::format(345, ValueFormat::TimeMinutes));
        $this->assertEquals("0h00", ValueFormat::format(0, ValueFormat::TimeMinutes));
        $this->assertEquals("0h01", ValueFormat::format(1, ValueFormat::TimeMinutes));
    }

    public function testFormatSecondsAsMinutes(): void
    {
        $this->assertEquals("Time (Seconds as Minutes)", ValueFormat::toString(ValueFormat::TimeSecondsAsMinutes));

        $this->assertEquals("3h25", ValueFormat::format(12345, ValueFormat::TimeSecondsAsMinutes));
        $this->assertEquals("0h05", ValueFormat::format(345, ValueFormat::TimeSecondsAsMinutes));
        $this->assertEquals("0h00", ValueFormat::format(0, ValueFormat::TimeSecondsAsMinutes));
        $this->assertEquals("0h00", ValueFormat::format(1, ValueFormat::TimeSecondsAsMinutes));
    }

    public function testFormatThousands(): void
    {
        $this->assertEquals("Value (Thousands)", ValueFormat::toString(ValueFormat::ValueThousands));

        $this->assertEquals("12,345,000", ValueFormat::format(12345, ValueFormat::ValueThousands));
        $this->assertEquals("-12,345,000", ValueFormat::format(-12345, ValueFormat::ValueThousands));
        $this->assertEquals("0", ValueFormat::format(0, ValueFormat::ValueThousands));
        $this->assertEquals("1,000", ValueFormat::format(1, ValueFormat::ValueThousands));
        $this->assertEquals("-1,000", ValueFormat::format(0xFFFFFFFF, ValueFormat::ValueThousands));
        $this->assertEquals("2,147,483,647,000", ValueFormat::format(0x7FFFFFFF, ValueFormat::ValueThousands));
    }

    public function testFormatHundreds(): void
    {
        $this->assertEquals("Value (Hundreds)", ValueFormat::toString(ValueFormat::ValueHundreds));

        $this->assertEquals("1,234,500", ValueFormat::format(12345, ValueFormat::ValueHundreds));
        $this->assertEquals("-1,234,500", ValueFormat::format(-12345, ValueFormat::ValueHundreds));
        $this->assertEquals("0", ValueFormat::format(0, ValueFormat::ValueHundreds));
        $this->assertEquals("100", ValueFormat::format(1, ValueFormat::ValueHundreds));
        $this->assertEquals("-100", ValueFormat::format(0xFFFFFFFF, ValueFormat::ValueHundreds));
        $this->assertEquals("214,748,364,700", ValueFormat::format(0x7FFFFFFF, ValueFormat::ValueHundreds));
    }

    public function testFormatTens(): void
    {
        $this->assertEquals("Value (Tens)", ValueFormat::toString(ValueFormat::ValueTens));

        $this->assertEquals("123,450", ValueFormat::format(12345, ValueFormat::ValueTens));
        $this->assertEquals("-123,450", ValueFormat::format(-12345, ValueFormat::ValueTens));
        $this->assertEquals("0", ValueFormat::format(0, ValueFormat::ValueTens));
        $this->assertEquals("10", ValueFormat::format(1, ValueFormat::ValueTens));
        $this->assertEquals("-10", ValueFormat::format(0xFFFFFFFF, ValueFormat::ValueTens));
        $this->assertEquals("21,474,836,470", ValueFormat::format(0x7FFFFFFF, ValueFormat::ValueTens));
    }

    public function testFormatFixed1(): void
    {
        $this->assertEquals("Value (Fixed1)", ValueFormat::toString(ValueFormat::Fixed1));

        $this->assertEquals("1,234.5", ValueFormat::format(12345, ValueFormat::Fixed1));
        $this->assertEquals("-1,234.5", ValueFormat::format(-12345, ValueFormat::Fixed1));
        $this->assertEquals("0.0", ValueFormat::format(0, ValueFormat::Fixed1));
        $this->assertEquals("0.1", ValueFormat::format(1, ValueFormat::Fixed1));
        $this->assertEquals("-0.1", ValueFormat::format(0xFFFFFFFF, ValueFormat::Fixed1));
        $this->assertEquals("214,748,364.7", ValueFormat::format(0x7FFFFFFF, ValueFormat::Fixed1));
    }

    public function testFormatFixed2(): void
    {
        $this->assertEquals("Value (Fixed2)", ValueFormat::toString(ValueFormat::Fixed2));

        $this->assertEquals("123.45", ValueFormat::format(12345, ValueFormat::Fixed2));
        $this->assertEquals("-123.45", ValueFormat::format(-12345, ValueFormat::Fixed2));
        $this->assertEquals("0.00", ValueFormat::format(0, ValueFormat::Fixed2));
        $this->assertEquals("0.01", ValueFormat::format(1, ValueFormat::Fixed2));
        $this->assertEquals("-0.01", ValueFormat::format(0xFFFFFFFF, ValueFormat::Fixed2));
        $this->assertEquals("21,474,836.47", ValueFormat::format(0x7FFFFFFF, ValueFormat::Fixed2));
    }

    public function testFormatFixed3(): void
    {
        $this->assertEquals("Value (Fixed3)", ValueFormat::toString(ValueFormat::Fixed3));

        $this->assertEquals("12.345", ValueFormat::format(12345, ValueFormat::Fixed3));
        $this->assertEquals("-12.345", ValueFormat::format(-12345, ValueFormat::Fixed3));
        $this->assertEquals("0.000", ValueFormat::format(0, ValueFormat::Fixed3));
        $this->assertEquals("0.001", ValueFormat::format(1, ValueFormat::Fixed3));
        $this->assertEquals("-0.001", ValueFormat::format(0xFFFFFFFF, ValueFormat::Fixed3));
        $this->assertEquals("2,147,483.647", ValueFormat::format(0x7FFFFFFF, ValueFormat::Fixed3));
    }
}
