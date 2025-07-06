<?php

namespace TS\PhpSystemD\Tests\Unit\Info;

use PHPUnit\Framework\TestCase;
use TS\PhpSystemD\Info\ServiceInfo;

class ServiceInfoTest extends TestCase
{
    public function testConstructorAndGetters(): void
    {
        $lastStatusChange = new \DateTimeImmutable('2023-05-01 12:30:45');
        
        $serviceInfo = new ServiceInfo(
            'nginx.service',
            'forking',
            'The nginx HTTP Server',
            true,    // active
            true,    // enabled
            'active',
            'enabled',
            'Status text for nginx',
            true,    // canStart
            true,    // canStop
            true,    // canReload
            $lastStatusChange,
            'www-data'
        );
        
        $this->assertEquals('nginx.service', $serviceInfo->getId());
        $this->assertEquals('forking', $serviceInfo->getType());
        $this->assertEquals('The nginx HTTP Server', $serviceInfo->getDescription());
        $this->assertTrue($serviceInfo->isActive());
        $this->assertTrue($serviceInfo->isEnabled());
        $this->assertEquals('active', $serviceInfo->getActiveStatus());
        $this->assertEquals('enabled', $serviceInfo->getEnabledStatus());
        $this->assertEquals('Status text for nginx', $serviceInfo->getStatusText());
        $this->assertTrue($serviceInfo->getCanStart());
        $this->assertTrue($serviceInfo->getCanStop());
        $this->assertTrue($serviceInfo->getCanReload());
        $this->assertEquals($lastStatusChange, $serviceInfo->getLastStatusChange());
        $this->assertEquals('www-data', $serviceInfo->getUser());
    }

    public function testWithNullValues(): void
    {
        $serviceInfo = new ServiceInfo(
            'test.service',
            'simple',
            null,     // description
            false,    // active
            false,    // enabled
            'inactive',
            'disabled',
            'Test status',
            null,     // canStart
            null,     // canStop
            null,     // canReload
            null,     // lastStatusChange
            null      // user
        );
        
        $this->assertEquals('test.service', $serviceInfo->getId());
        $this->assertEquals('simple', $serviceInfo->getType());
        $this->assertNull($serviceInfo->getDescription());
        $this->assertFalse($serviceInfo->isActive());
        $this->assertFalse($serviceInfo->isEnabled());
        $this->assertNull($serviceInfo->getCanStart());
        $this->assertNull($serviceInfo->getCanStop());
        $this->assertNull($serviceInfo->getCanReload());
        $this->assertNull($serviceInfo->getLastStatusChange());
        $this->assertNull($serviceInfo->getUser());
    }

    public function testVariousServiceTypes(): void
    {
        $types = ['simple', 'forking', 'oneshot', 'dbus', 'notify', 'idle'];
        
        foreach ($types as $type) {
            $service = new ServiceInfo(
                "test-$type.service",
                $type,
                "Test $type Service",
                true,
                true,
                'active',
                'enabled',
                'Test status',
                true,
                true,
                true,
                null,
                'root'
            );
            
            $this->assertEquals($type, $service->getType());
        }
    }
}