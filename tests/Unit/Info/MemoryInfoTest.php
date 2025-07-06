<?php

namespace TS\PhpSystemD\Tests\Unit\Info;

use PHPUnit\Framework\TestCase;
use TS\PhpSystemD\Info\MemoryInfo;

class MemoryInfoTest extends TestCase
{
    public function testConstructorAndGetters(): void
    {
        $memoryInfo = new MemoryInfo(
            8388608,  // 8MB total
            2097152,  // 2MB used
            4194304,  // 4MB swap total
            1048576   // 1MB swap used
        );
        
        $this->assertEquals(8388608, $memoryInfo->getTotalBytes());
        $this->assertEquals(2097152, $memoryInfo->getUsedBytes());
        $this->assertEquals(4194304, $memoryInfo->getSwapTotalBytes());
        $this->assertEquals(1048576, $memoryInfo->getSwapUsedBytes());
    }

    public function testWithZeroValues(): void
    {
        $memoryInfo = new MemoryInfo(0, 0, 0, 0);
        
        $this->assertEquals(0, $memoryInfo->getTotalBytes());
        $this->assertEquals(0, $memoryInfo->getUsedBytes());
        $this->assertEquals(0, $memoryInfo->getSwapTotalBytes());
        $this->assertEquals(0, $memoryInfo->getSwapUsedBytes());
    }

    public function testWithLargeValues(): void
    {
        // 64GB RAM, 32GB swap scenario
        $memoryInfo = new MemoryInfo(
            68719476736,  // 64GB total
            34359738368,  // 32GB used
            34359738368,  // 32GB swap total
            8589934592    // 8GB swap used
        );
        
        $this->assertEquals(68719476736, $memoryInfo->getTotalBytes());
        $this->assertEquals(34359738368, $memoryInfo->getUsedBytes());
        $this->assertEquals(34359738368, $memoryInfo->getSwapTotalBytes());
        $this->assertEquals(8589934592, $memoryInfo->getSwapUsedBytes());
    }

    public function testNoSwapScenario(): void
    {
        $memoryInfo = new MemoryInfo(16777216, 8388608, 0, 0);
        
        $this->assertEquals(16777216, $memoryInfo->getTotalBytes());
        $this->assertEquals(8388608, $memoryInfo->getUsedBytes());
        $this->assertEquals(0, $memoryInfo->getSwapTotalBytes());
        $this->assertEquals(0, $memoryInfo->getSwapUsedBytes());
    }
}