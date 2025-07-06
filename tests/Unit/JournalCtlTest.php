<?php

namespace TS\PhpSystemD\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;
use TS\PhpSystemD\JournalCtl;
use TS\PhpSystemD\Process\ProcessFactory;

class JournalCtlTest extends TestCase
{
    private $processMock;
    private $journalCtl;

    protected function setUp(): void
    {
        $this->processMock = $this->createMock(Process::class);
        $this->journalCtl = $this->getMockBuilder(JournalCtl::class)
            ->setConstructorArgs(['journalctl', 60])
            ->onlyMethods(['process'])
            ->getMock();
        
        $this->journalCtl->method('process')
            ->willReturn($this->processMock);
    }

    public function testConstructorSetsDefaults(): void
    {
        $journalCtl = new JournalCtl();
        $this->assertInstanceOf(JournalCtl::class, $journalCtl);
    }

    public function testConstructorWithCustomValues(): void
    {
        $journalCtl = new JournalCtl('custom-journalctl', 120);
        $this->assertInstanceOf(JournalCtl::class, $journalCtl);
    }

    public function testFollowStartsProcessAndReturnsIt(): void
    {
        $this->processMock->expects($this->once())
            ->method('start');
        $this->processMock->expects($this->never())
            ->method('run');

        $result = $this->journalCtl->follow();
        $this->assertSame($this->processMock, $result);
    }

    public function testFollowWithCallback(): void
    {
        $callbackCalled = false;
        $callback = function($type, $buffer) use (&$callbackCalled) {
            $callbackCalled = true;
        };

        $this->processMock->expects($this->once())
            ->method('start')
            ->with($callback);

        $result = $this->journalCtl->follow($callback);
        $this->assertSame($this->processMock, $result);
    }

    public function testGet(): void
    {
        $expectedOutput = "Journal log output";
        
        $this->processMock->expects($this->once())
            ->method('run');
        $this->processMock->expects($this->once())
            ->method('isSuccessful')
            ->willReturn(true);
        $this->processMock->expects($this->once())
            ->method('getOutput')
            ->willReturn($expectedOutput);

        $result = $this->journalCtl->get();
        $this->assertEquals($expectedOutput, $result);
    }

    public function testGetThrowsExceptionOnError(): void
    {
        $this->processMock->expects($this->once())
            ->method('run');
        $this->processMock->expects($this->once())
            ->method('isSuccessful')
            ->willReturn(false);
        $this->processMock->expects($this->once())
            ->method('getCommandLine')
            ->willReturn('journalctl');
        $this->processMock->expects($this->once())
            ->method('getErrorOutput')
            ->willReturn('Permission denied');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('An error occurred while running "journalctl": Permission denied');
        
        $this->journalCtl->get();
    }

    public function testFluentInterfaceMethodsReturnClonedInstances(): void
    {
        $original = new JournalCtl();
        
        $withLines = $original->withLines(100);
        $this->assertNotSame($original, $withLines);
        $this->assertEquals(10, $original->getLines());
        $this->assertEquals(100, $withLines->getLines());

        $withCursor = $original->withAfterCursor('cursor-123');
        $this->assertNotSame($original, $withCursor);
        $this->assertNull($original->getAfterCursor());
        $this->assertEquals('cursor-123', $withCursor->getAfterCursor());

        $withOutput = $original->withOutput('json');
        $this->assertNotSame($original, $withOutput);
        $this->assertEquals('short', $original->getOutput());
        $this->assertEquals('json', $withOutput->getOutput());

        $withUnit = $original->withUnit('nginx.service');
        $this->assertNotSame($original, $withUnit);
        $this->assertNull($original->getUnit());
        $this->assertEquals('nginx.service', $withUnit->getUnit());

        $withUtc = $original->withUtc(true);
        $this->assertNotSame($original, $withUtc);
        $this->assertFalse($original->isUtc());
        $this->assertTrue($withUtc->isUtc());

        $withQuiet = $original->withQuiet(true);
        $this->assertNotSame($original, $withQuiet);
        $this->assertTrue($original->isQuiet());
        $this->assertTrue($withQuiet->isQuiet());
    }

    public function testWithOutputFields(): void
    {
        $original = new JournalCtl();
        $withFields = $original->withOutputFields('MESSAGE', 'PRIORITY', '_PID');
        
        $this->assertNotSame($original, $withFields);
        $this->assertEquals([], $original->getOutputFields());
        $this->assertEquals(['MESSAGE', 'PRIORITY', '_PID'], $withFields->getOutputFields());
    }

    public function testCommandBuildingWithAllOptions(): void
    {
        $journalCtl = $this->journalCtl
            ->withLines(50)
            ->withAfterCursor('cursor-abc')
            ->withOutput('json')
            ->withUnit('ssh.service')
            ->withOutputFields('MESSAGE', 'PRIORITY')
            ->withUtc(true)
            ->withQuiet(true);

        // We need to test the command that gets built
        // Since getIterator is protected, we'll test through follow() or get()
        $this->processMock->expects($this->once())
            ->method('run');
        $this->processMock->expects($this->once())
            ->method('isSuccessful')
            ->willReturn(true);
        $this->processMock->expects($this->once())
            ->method('getOutput')
            ->willReturn('');

        // We can't easily test the exact command building without 
        // breaking encapsulation, so we'll just verify the methods work
        $journalCtl->get();
        
        // Verify the configuration was set correctly
        $this->assertEquals(50, $journalCtl->getLines());
        $this->assertEquals('cursor-abc', $journalCtl->getAfterCursor());
        $this->assertEquals('json', $journalCtl->getOutput());
        $this->assertEquals('ssh.service', $journalCtl->getUnit());
        $this->assertEquals(['MESSAGE', 'PRIORITY'], $journalCtl->getOutputFields());
        $this->assertTrue($journalCtl->isUtc());
        $this->assertTrue($journalCtl->isQuiet());
    }

    public function testCommandBuildingWithFollow(): void
    {
        $journalCtl = $this->journalCtl
            ->withLines(100)
            ->withUnit('nginx.service');

        $this->processMock->expects($this->once())
            ->method('start');

        $journalCtl->follow();

        // Verify the configuration was set correctly
        $this->assertEquals(100, $journalCtl->getLines());
        $this->assertEquals('nginx.service', $journalCtl->getUnit());
    }

    public function testGetLinesDefaultValue(): void
    {
        $journalCtl = new JournalCtl();
        $this->assertEquals(10, $journalCtl->getLines());
    }

    public function testGetAfterCursorDefaultValue(): void
    {
        $journalCtl = new JournalCtl();
        $this->assertNull($journalCtl->getAfterCursor());
    }

    public function testGetOutputDefaultValue(): void
    {
        $journalCtl = new JournalCtl();
        $this->assertEquals('short', $journalCtl->getOutput());
    }

    public function testGetUnitDefaultValue(): void
    {
        $journalCtl = new JournalCtl();
        $this->assertNull($journalCtl->getUnit());
    }

    public function testGetOutputFieldsDefaultValue(): void
    {
        $journalCtl = new JournalCtl();
        $this->assertEquals([], $journalCtl->getOutputFields());
    }

    public function testIsUtcDefaultValue(): void
    {
        $journalCtl = new JournalCtl();
        $this->assertFalse($journalCtl->isUtc());
    }

    public function testIsQuietDefaultValue(): void
    {
        $journalCtl = new JournalCtl();
        $this->assertTrue($journalCtl->isQuiet());
    }

    public function testWithAfterCursorNull(): void
    {
        $journalCtl = new JournalCtl();
        $withCursor = $journalCtl->withAfterCursor('cursor');
        $withoutCursor = $withCursor->withAfterCursor(null);
        
        $this->assertNull($withoutCursor->getAfterCursor());
    }

    public function testWithUnitNull(): void
    {
        $journalCtl = new JournalCtl();
        $withUnit = $journalCtl->withUnit('service');
        $withoutUnit = $withUnit->withUnit(null);
        
        $this->assertNull($withoutUnit->getUnit());
    }

    public function testMinimalCommandBuilding(): void
    {
        // Test with no options set
        $this->processMock->expects($this->once())
            ->method('run');
        $this->processMock->expects($this->once())
            ->method('isSuccessful')
            ->willReturn(true);
        $this->processMock->expects($this->once())
            ->method('getOutput')
            ->willReturn('');

        $this->journalCtl->get();

        // Verify defaults are maintained
        $this->assertEquals('short', $this->journalCtl->getOutput());
        $this->assertEquals(10, $this->journalCtl->getLines());
        $this->assertTrue($this->journalCtl->isQuiet());
    }

    public function testProcessWithDefaultOptions(): void
    {
        $processFactoryMock = $this->createMock(ProcessFactory::class);
        $journalCtl = new JournalCtl('journalctl', 60, $processFactoryMock);

        $processMock = $this->createMock(Process::class);
        
        $processFactoryMock->expects($this->once())
            ->method('create')
            ->with(
                ['journalctl', '--output', 'short', '--lines', 10, '--quiet'],
                null,
                null,
                null,
                60
            )
            ->willReturn($processMock);

        // Use reflection to access the protected process method
        $reflection = new \ReflectionClass($journalCtl);
        $processMethod = $reflection->getMethod('process');
        $processMethod->setAccessible(true);

        $result = $processMethod->invoke($journalCtl, []);
        
        $this->assertSame($processMock, $result);
    }

    public function testProcessWithAllOptionsEnabled(): void
    {
        $processFactoryMock = $this->createMock(ProcessFactory::class);
        $journalCtl = new JournalCtl('journalctl', 30, $processFactoryMock);

        $processMock = $this->createMock(Process::class);
        
        // Configure JournalCtl with all options
        $journalCtl = $journalCtl
            ->withOutput('json')
            ->withLines(50)
            ->withUtc(true)
            ->withQuiet(false)
            ->withOutputFields('MESSAGE', 'PRIORITY', 'SYSLOG_IDENTIFIER')
            ->withUnit('nginx.service')
            ->withAfterCursor('s=abc123');

        $expectedCommand = [
            'journalctl', 
            '--output', 'json', 
            '--lines', 50,
            '--utc',
            '--output-fields', 'MESSAGE,PRIORITY,SYSLOG_IDENTIFIER',
            '--unit', 'nginx.service',
            '--after-cursor', 's=abc123'
        ];

        $processFactoryMock->expects($this->once())
            ->method('create')
            ->with(
                $expectedCommand,
                null,
                null,
                null,
                30
            )
            ->willReturn($processMock);

        // Use reflection to access the protected process method
        $reflection = new \ReflectionClass($journalCtl);
        $processMethod = $reflection->getMethod('process');
        $processMethod->setAccessible(true);

        $result = $processMethod->invoke($journalCtl, []);
        
        $this->assertSame($processMock, $result);
    }

    public function testProcessWithCustomArguments(): void
    {
        $processFactoryMock = $this->createMock(ProcessFactory::class);
        $journalCtl = new JournalCtl('journalctl', 45, $processFactoryMock);

        $processMock = $this->createMock(Process::class);
        
        // Configure JournalCtl with some options
        $journalCtl = $journalCtl
            ->withOutput('cat')
            ->withLines(25)
            ->withUnit('systemd-logind.service');

        $customArgs = ['--since', '2023-01-01', '--until', '2023-12-31'];
        
        $expectedCommand = [
            'journalctl', 
            '--output', 'cat', 
            '--lines', 25,
            '--since', '2023-01-01',
            '--until', '2023-12-31',
            '--quiet',
            '--unit', 'systemd-logind.service'
        ];

        $processFactoryMock->expects($this->once())
            ->method('create')
            ->with(
                $expectedCommand,
                null,
                null,
                null,
                45
            )
            ->willReturn($processMock);

        // Use reflection to access the protected process method
        $reflection = new \ReflectionClass($journalCtl);
        $processMethod = $reflection->getMethod('process');
        $processMethod->setAccessible(true);

        $result = $processMethod->invoke($journalCtl, $customArgs);
        
        $this->assertSame($processMock, $result);
    }

    public function testProcessWithFollowOption(): void
    {
        $processFactoryMock = $this->createMock(ProcessFactory::class);
        $journalCtl = new JournalCtl('custom-journalctl', 120, $processFactoryMock);

        $processMock = $this->createMock(Process::class);
        $processMock->expects($this->once())
            ->method('start')
            ->with(null);
        
        $expectedCommand = [
            'custom-journalctl', 
            '--output', 'short', 
            '--lines', 10,
            '--follow',
            '--quiet'
        ];

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

        $result = $journalCtl->follow();
        
        $this->assertSame($processMock, $result);
    }

    public function testProcessWithComplexConfiguration(): void
    {
        $processFactoryMock = $this->createMock(ProcessFactory::class);
        $journalCtl = new JournalCtl('journalctl', 90, $processFactoryMock);

        $processMock = $this->createMock(Process::class);
        
        // Configure with multiple output fields and complex setup
        $journalCtl = $journalCtl
            ->withOutput('json-pretty')
            ->withLines(100)
            ->withUtc(true)
            ->withQuiet(false)
            ->withOutputFields('_SYSTEMD_UNIT', 'MESSAGE', 'PRIORITY', '_PID', '_COMM')
            ->withUnit('docker.service')
            ->withAfterCursor('s=def456xyz789');

        $customArgs = ['--priority', 'err', '--boot', '0'];
        
        $expectedCommand = [
            'journalctl', 
            '--output', 'json-pretty', 
            '--lines', 100,
            '--priority', 'err',
            '--boot', '0',
            '--utc',
            '--output-fields', '_SYSTEMD_UNIT,MESSAGE,PRIORITY,_PID,_COMM',
            '--unit', 'docker.service',
            '--after-cursor', 's=def456xyz789'
        ];

        $processFactoryMock->expects($this->once())
            ->method('create')
            ->with(
                $expectedCommand,
                null,
                null,
                null,
                90
            )
            ->willReturn($processMock);

        // Use reflection to access the protected process method
        $reflection = new \ReflectionClass($journalCtl);
        $processMethod = $reflection->getMethod('process');
        $processMethod->setAccessible(true);

        $result = $processMethod->invoke($journalCtl, $customArgs);
        
        $this->assertSame($processMock, $result);
    }

    public function testProcessCommandLineBuilding(): void
    {
        $processFactoryMock = $this->createMock(ProcessFactory::class);
        $journalCtl = new JournalCtl('journalctl', 60, $processFactoryMock);

        // Test minimal configuration
        $processMock1 = $this->createMock(Process::class);
        $processFactoryMock->expects($this->exactly(2))
            ->method('create')
            ->withConsecutive(
                [
                    ['journalctl', '--output', 'short', '--lines', 10, '--quiet'],
                    null, null, null, 60
                ],
                [
                    ['journalctl', '--output', 'short', '--lines', 10, '--quiet', '--unit', 'ssh.service'],
                    null, null, null, 60
                ]
            )
            ->willReturnOnConsecutiveCalls($processMock1, $this->createMock(Process::class));

        $reflection = new \ReflectionClass($journalCtl);
        $processMethod = $reflection->getMethod('process');
        $processMethod->setAccessible(true);

        $result1 = $processMethod->invoke($journalCtl, []);
        $this->assertSame($processMock1, $result1);

        // Test with unit filter
        $journalCtlWithUnit = $journalCtl->withUnit('ssh.service');
        $result2 = $processMethod->invoke($journalCtlWithUnit, []);
        $this->assertInstanceOf(Process::class, $result2);
    }

    public function testProcessWithEmptyArguments(): void
    {
        $processFactoryMock = $this->createMock(ProcessFactory::class);
        $journalCtl = new JournalCtl('journalctl', 60, $processFactoryMock);

        $processMock = $this->createMock(Process::class);
        
        $processFactoryMock->expects($this->once())
            ->method('create')
            ->with(
                ['journalctl', '--output', 'short', '--lines', 10, '--quiet'],
                null,
                null,
                null,
                60
            )
            ->willReturn($processMock);

        // Use reflection to test with empty array
        $reflection = new \ReflectionClass($journalCtl);
        $processMethod = $reflection->getMethod('process');
        $processMethod->setAccessible(true);

        $result = $processMethod->invoke($journalCtl, []);
        
        $this->assertSame($processMock, $result);
    }
}