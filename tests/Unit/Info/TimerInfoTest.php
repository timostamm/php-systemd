<?php

namespace TS\PhpSystemD\Tests\Unit\Info;

use PHPUnit\Framework\TestCase;
use TS\PhpSystemD\Info\TimerInfo;

class TimerInfoTest extends TestCase
{
    public function testConstructorAndGetters(): void
    {
        $lastTrigger = new \DateTimeImmutable('2023-05-01 00:00:00');
        $nextElapse = new \DateTimeImmutable('2023-05-02 00:00:00');
        
        $timerInfo = new TimerInfo(
            'backup.timer',
            'Daily backup timer',
            true,     // active
            true,     // enabled
            'active',
            'enabled',
            'Timer status text',
            $lastTrigger,
            $nextElapse,
            'backup.service'
        );
        
        $this->assertEquals('backup.timer', $timerInfo->getId());
        $this->assertEquals('Daily backup timer', $timerInfo->getDescription());
        $this->assertTrue($timerInfo->isActive());
        $this->assertTrue($timerInfo->isEnabled());
        $this->assertEquals('active', $timerInfo->getActiveStatus());
        $this->assertEquals('enabled', $timerInfo->getEnabledStatus());
        $this->assertEquals('Timer status text', $timerInfo->getStatusText());
        $this->assertEquals($lastTrigger, $timerInfo->getLastTrigger());
        $this->assertEquals($nextElapse, $timerInfo->getNextElapse());
        $this->assertEquals('backup.service', $timerInfo->getUnitId());
    }

    public function testWithNullValues(): void
    {
        $timerInfo = new TimerInfo(
            'test.timer',
            null,     // description
            false,    // active
            false,    // enabled
            'inactive',
            'disabled',
            'Test status',
            null,     // lastTrigger
            null,     // nextElapse
            'test.service'
        );
        
        $this->assertEquals('test.timer', $timerInfo->getId());
        $this->assertNull($timerInfo->getDescription());
        $this->assertFalse($timerInfo->isActive());
        $this->assertFalse($timerInfo->isEnabled());
        $this->assertNull($timerInfo->getLastTrigger());
        $this->assertNull($timerInfo->getNextElapse());
        $this->assertEquals('test.service', $timerInfo->getUnitId());
    }

    public function testTimerWithoutTriggerHistory(): void
    {
        // Timer that has never been triggered
        $timer = new TimerInfo(
            'new-timer.timer',
            'New Timer',
            true,
            true,
            'active',
            'enabled',
            'Timer waiting for first trigger',
            null, // Never triggered
            new \DateTimeImmutable('2023-05-02 00:00:00'),
            'new-service.service'
        );
        
        $this->assertNull($timer->getLastTrigger());
        $this->assertInstanceOf(\DateTimeImmutable::class, $timer->getNextElapse());
    }

    public function testTimerWithoutNextElapse(): void
    {
        // Timer that has no next scheduled time
        $timer = new TimerInfo(
            'oneshot.timer',
            'One-shot Timer',
            false,
            false,
            'inactive',
            'disabled',
            'Timer completed',
            new \DateTimeImmutable('2023-05-01 00:00:00'),
            null, // No next trigger
            'oneshot.service'
        );
        
        $this->assertInstanceOf(\DateTimeImmutable::class, $timer->getLastTrigger());
        $this->assertNull($timer->getNextElapse());
    }
}