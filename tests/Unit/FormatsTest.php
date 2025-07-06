<?php

namespace TS\PhpSystemD\Tests\Unit;

use PHPUnit\Framework\TestCase;
use TS\PhpSystemD\Formats;

class FormatsTest extends TestCase
{
    /**
     * Test valid date parsing with correct systemd format
     * @dataProvider validDateProvider
     */
    public function testParseSystemDUSecValid(string $input, string $expectedDate): void
    {
        $result = Formats::parseSystemDUSec($input);
        
        $this->assertInstanceOf(\DateTimeImmutable::class, $result);
        $this->assertEquals($expectedDate, $result->format('Y-m-d H:i:s'));
    }

    public function validDateProvider(): array
    {
        return [
            // Format: "D Y-m-d H:i:s T"
            ['Mon 2023-05-01 12:30:45 UTC', '2023-05-01 12:30:45'],
            ['Tue 2023-12-26 00:00:00 UTC', '2023-12-26 00:00:00'], // Day matches date
            ['Wed 2023-01-04 23:59:59 UTC', '2023-01-04 23:59:59'], // Day matches date
            ['Thu 2023-06-15 14:22:33 UTC', '2023-06-15 14:22:33'],
            ['Fri 2023-09-29 09:15:00 UTC', '2023-09-29 09:15:00'], // Day matches date
            ['Sat 2023-02-25 18:45:30 UTC', '2023-02-25 18:45:30'], // Day matches date
            ['Sun 2023-07-02 07:00:00 UTC', '2023-07-02 07:00:00'], // Day matches date
        ];
    }

    /**
     * Test invalid date parsing
     * @dataProvider invalidDateProvider
     */
    public function testParseSystemDUSecInvalid(string $input): void
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage("Unable to parse $input as date.");
        
        Formats::parseSystemDUSec($input);
    }

    public function invalidDateProvider(): array
    {
        return [
            [''], // Empty string
            ['Invalid date string'], // Not a date
            ['2023-05-01 12:30:45'], // Missing day of week and timezone
            ['12:30:45 UTC'], // Missing date part
            ['Mon 2023-05-01'], // Missing time and timezone
            ['Mon'], // Just day of week
            ['2023-05-01 UTC'], // Missing time part
        ];
    }

    public function testParseSystemDUSecReturnsDateTimeImmutable(): void
    {
        $result = Formats::parseSystemDUSec('Mon 2023-05-01 12:30:45 UTC');
        
        $this->assertInstanceOf(\DateTimeImmutable::class, $result);
        $this->assertNotInstanceOf(\DateTime::class, $result);
    }

    public function testParseSystemDUSecWithDifferentTimezones(): void
    {
        // Test UTC
        $utcResult = Formats::parseSystemDUSec('Mon 2023-05-01 12:30:45 UTC');
        $this->assertEquals('UTC', $utcResult->getTimezone()->getName());
        
        // Test other timezones (if supported)
        try {
            $cestResult = Formats::parseSystemDUSec('Mon 2023-05-01 12:30:45 CEST');
            $this->assertInstanceOf(\DateTimeImmutable::class, $cestResult);
        } catch (\UnexpectedValueException $e) {
            // If CEST is not supported, that's also acceptable
            $this->assertStringContainsString('Unable to parse', $e->getMessage());
        }
    }

    public function testStaticMethodAccess(): void
    {
        // Verify the method is static and can be called without instantiation
        $reflection = new \ReflectionMethod(Formats::class, 'parseSystemDUSec');
        $this->assertTrue($reflection->isStatic());
        $this->assertTrue($reflection->isPublic());
    }
}