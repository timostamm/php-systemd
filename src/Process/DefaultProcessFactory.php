<?php

namespace TS\PhpSystemD\Process;

use Symfony\Component\Process\Process;

class DefaultProcessFactory implements ProcessFactory
{
    /**
     * Create a new Process instance
     *
     * @param array $command The command to run and its arguments listed as separate entries
     * @param string|null $cwd The working directory or null to use the working directory of the current PHP process
     * @param array|null $env The environment variables or null to use the same environment as the current PHP process
     * @param mixed $input The input as stream resource, scalar or \Traversable, or null for no input
     * @param float|null $timeout The timeout in seconds or null to disable
     * @return Process
     */
    public function create(array $command, string $cwd = null, array $env = null, $input = null, float $timeout = null): Process
    {
        return new Process($command, $cwd, $env, $input, $timeout);
    }
}