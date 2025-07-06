<?php

namespace TS\PhpSystemD\Tests\Unit\Info;

use PHPUnit\Framework\TestCase;
use TS\PhpSystemD\Info\UptimeInfo;

class UptimeInfoTest extends TestCase
{
    public function testConstructorAndGetters(): void
    {
        $uptimeInfo = new UptimeInfo(
            ' 10:15:32 up 5 days, 3:22, 2 users, load average: 0.15, 0.22, 0.18',
            0.15,
            0.22,
            0.18
        );
        
        $this->assertEquals(' 10:15:32 up 5 days, 3:22, 2 users, load average: 0.15, 0.22, 0.18', $uptimeInfo->getUptimePretty());
        $this->assertEquals(0.15, $uptimeInfo->getLoadAvg1());
        $this->assertEquals(0.22, $uptimeInfo->getLoadAvg5());
        $this->assertEquals(0.18, $uptimeInfo->getLoadAvg15());
    }

    public function testWithZeroLoadAverages(): void
    {
        $uptimeInfo = new UptimeInfo(
            ' 10:15:32 up 1 day, 0:00, 0 users, load average: 0.00, 0.00, 0.00',
            0.00,
            0.00,
            0.00
        );
        
        $this->assertEquals(0.00, $uptimeInfo->getLoadAvg1());
        $this->assertEquals(0.00, $uptimeInfo->getLoadAvg5());
        $this->assertEquals(0.00, $uptimeInfo->getLoadAvg15());
    }

    public function testWithHighLoadAverages(): void
    {
        $uptimeInfo = new UptimeInfo(
            ' 10:15:32 up 1 day, 0:00, 10 users, load average: 8.50, 7.25, 6.10',
            8.50,
            7.25,
            6.10
        );
        
        $this->assertEquals(8.50, $uptimeInfo->getLoadAvg1());
        $this->assertEquals(7.25, $uptimeInfo->getLoadAvg5());
        $this->assertEquals(6.10, $uptimeInfo->getLoadAvg15());
    }

    public function testLoadAveragePatterns(): void
    {
        // Increasing load (system getting busier)
        $increasingLoad = new UptimeInfo(
            ' 10:15:32 up 1 day, 0:00, 5 users, load average: 2.50, 1.80, 1.20',
            2.50,
            1.80,
            1.20
        );
        
        $this->assertGreaterThan($increasingLoad->getLoadAvg5(), $increasingLoad->getLoadAvg1());
        $this->assertGreaterThan($increasingLoad->getLoadAvg15(), $increasingLoad->getLoadAvg5());
    }
}