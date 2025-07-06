<?php

namespace TS\PhpSystemD\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;
use TS\PhpSystemD\Process\ProcessFactory;
use TS\PhpSystemD\SystemCtl;

class SystemCtlTest extends TestCase
{
    private $processMock;
    private $systemCtl;

    protected function setUp(): void
    {
        $this->processMock = $this->createMock(Process::class);
        $this->systemCtl = $this->getMockBuilder(SystemCtl::class)
            ->setConstructorArgs(['systemctl', 60])
            ->onlyMethods(['runProcess'])
            ->getMock();
    }

    public function testConstructorSetsDefaults(): void
    {
        $systemCtl = new SystemCtl();
        $this->assertInstanceOf(SystemCtl::class, $systemCtl);
    }

    public function testConstructorWithCustomValues(): void
    {
        $systemCtl = new SystemCtl('custom-systemctl', 120);
        $this->assertInstanceOf(SystemCtl::class, $systemCtl);
    }

    public function testStatusReturnsOutput(): void
    {
        $expectedOutput = 'Active: active (running)';
        $this->processMock->method('isSuccessful')
            ->willReturn(true);
        $this->processMock->method('getOutput')
            ->willReturn($expectedOutput);

        $this->systemCtl->method('runProcess')
            ->willReturn($this->processMock);

        $result = $this->systemCtl->status('nginx.service');
        $this->assertEquals($expectedOutput, $result);
    }

    public function testStatusWithJournalLines(): void
    {
        $expectedOutput = 'Active: active (running)';
        $this->processMock->method('isSuccessful')
            ->willReturn(true);
        $this->processMock->method('getOutput')
            ->willReturn($expectedOutput);

        $this->systemCtl->method('runProcess')
            ->willReturn($this->processMock);

        $result = $this->systemCtl->status('nginx.service', 50);
        $this->assertEquals($expectedOutput, $result);
    }

    public function testStatusHandlesExitCode3(): void
    {
        $expectedOutput = 'Active: inactive (dead)';
        $this->processMock->method('isSuccessful')
            ->willReturn(false);
        $this->processMock->method('getExitCode')
            ->willReturn(3);
        $this->processMock->method('getOutput')
            ->willReturn($expectedOutput);

        $this->systemCtl->method('runProcess')
            ->willReturn($this->processMock);

        $result = $this->systemCtl->status('nginx.service');
        $this->assertEquals($expectedOutput, $result);
    }

    public function testStatusThrowsExceptionOnError(): void
    {
        $this->processMock->method('isSuccessful')
            ->willReturn(false);
        $this->processMock->method('getExitCode')
            ->willReturn(1);
        $this->processMock->method('getErrorOutput')
            ->willReturn('Unit not found');

        $this->systemCtl->method('runProcess')
            ->willReturn($this->processMock);

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Unable to get status for nginx.service: Unit not found');
        
        $this->systemCtl->status('nginx.service');
    }

    public function testStopService(): void
    {
        $this->processMock->method('isSuccessful')
            ->willReturn(true);

        $this->systemCtl->method('runProcess')
            ->willReturn($this->processMock);

        $this->systemCtl->stop('nginx.service');
        $this->assertTrue(true); // Verify no exception was thrown
    }

    public function testStopServiceThrowsExceptionOnError(): void
    {
        $this->processMock->method('isSuccessful')
            ->willReturn(false);
        $this->processMock->method('getErrorOutput')
            ->willReturn('Access denied');

        $this->systemCtl->method('runProcess')
            ->willReturn($this->processMock);

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Unable to stop nginx.service: Access denied');
        
        $this->systemCtl->stop('nginx.service');
    }

    public function testStartService(): void
    {
        $this->processMock->method('isSuccessful')
            ->willReturn(true);

        $this->systemCtl->method('runProcess')
            ->willReturn($this->processMock);

        $this->systemCtl->start('nginx.service');
        $this->assertTrue(true); // Verify no exception was thrown
    }

    public function testStartServiceThrowsExceptionOnError(): void
    {
        $this->processMock->method('isSuccessful')
            ->willReturn(false);
        $this->processMock->method('getErrorOutput')
            ->willReturn('Failed to start');

        $this->systemCtl->method('runProcess')
            ->willReturn($this->processMock);

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Unable to start nginx.service: Failed to start');
        
        $this->systemCtl->start('nginx.service');
    }

    public function testRestartService(): void
    {
        $this->processMock->method('isSuccessful')
            ->willReturn(true);

        $this->systemCtl->method('runProcess')
            ->willReturn($this->processMock);

        $this->systemCtl->restart('nginx.service');
        $this->assertTrue(true); // Verify no exception was thrown
    }

    public function testRestartServiceThrowsExceptionOnError(): void
    {
        $this->processMock->method('isSuccessful')
            ->willReturn(false);
        $this->processMock->method('getErrorOutput')
            ->willReturn('Failed to restart');

        $this->systemCtl->method('runProcess')
            ->willReturn($this->processMock);

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Unable to restart nginx.service: Failed to restart');
        
        $this->systemCtl->restart('nginx.service');
    }

    public function testIsEnabledReturnsTrue(): void
    {
        $this->processMock->method('isSuccessful')
            ->willReturn(true);
        $this->processMock->method('getOutput')
            ->willReturn('enabled');

        $this->systemCtl->method('runProcess')
            ->willReturn($this->processMock);

        $result = $this->systemCtl->isEnabled('nginx.service');
        $this->assertTrue($result);
    }

    public function testIsEnabledReturnsFalse(): void
    {
        $this->processMock->method('isSuccessful')
            ->willReturn(false);
        $this->processMock->method('getOutput')
            ->willReturn('disabled');

        $this->systemCtl->method('runProcess')
            ->willReturn($this->processMock);

        $result = $this->systemCtl->isEnabled('nginx.service');
        $this->assertFalse($result);
    }

    public function testGetEnabledStatus(): void
    {
        $this->processMock->method('getOutput')
            ->willReturn('enabled-runtime');

        $this->systemCtl->method('runProcess')
            ->willReturn($this->processMock);

        $result = $this->systemCtl->getEnabledStatus('nginx.service');
        $this->assertEquals('enabled-runtime', $result);
    }

    public function testGetEnabledStatusThrowsExceptionOnError(): void
    {
        $this->processMock->method('getOutput')
            ->willReturn('Unit not found');

        $this->systemCtl->method('runProcess')
            ->willReturn($this->processMock);
        
        $result = $this->systemCtl->getEnabledStatus('nginx.service');
        $this->assertEquals('Unit not found', $result);
    }

    public function testIsActiveReturnsTrue(): void
    {
        $this->processMock->method('isSuccessful')
            ->willReturn(true);

        $this->systemCtl->method('runProcess')
            ->willReturn($this->processMock);

        $result = $this->systemCtl->isActive('nginx.service');
        $this->assertTrue($result);
    }

    public function testIsActiveReturnsFalse(): void
    {
        $this->processMock->method('isSuccessful')
            ->willReturn(false);

        $this->systemCtl->method('runProcess')
            ->willReturn($this->processMock);

        $result = $this->systemCtl->isActive('nginx.service');
        $this->assertFalse($result);
    }

    public function testGetActiveStatus(): void
    {
        $this->processMock->method('getOutput')
            ->willReturn('activating');

        $this->systemCtl->method('runProcess')
            ->willReturn($this->processMock);

        $result = $this->systemCtl->getActiveStatus('nginx.service');
        $this->assertEquals('activating', $result);
    }

    public function testShowPropertyReturnsValue(): void
    {
        $this->processMock->method('getOutput')
            ->willReturn('1234');

        $this->systemCtl->method('runProcess')
            ->willReturn($this->processMock);

        $result = $this->systemCtl->showProperty('nginx.service', 'MainPID');
        $this->assertEquals('1234', $result);
    }

    public function testShowPropertyReturnsNullForEmptyValue(): void
    {
        $this->processMock->method('getOutput')
            ->willReturn('');

        $this->systemCtl->method('runProcess')
            ->willReturn($this->processMock);

        $result = $this->systemCtl->showProperty('nginx.service', 'MainPID');
        $this->assertNull($result);
    }

    public function testShowPropertyReturnsValueForValidFormat(): void
    {
        $this->processMock->method('getOutput')
            ->willReturn('InvalidFormat');

        $this->systemCtl->method('runProcess')
            ->willReturn($this->processMock);
        
        $result = $this->systemCtl->showProperty('nginx.service', 'MainPID');
        $this->assertEquals('InvalidFormat', $result);
    }

    public function testShowProperties(): void
    {
        $output = "MainPID=1234\nCanStart=yes\nCanReload=no\nMemoryCurrent=12345678";
        $this->processMock->method('getOutput')
            ->willReturn($output);

        $this->systemCtl->method('runProcess')
            ->willReturn($this->processMock);

        $result = $this->systemCtl->showProperties('nginx.service', 'MainPID', 'CanStart', 'CanReload', 'MemoryCurrent');
        
        $expected = [
            'MainPID' => '1234',
            'CanStart' => 'yes',
            'CanReload' => 'no',
            'MemoryCurrent' => '12345678'
        ];
        $this->assertEquals($expected, $result);
    }

    public function testShowPropertiesHandlesEmptyValues(): void
    {
        $output = "MainPID=\nCanStart=yes\nDescription=Some service";
        $this->processMock->method('getOutput')
            ->willReturn($output);

        $this->systemCtl->method('runProcess')
            ->willReturn($this->processMock);

        $result = $this->systemCtl->showProperties('nginx.service', 'MainPID', 'CanStart', 'Description');
        
        $expected = [
            'MainPID' => '',
            'CanStart' => 'yes',
            'Description' => 'Some service'
        ];
        $this->assertEquals($expected, $result);
    }

    public function testShowPropertyDateReturnsDateTimeImmutable(): void
    {
        $this->processMock->method('getOutput')
            ->willReturn('Mon 2023-05-01 12:30:45 UTC');

        $this->systemCtl->method('runProcess')
            ->willReturn($this->processMock);

        $result = $this->systemCtl->showPropertyDate('nginx.service', 'ActiveEnterTimestamp');
        
        $this->assertInstanceOf(\DateTimeImmutable::class, $result);
        $this->assertEquals('2023-05-01 12:30:45', $result->format('Y-m-d H:i:s'));
    }

    public function testShowPropertyDateReturnsNullForEmptyValue(): void
    {
        $this->processMock->method('getOutput')
            ->willReturn('');

        $this->systemCtl->method('runProcess')
            ->willReturn($this->processMock);

        $result = $this->systemCtl->showPropertyDate('nginx.service', 'ActiveEnterTimestamp');
        $this->assertNull($result);
    }

    public function testShowPropertyDateThrowsExceptionForInvalidDate(): void
    {
        $this->processMock->method('getOutput')
            ->willReturn('InvalidDate');

        $this->systemCtl->method('runProcess')
            ->willReturn($this->processMock);

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Unable to parse InvalidDate as date.');
        
        $this->systemCtl->showPropertyDate('nginx.service', 'ActiveEnterTimestamp');
    }

    /**
     * @dataProvider boolPropertyProvider
     */
    public function testShowPropertyBool(string $value, ?bool $expected): void
    {
        $this->processMock->method('getOutput')
            ->willReturn($value);

        $this->systemCtl->method('runProcess')
            ->willReturn($this->processMock);

        $result = $this->systemCtl->showPropertyBool('nginx.service', 'CanStart');
        $this->assertEquals($expected, $result);
    }

    public function boolPropertyProvider(): array
    {
        return [
            ['yes', true],
            ['no', false],
            ['', null],
        ];
    }

    public function testShowPropertyBoolThrowsExceptionForInvalidFormat(): void
    {
        $this->processMock->method('getOutput')
            ->willReturn('InvalidFormat');

        $this->systemCtl->method('runProcess')
            ->willReturn($this->processMock);

        $this->expectException(\UnexpectedValueException::class);
        
        $this->systemCtl->showPropertyBool('nginx.service', 'CanStart');
    }

    public function testRunProcessWithBasicArguments(): void
    {
        $processFactoryMock = $this->createMock(ProcessFactory::class);
        $systemCtl = new SystemCtl('systemctl', 60, $processFactoryMock);

        $processMock = $this->createMock(Process::class);
        $processMock->expects($this->once())
            ->method('run');

        $processFactoryMock->expects($this->once())
            ->method('create')
            ->with(
                ['systemctl', 'status', 'nginx.service'],
                null,
                null,
                null,
                60
            )
            ->willReturn($processMock);

        // Use reflection to access the protected runProcess method
        $reflection = new \ReflectionClass($systemCtl);
        $runProcessMethod = $reflection->getMethod('runProcess');
        $runProcessMethod->setAccessible(true);

        $result = $runProcessMethod->invoke($systemCtl, ['status', 'nginx.service']);
        
        $this->assertSame($processMock, $result);
    }

    public function testRunProcessWithMultipleArguments(): void
    {
        $processFactoryMock = $this->createMock(ProcessFactory::class);
        $systemCtl = new SystemCtl('systemctl', 90, $processFactoryMock);

        $processMock = $this->createMock(Process::class);
        $processMock->expects($this->once())
            ->method('run');

        $processFactoryMock->expects($this->once())
            ->method('create')
            ->with(
                ['systemctl', 'show', 'nginx.service', '-p', 'MainPID', '--value'],
                null,
                null,
                null,
                90
            )
            ->willReturn($processMock);

        // Use reflection to access the protected runProcess method
        $reflection = new \ReflectionClass($systemCtl);
        $runProcessMethod = $reflection->getMethod('runProcess');
        $runProcessMethod->setAccessible(true);

        $result = $runProcessMethod->invoke($systemCtl, ['show', 'nginx.service', '-p', 'MainPID', '--value']);
        
        $this->assertSame($processMock, $result);
    }

    public function testRunProcessWithCustomCommand(): void
    {
        $processFactoryMock = $this->createMock(ProcessFactory::class);
        $systemCtl = new SystemCtl('custom-systemctl', 45, $processFactoryMock);

        $processMock = $this->createMock(Process::class);
        $processMock->expects($this->once())
            ->method('run');

        $processFactoryMock->expects($this->once())
            ->method('create')
            ->with(
                ['custom-systemctl', 'start', 'test.service'],
                null,
                null,
                null,
                45
            )
            ->willReturn($processMock);

        // Use reflection to access the protected runProcess method
        $reflection = new \ReflectionClass($systemCtl);
        $runProcessMethod = $reflection->getMethod('runProcess');
        $runProcessMethod->setAccessible(true);

        $result = $runProcessMethod->invoke($systemCtl, ['start', 'test.service']);
        
        $this->assertSame($processMock, $result);
    }

    public function testRunProcessWithEmptyArguments(): void
    {
        $processFactoryMock = $this->createMock(ProcessFactory::class);
        $systemCtl = new SystemCtl('systemctl', 60, $processFactoryMock);

        $processMock = $this->createMock(Process::class);
        $processMock->expects($this->once())
            ->method('run');

        $processFactoryMock->expects($this->once())
            ->method('create')
            ->with(
                ['systemctl'],
                null,
                null,
                null,
                60
            )
            ->willReturn($processMock);

        // Use reflection to access the protected runProcess method
        $reflection = new \ReflectionClass($systemCtl);
        $runProcessMethod = $reflection->getMethod('runProcess');
        $runProcessMethod->setAccessible(true);

        $result = $runProcessMethod->invoke($systemCtl, []);
        
        $this->assertSame($processMock, $result);
    }

    public function testRunProcessWithComplexArguments(): void
    {
        $processFactoryMock = $this->createMock(ProcessFactory::class);
        $systemCtl = new SystemCtl('systemctl', 120, $processFactoryMock);

        $processMock = $this->createMock(Process::class);
        $processMock->expects($this->once())
            ->method('run');

        $complexArgs = [
            'show', 
            'nginx.service', 
            'apache2.service', 
            '-p', 'MainPID', 
            '-p', 'CanStart', 
            '-p', 'CanReload', 
            '--value'
        ];

        $expectedCommand = ['systemctl'];
        array_push($expectedCommand, ...$complexArgs);

        $processFactoryMock->expects($this->once())
            ->method('create')
            ->with(
                $expectedCommand,
                null,
                null,
                null,
                120
            )
            ->willReturn($processMock);

        // Use reflection to access the protected runProcess method
        $reflection = new \ReflectionClass($systemCtl);
        $runProcessMethod = $reflection->getMethod('runProcess');
        $runProcessMethod->setAccessible(true);

        $result = $runProcessMethod->invoke($systemCtl, $complexArgs);
        
        $this->assertSame($processMock, $result);
    }

    public function testRunProcessTimeoutConfiguration(): void
    {
        $processFactoryMock = $this->createMock(ProcessFactory::class);
        $systemCtl = new SystemCtl('systemctl', 30.5, $processFactoryMock);

        $processMock = $this->createMock(Process::class);
        $processMock->expects($this->once())
            ->method('run');

        $processFactoryMock->expects($this->once())
            ->method('create')
            ->with(
                ['systemctl', 'daemon-reload'],
                null,
                null,
                null,
                30.5
            )
            ->willReturn($processMock);

        // Use reflection to access the protected runProcess method
        $reflection = new \ReflectionClass($systemCtl);
        $runProcessMethod = $reflection->getMethod('runProcess');
        $runProcessMethod->setAccessible(true);

        $result = $runProcessMethod->invoke($systemCtl, ['daemon-reload']);
        
        $this->assertSame($processMock, $result);
    }

    public function testRunProcessCommandLineBuilding(): void
    {
        $processFactoryMock = $this->createMock(ProcessFactory::class);
        $systemCtl = new SystemCtl('systemctl', 60, $processFactoryMock);

        $processMock1 = $this->createMock(Process::class);
        $processMock1->expects($this->once())->method('run');

        $processMock2 = $this->createMock(Process::class);
        $processMock2->expects($this->once())->method('run');

        $processFactoryMock->expects($this->exactly(2))
            ->method('create')
            ->withConsecutive(
                [
                    ['systemctl', 'is-active', 'nginx.service'],
                    null, null, null, 60
                ],
                [
                    ['systemctl', 'status', 'nginx.service', '--lines', '0'],
                    null, null, null, 60
                ]
            )
            ->willReturnOnConsecutiveCalls($processMock1, $processMock2);

        // Use reflection to access the protected runProcess method
        $reflection = new \ReflectionClass($systemCtl);
        $runProcessMethod = $reflection->getMethod('runProcess');
        $runProcessMethod->setAccessible(true);

        $result1 = $runProcessMethod->invoke($systemCtl, ['is-active', 'nginx.service']);
        $this->assertSame($processMock1, $result1);

        $result2 = $runProcessMethod->invoke($systemCtl, ['status', 'nginx.service', '--lines', '0']);
        $this->assertSame($processMock2, $result2);
    }

    public function testRunProcessWithNumericArguments(): void
    {
        $processFactoryMock = $this->createMock(ProcessFactory::class);
        $systemCtl = new SystemCtl('systemctl', 60, $processFactoryMock);

        $processMock = $this->createMock(Process::class);
        $processMock->expects($this->once())
            ->method('run');

        $processFactoryMock->expects($this->once())
            ->method('create')
            ->with(
                ['systemctl', 'status', 'nginx.service', '--lines', 50],
                null,
                null,
                null,
                60
            )
            ->willReturn($processMock);

        // Use reflection to access the protected runProcess method
        $reflection = new \ReflectionClass($systemCtl);
        $runProcessMethod = $reflection->getMethod('runProcess');
        $runProcessMethod->setAccessible(true);

        $result = $runProcessMethod->invoke($systemCtl, ['status', 'nginx.service', '--lines', 50]);
        
        $this->assertSame($processMock, $result);
    }
}