<?php
/**
 * Created by PhpStorm.
 * User: ts
 * Date: 2019-03-08
 * Time: 00:19
 */

namespace TS\PhpSystemD;

use RuntimeException;
use Symfony\Component\Process\Process;
use TS\PhpSystemD\Process\DefaultProcessFactory;
use TS\PhpSystemD\Process\ProcessFactory;


class JournalCtl
{


    /** @var string */
    private $command;

    /** @var float */
    private $commandTimeout;

    /** @var string[] */
    private $outputFields = [];

    /** @var bool */
    private $utc = false;

    /** @var string */
    private $output = 'short';

    /** @var string|null */
    private $unit;

    /** @var int */
    private $lines = 10;

    /** @var bool */
    private $quiet = true;

    /** @var string|null */
    private $afterCursor;

    /** @var ProcessFactory */
    private $processFactory;


    /**
     * JournalCtl constructor.
     * @param string $command
     * @param float $commandTimeout
     * @param ProcessFactory|null $processFactory
     */
    public function __construct(string $command = 'journalctl', float $commandTimeout = 60, ProcessFactory $processFactory = null)
    {
        $this->command = $command;
        $this->commandTimeout = $commandTimeout;
        $this->processFactory = $processFactory ?: new DefaultProcessFactory();
    }

    /**
     * Show only the most recent journal entries, and continuously print new entries as they are appended to the journal.
     *
     * @param callable|null $callback A PHP callback to run whenever there is some
     *                                output available on STDOUT or STDERR.
     *                                Takes two parameters: string $type (out/err), $data
     * @return Process
     */
    public function follow(callable $callback = null): Process
    {
        $args = ['--follow'];
        $process = $this->process($args);
        $process->start($callback);
        return $process;
    }


    public function get(): string
    {
        $process = $this->process([]);
        $process->run();
        if ($process->isSuccessful()) {
            return $process->getOutput();
        } else {
            $msg = sprintf('An error occurred while running "%s": %s', $process->getCommandLine(), $process->getErrorOutput());
            throw new RuntimeException($msg);
        }
    }


    public function getLines(): int
    {
        return $this->lines;
    }

    public function withLines(int $lines): self
    {
        $clone = clone $this;
        $clone->lines = $lines;
        return $clone;
    }


    public function getAfterCursor(): ?string
    {
        return $this->afterCursor;
    }

    public function withAfterCursor(?string $cursor): self
    {
        $clone = clone $this;
        $clone->afterCursor = $cursor;
        return $clone;
    }


    public function getOutput(): string
    {
        return $this->output;
    }

    public function withOutput(string $format): self
    {
        $clone = clone $this;
        $clone->output = $format;
        return $clone;
    }


    public function getUnit(): ?string
    {
        return $this->unit;
    }

    public function withUnit(?string $unit): self
    {
        $clone = clone $this;
        $clone->unit = $unit;
        return $clone;
    }


    public function getOutputFields(): array
    {
        return $this->outputFields;
    }

    public function withOutputFields(string ... $fields): self
    {
        $clone = clone $this;
        $clone->outputFields = $fields;
        return $clone;

    }


    public function isUtc(): bool
    {
        return $this->utc;
    }

    public function withUtc(bool $utc): self
    {
        $clone = clone $this;
        $clone->utc = $utc;
        return $clone;
    }


    public function isQuiet(): bool
    {
        return $this->quiet;
    }

    public function withQuiet(bool $quiet): self
    {
        $clone = clone $this;
        $clone->quiet = $quiet;
        return $clone;
    }


    protected function process(array $args): Process
    {
        $commandline = [$this->command, '--output', $this->output, '--lines', $this->lines];

        if ($this->utc) {
            array_push($args, '--utc');
        }

        if ($this->quiet) {
            array_push($args, '--quiet');
        }

        if (!empty($this->outputFields)) {
            array_push($args, '--output-fields', join(',', $this->outputFields));
        }

        if (!empty($this->unit)) {
            array_push($args, '--unit', $this->unit);
        }

        if (!empty($this->afterCursor)) {
            array_push($args, '--after-cursor', $this->afterCursor);
        }

        if (!empty($args)) {
            array_push($commandline, ...$args);
        }

        return $this->processFactory->create($commandline, null, null, null, $this->commandTimeout);
    }


}
