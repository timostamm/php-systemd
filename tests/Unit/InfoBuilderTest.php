<?php

namespace TS\PhpSystemD\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;
use TS\PhpSystemD\Info\MemoryInfo;
use TS\PhpSystemD\Info\ServiceInfo;
use TS\PhpSystemD\Info\TimerInfo;
use TS\PhpSystemD\Info\UptimeInfo;
use TS\PhpSystemD\InfoBuilder;
use TS\PhpSystemD\Process\ProcessFactory;
use TS\PhpSystemD\SystemCtl;

class InfoBuilderTest extends TestCase
{
    private $systemCtlMock;
    private $infoBuilder;

    protected function setUp(): void
    {
        $this->systemCtlMock = $this->createMock(SystemCtl::class);
        $this->infoBuilder = new InfoBuilder($this->systemCtlMock);
    }

    public function testUptimeSuccess(): void
    {
        $processFactoryMock = $this->createMock(ProcessFactory::class);
        $infoBuilder = new InfoBuilder($this->systemCtlMock, $processFactoryMock);

        $uptimeProcessMock = $this->createMock(Process::class);
        $uptimeProcessMock->expects($this->once())
            ->method('mustRun')
            ->willReturnSelf();
        $uptimeProcessMock->expects($this->once())
            ->method('getOutput')
            ->willReturn('up 1 day, 2 hours, 30 minutes');

        $loadProcessMock = $this->createMock(Process::class);
        $loadProcessMock->expects($this->once())
            ->method('mustRun')
            ->willReturnSelf();
        $loadProcessMock->expects($this->once())
            ->method('getOutput')
            ->willReturn('10:30  up 1 day,  2:30, 1 user, load averages: 1.25 2.50 3.75');

        $processFactoryMock->expects($this->exactly(2))
            ->method('create')
            ->withConsecutive(
                [['uptime', '-p']],
                [['uptime']]
            )
            ->willReturnOnConsecutiveCalls(
                $uptimeProcessMock,
                $loadProcessMock
            );

        $result = $infoBuilder->uptime();

        $this->assertInstanceOf(UptimeInfo::class, $result);
        $this->assertEquals('up 1 day, 2 hours, 30 minutes', $result->getUptimePretty());
        $this->assertEquals(1.25, $result->getLoadAvg1());
        $this->assertEquals(2.50, $result->getLoadAvg5());
        $this->assertEquals(3.75, $result->getLoadAvg15());
    }

    public function testUptimeWithCommaDecimalSeparator(): void
    {
        $processFactoryMock = $this->createMock(ProcessFactory::class);
        $infoBuilder = new InfoBuilder($this->systemCtlMock, $processFactoryMock);

        $uptimeProcessMock = $this->createMock(Process::class);
        $uptimeProcessMock->expects($this->once())
            ->method('mustRun')
            ->willReturnSelf();
        $uptimeProcessMock->expects($this->once())
            ->method('getOutput')
            ->willReturn('up 2 days, 5 hours, 15 minutes');

        $loadProcessMock = $this->createMock(Process::class);
        $loadProcessMock->expects($this->once())
            ->method('mustRun')
            ->willReturnSelf();
        $loadProcessMock->expects($this->once())
            ->method('getOutput')
            ->willReturn('15:45  up 2 days,  5:15, 3 users, load averages: 0,75 1,20 2,85');

        $processFactoryMock->expects($this->exactly(2))
            ->method('create')
            ->withConsecutive(
                [['uptime', '-p']],
                [['uptime']]
            )
            ->willReturnOnConsecutiveCalls(
                $uptimeProcessMock,
                $loadProcessMock
            );

        $result = $infoBuilder->uptime();

        $this->assertInstanceOf(UptimeInfo::class, $result);
        $this->assertEquals('up 2 days, 5 hours, 15 minutes', $result->getUptimePretty());
        $this->assertEquals(0.75, $result->getLoadAvg1());
        $this->assertEquals(1.20, $result->getLoadAvg5());
        $this->assertEquals(2.85, $result->getLoadAvg15());
    }

    public function testUptimeInvalidFormat(): void
    {
        $processFactoryMock = $this->createMock(ProcessFactory::class);
        $infoBuilder = new InfoBuilder($this->systemCtlMock, $processFactoryMock);

        $uptimeProcessMock = $this->createMock(Process::class);
        $uptimeProcessMock->expects($this->once())
            ->method('mustRun')
            ->willReturnSelf();
        $uptimeProcessMock->expects($this->once())
            ->method('getOutput')
            ->willReturn('up 1 day');

        $loadProcessMock = $this->createMock(Process::class);
        $loadProcessMock->expects($this->once())
            ->method('mustRun')
            ->willReturnSelf();
        $loadProcessMock->expects($this->once())
            ->method('getOutput')
            ->willReturn('invalid uptime format');

        $processFactoryMock->expects($this->exactly(2))
            ->method('create')
            ->withConsecutive(
                [['uptime', '-p']],
                [['uptime']]
            )
            ->willReturnOnConsecutiveCalls(
                $uptimeProcessMock,
                $loadProcessMock
            );

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessageMatches('/No regex match for uptime load averages/');

        $infoBuilder->uptime();
    }

    public function testMemorySuccess(): void
    {
        $processFactoryMock = $this->createMock(ProcessFactory::class);
        $infoBuilder = new InfoBuilder($this->systemCtlMock, $processFactoryMock);

        $processMemoryMock = $this->createMock(Process::class);
        $processMemoryMock->expects($this->once())
            ->method('mustRun')
            ->willReturnSelf();
        $processMemoryMock->expects($this->once())
            ->method('getOutput')
            ->willReturn("              total        used        free      shared  buff/cache   available\n" .
                "Mem:     8589934592  4294967296  2147483648   134217728  2147483648  4160749568\n" .
                "Swap:    1073741824   536870912   536870912");

        $processFactoryMock->expects($this->once())
            ->method('create')
            ->with(['free', '--bytes'])
            ->willReturn($processMemoryMock);

        $result = $infoBuilder->memory();

        $this->assertInstanceOf(MemoryInfo::class, $result);
        $this->assertEquals(8589934592, $result->getTotalBytes());
        $this->assertEquals(4294967296, $result->getUsedBytes());
        $this->assertEquals(1073741824, $result->getSwapTotalBytes());
        $this->assertEquals(536870912, $result->getSwapUsedBytes());
    }

    public function testMemoryNoSwap(): void
    {
        $processFactoryMock = $this->createMock(ProcessFactory::class);
        $infoBuilder = new InfoBuilder($this->systemCtlMock, $processFactoryMock);

        $processMemoryMock = $this->createMock(Process::class);
        $processMemoryMock->expects($this->once())
            ->method('mustRun')
            ->willReturnSelf();
        $processMemoryMock->expects($this->once())
            ->method('getOutput')
            ->willReturn("              total        used        free      shared  buff/cache   available\n" .
                "Mem:     4294967296  2147483648  1073741824    67108864  1073741824  2013265920\n" .
                "Swap:             0           0           0");

        $processFactoryMock->expects($this->once())
            ->method('create')
            ->with(['free', '--bytes'])
            ->willReturn($processMemoryMock);

        $result = $infoBuilder->memory();

        $this->assertInstanceOf(MemoryInfo::class, $result);
        $this->assertEquals(4294967296, $result->getTotalBytes());
        $this->assertEquals(2147483648, $result->getUsedBytes());
        $this->assertEquals(0, $result->getSwapTotalBytes());
        $this->assertEquals(0, $result->getSwapUsedBytes());
    }

    public function testMemoryInvalidFormat(): void
    {
        $processFactoryMock = $this->createMock(ProcessFactory::class);
        $infoBuilder = new InfoBuilder($this->systemCtlMock, $processFactoryMock);

        $processMemoryMock = $this->createMock(Process::class);
        $processMemoryMock->expects($this->once())
            ->method('mustRun')
            ->willReturnSelf();
        $processMemoryMock->expects($this->once())
            ->method('getOutput')
            ->willReturn("invalid free output format");

        $processFactoryMock->expects($this->once())
            ->method('create')
            ->with(['free', '--bytes'])
            ->willReturn($processMemoryMock);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessageMatches('/No regex match for memory info/');

        $infoBuilder->memory();
    }

    public function testGetUnitInfoService(): void
    {
        $this->systemCtlMock->expects($this->once())
            ->method('status')
            ->with('nginx.service', 0)
            ->willReturn('● nginx.service - The nginx HTTP Server\n   Active: active (running)');

        // Mock all the individual showProperty calls (Id is called twice - once in getUnitInfo, once in getServiceInfo)
        $this->systemCtlMock->expects($this->exactly(5))
            ->method('showProperty')
            ->withConsecutive(
                ['nginx.service', 'Id'],
                ['nginx.service', 'Id'],
                ['nginx.service', 'Type'],
                ['nginx.service', 'Description'],
                ['nginx.service', 'User']
            )
            ->willReturnOnConsecutiveCalls(
                'nginx.service',
                'nginx.service',
                'forking',
                'The nginx HTTP Server',
                'www-data'
            );

        $this->systemCtlMock->expects($this->once())
            ->method('isActive')
            ->with('nginx.service')
            ->willReturn(true);

        $this->systemCtlMock->expects($this->once())
            ->method('isEnabled')
            ->with('nginx.service')
            ->willReturn(true);

        $this->systemCtlMock->expects($this->once())
            ->method('getActiveStatus')
            ->with('nginx.service')
            ->willReturn('active');

        $this->systemCtlMock->expects($this->once())
            ->method('getEnabledStatus')
            ->with('nginx.service')
            ->willReturn('enabled');

        $this->systemCtlMock->expects($this->exactly(3))
            ->method('showPropertyBool')
            ->withConsecutive(
                ['nginx.service', 'CanStart'],
                ['nginx.service', 'CanStop'],
                ['nginx.service', 'CanReload']
            )
            ->willReturnOnConsecutiveCalls(true, true, true);

        $this->systemCtlMock->expects($this->once())
            ->method('showPropertyDate')
            ->with('nginx.service', 'StateChangeTimestamp')
            ->willReturn(new \DateTimeImmutable('2023-05-01 12:30:45'));

        $result = $this->infoBuilder->getUnitInfo('nginx.service');
        
        $this->assertInstanceOf(ServiceInfo::class, $result);
        $this->assertEquals('nginx.service', $result->getId());
        $this->assertEquals('The nginx HTTP Server', $result->getDescription());
        $this->assertTrue($result->isActive());
        $this->assertTrue($result->isEnabled());
        $this->assertEquals('forking', $result->getType());
        $this->assertTrue($result->getCanStart());
        $this->assertEquals('www-data', $result->getUser());
    }

    public function testGetUnitInfoTimer(): void
    {
        $this->systemCtlMock->expects($this->once())
            ->method('status')
            ->with('backup.timer', 0)
            ->willReturn('● backup.timer - Daily backup timer\n   Active: active (waiting)');

        // Mock all the individual calls for TimerInfo constructor (Id is called twice - once in getUnitInfo, once in getTimerInfo)
        $this->systemCtlMock->expects($this->exactly(4))
            ->method('showProperty')
            ->withConsecutive(
                ['backup.timer', 'Id'],
                ['backup.timer', 'Id'],
                ['backup.timer', 'Description'],
                ['backup.timer', 'Unit']
            )
            ->willReturnOnConsecutiveCalls(
                'backup.timer',
                'backup.timer',
                'Daily backup timer',
                'backup.service'
            );

        $this->systemCtlMock->expects($this->once())
            ->method('isActive')
            ->with('backup.timer')
            ->willReturn(true);

        $this->systemCtlMock->expects($this->once())
            ->method('isEnabled')
            ->with('backup.timer')
            ->willReturn(true);

        $this->systemCtlMock->expects($this->once())
            ->method('getActiveStatus')
            ->with('backup.timer')
            ->willReturn('active');

        $this->systemCtlMock->expects($this->once())
            ->method('getEnabledStatus')
            ->with('backup.timer')
            ->willReturn('enabled');

        $this->systemCtlMock->expects($this->exactly(2))
            ->method('showPropertyDate')
            ->withConsecutive(
                ['backup.timer', 'LastTriggerUSec'],
                ['backup.timer', 'NextElapseUSecRealtime']
            )
            ->willReturnOnConsecutiveCalls(
                new \DateTimeImmutable('2023-05-01 00:00:00'),
                new \DateTimeImmutable('2023-05-02 00:00:00')
            );

        $result = $this->infoBuilder->getUnitInfo('backup.timer');
        
        $this->assertInstanceOf(TimerInfo::class, $result);
        $this->assertEquals('backup.timer', $result->getId());
        $this->assertEquals('Daily backup timer', $result->getDescription());
        $this->assertEquals('backup.service', $result->getUnitId());
    }

    public function testGetUnitInfoNotFound(): void
    {
        $this->systemCtlMock->expects($this->once())
            ->method('showProperty')
            ->with('notfound.service', 'Id')
            ->willReturn('');

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Unable to get Id of notfound.service: Unit seems to be unknown.');
        
        $this->infoBuilder->getUnitInfo('notfound.service');
    }

    public function testGetUnitInfoUnsupportedType(): void
    {
        $this->systemCtlMock->expects($this->once())
            ->method('showProperty')
            ->with('something.mount', 'Id')
            ->willReturn('something.mount');

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Unable to get info for something.mount: Unsupported unit type mount.');
        
        $this->infoBuilder->getUnitInfo('something.mount');
    }

    public function testGetTimerInfo(): void
    {
        // Mock the status call
        $this->systemCtlMock->expects($this->once())
            ->method('status')
            ->with('backup.timer', 0)
            ->willReturn('● backup.timer - Daily backup timer\n   Active: active (waiting)');

        // Mock all the individual showProperty calls for TimerInfo constructor
        $this->systemCtlMock->expects($this->exactly(3))
            ->method('showProperty')
            ->withConsecutive(
                ['backup.timer', 'Id'],
                ['backup.timer', 'Description'],
                ['backup.timer', 'Unit']
            )
            ->willReturnOnConsecutiveCalls(
                'backup.timer',
                'Daily backup timer',
                'backup.service'
            );

        $this->systemCtlMock->expects($this->once())
            ->method('isActive')
            ->with('backup.timer')
            ->willReturn(true);

        $this->systemCtlMock->expects($this->once())
            ->method('isEnabled')
            ->with('backup.timer')
            ->willReturn(true);

        $this->systemCtlMock->expects($this->once())
            ->method('getActiveStatus')
            ->with('backup.timer')
            ->willReturn('active');

        $this->systemCtlMock->expects($this->once())
            ->method('getEnabledStatus')
            ->with('backup.timer')
            ->willReturn('enabled');

        $this->systemCtlMock->expects($this->exactly(2))
            ->method('showPropertyDate')
            ->withConsecutive(
                ['backup.timer', 'LastTriggerUSec'],
                ['backup.timer', 'NextElapseUSecRealtime']
            )
            ->willReturnOnConsecutiveCalls(
                new \DateTimeImmutable('2023-05-01 00:00:00'),
                new \DateTimeImmutable('2023-05-02 00:00:00')
            );

        $result = $this->infoBuilder->getTimerInfo('backup.timer');
        
        $this->assertInstanceOf(TimerInfo::class, $result);
        $this->assertEquals('backup.timer', $result->getId());
        $this->assertEquals('Daily backup timer', $result->getDescription());
        $this->assertEquals('backup.service', $result->getUnitId());
        $this->assertInstanceOf(\DateTimeImmutable::class, $result->getLastTrigger());
        $this->assertInstanceOf(\DateTimeImmutable::class, $result->getNextElapse());
    }

    public function testGetServiceInfo(): void
    {
        // Mock the status call
        $this->systemCtlMock->expects($this->once())
            ->method('status')
            ->with('ssh.service', 0)
            ->willReturn('● ssh.service - OpenSSH server\n   Active: active (running)');

        // Mock all the individual showProperty calls for ServiceInfo constructor
        $this->systemCtlMock->expects($this->exactly(4))
            ->method('showProperty')
            ->withConsecutive(
                ['ssh.service', 'Id'],
                ['ssh.service', 'Type'],
                ['ssh.service', 'Description'],
                ['ssh.service', 'User']
            )
            ->willReturnOnConsecutiveCalls(
                'ssh.service',
                'notify',
                'OpenSSH server',
                'root'
            );

        $this->systemCtlMock->expects($this->once())
            ->method('isActive')
            ->with('ssh.service')
            ->willReturn(true);

        $this->systemCtlMock->expects($this->once())
            ->method('isEnabled')
            ->with('ssh.service')
            ->willReturn(true);

        $this->systemCtlMock->expects($this->once())
            ->method('getActiveStatus')
            ->with('ssh.service')
            ->willReturn('active');

        $this->systemCtlMock->expects($this->once())
            ->method('getEnabledStatus')
            ->with('ssh.service')
            ->willReturn('enabled');

        $this->systemCtlMock->expects($this->exactly(3))
            ->method('showPropertyBool')
            ->withConsecutive(
                ['ssh.service', 'CanStart'],
                ['ssh.service', 'CanStop'],
                ['ssh.service', 'CanReload']
            )
            ->willReturnOnConsecutiveCalls(false, true, true);

        $this->systemCtlMock->expects($this->once())
            ->method('showPropertyDate')
            ->with('ssh.service', 'StateChangeTimestamp')
            ->willReturn(new \DateTimeImmutable('2023-05-01 10:00:00'));

        $result = $this->infoBuilder->getServiceInfo('ssh.service');
        
        $this->assertInstanceOf(ServiceInfo::class, $result);
        $this->assertEquals('ssh.service', $result->getId());
        $this->assertEquals('OpenSSH server', $result->getDescription());
        $this->assertEquals('notify', $result->getType());
        $this->assertFalse($result->getCanStart());
        $this->assertTrue($result->getCanStop());
        $this->assertEquals('root', $result->getUser());
    }

    public function testStatusTextExtraction(): void
    {
        $statusOutput = "● test.service - Test Service\n" .
                       "   Loaded: loaded (/etc/systemd/system/test.service; enabled)\n" .
                       "   Active: active (running) since Mon 2023-05-01 10:00:00 UTC\n" .
                       " Main PID: 1234 (test)\n" .
                       "   Status: \"Running successfully\"\n" .
                       "   CGroup: /system.slice/test.service\n" .
                       "           └─1234 /usr/bin/test";

        $this->systemCtlMock->expects($this->once())
            ->method('status')
            ->with('test.service', 0)
            ->willReturn($statusOutput);

        // Mock all the individual calls for ServiceInfo constructor (Id is called twice - once in getUnitInfo, once in getServiceInfo)
        $this->systemCtlMock->expects($this->exactly(5))
            ->method('showProperty')
            ->withConsecutive(
                ['test.service', 'Id'],
                ['test.service', 'Id'],
                ['test.service', 'Type'],
                ['test.service', 'Description'],
                ['test.service', 'User']
            )
            ->willReturnOnConsecutiveCalls(
                'test.service',
                'test.service',
                'simple',
                'Test Service',
                'root'
            );

        $this->systemCtlMock->expects($this->once())
            ->method('isActive')
            ->with('test.service')
            ->willReturn(true);

        $this->systemCtlMock->expects($this->once())
            ->method('isEnabled')
            ->with('test.service')
            ->willReturn(true);

        $this->systemCtlMock->expects($this->once())
            ->method('getActiveStatus')
            ->with('test.service')
            ->willReturn('active');

        $this->systemCtlMock->expects($this->once())
            ->method('getEnabledStatus')
            ->with('test.service')
            ->willReturn('enabled');

        $this->systemCtlMock->expects($this->exactly(3))
            ->method('showPropertyBool')
            ->withConsecutive(
                ['test.service', 'CanStart'],
                ['test.service', 'CanStop'],
                ['test.service', 'CanReload']
            )
            ->willReturnOnConsecutiveCalls(true, true, true);

        $this->systemCtlMock->expects($this->once())
            ->method('showPropertyDate')
            ->with('test.service', 'StateChangeTimestamp')
            ->willReturn(new \DateTimeImmutable('2023-05-01 10:00:00'));

        $result = $this->infoBuilder->getUnitInfo('test.service');
        
        $expectedStatusText = "   Loaded: loaded (/etc/systemd/system/test.service; enabled)\n" .
                            "   Active: active (running) since Mon 2023-05-01 10:00:00 UTC\n" .
                            " Main PID: 1234 (test)\n" .
                            "   Status: \"Running successfully\"\n" .
                            "   CGroup: /system.slice/test.service\n" .
                            "           └─1234 /usr/bin/test";
        
        $this->assertEquals($expectedStatusText, $result->getStatusText());
    }
}