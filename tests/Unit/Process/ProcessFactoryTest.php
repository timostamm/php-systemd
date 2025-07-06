<?php

namespace TS\PhpSystemD\Tests\Unit\Process;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;
use TS\PhpSystemD\Process\DefaultProcessFactory;

class ProcessFactoryTest extends TestCase
{
    public function testDefaultProcessFactoryCreatesProcess(): void
    {
        $factory = new DefaultProcessFactory();
        $process = $factory->create(['echo', 'test']);
        
        $this->assertInstanceOf(Process::class, $process);
        $this->assertEquals("'echo' 'test'", $process->getCommandLine());
    }

    public function testDefaultProcessFactoryWithAllParameters(): void
    {
        $factory = new DefaultProcessFactory();
        $process = $factory->create(
            ['echo', 'test'],
            '/tmp',
            ['TEST_VAR' => 'value'],
            'input data',
            30.0
        );
        
        $this->assertInstanceOf(Process::class, $process);
        $this->assertEquals("'echo' 'test'", $process->getCommandLine());
        $this->assertEquals('/tmp', $process->getWorkingDirectory());
        $this->assertEquals(30.0, $process->getTimeout());
    }

    public function testDefaultProcessFactoryWithDefaults(): void
    {
        $factory = new DefaultProcessFactory();
        $process = $factory->create(['pwd']);
        
        $this->assertInstanceOf(Process::class, $process);
        $this->assertEquals("'pwd'", $process->getCommandLine());
        $this->assertNull($process->getTimeout()); // Default timeout is null
    }
}