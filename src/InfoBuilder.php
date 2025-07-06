<?php
/**
 * Created by PhpStorm.
 * User: ts
 * Date: 2019-03-08
 * Time: 00:42
 */

namespace TS\PhpSystemD;


use LogicException;
use TS\PhpSystemD\Info\AbstractUnitInfo;
use TS\PhpSystemD\Info\MemoryInfo;
use TS\PhpSystemD\Info\ServiceInfo;
use TS\PhpSystemD\Info\TimerInfo;
use TS\PhpSystemD\Info\UptimeInfo;
use TS\PhpSystemD\Process\DefaultProcessFactory;
use TS\PhpSystemD\Process\ProcessFactory;
use UnexpectedValueException;

class InfoBuilder
{


    private const RE_MEM = '/^Mem:\h+([0-9]+)\h+([0-9]+)/m';
    private const RE_SWAP = '/^Swap:\h+([0-9]+)\h+([0-9]+)/m';
    private const RE_UPTIME_LOAD = '/load averages?: ([0-9]+(?:,|.)[0-9]+),? ([0-9]+(?:,|.)[0-9]+),? ([0-9]+(?:,|.)[0-9]+)/';


    /** @var SystemCtl */
    private $systemCtl;

    /** @var ProcessFactory */
    private $processFactory;

    /**
     * InfoBuilder constructor.
     * @param SystemCtl $systemCtl
     * @param ProcessFactory|null $processFactory
     */
    public function __construct(SystemCtl $systemCtl, ProcessFactory $processFactory = null)
    {
        $this->systemCtl = $systemCtl;
        $this->processFactory = $processFactory ?: new DefaultProcessFactory();
    }


    public function uptime(): UptimeInfo
    {
        $process = $this->processFactory->create(['uptime', '-p']);
        $uptimePretty = trim($process->mustRun()->getOutput());

        $process = $this->processFactory->create(['uptime']);
        $output = $process->mustRun()->getOutput();
        $ok = preg_match(self::RE_UPTIME_LOAD, $output, $matches);
        if ($ok === false) {
            $msg = sprintf('RegEx error: %s', preg_last_error());
            throw new LogicException($msg);
        }
        if ($ok === 0) {
            $msg = sprintf('No regex match for uptime load averages. RegEx: "%s", text: "%s"', self::RE_UPTIME_LOAD, $output);
            throw new LogicException($msg);
        }
        $loadAvg1 = floatval(str_replace(',', '.', $matches[1]));
        $loadAvg5 = floatval(str_replace(',', '.', $matches[2]));
        $loadAvg15 = floatval(str_replace(',', '.', $matches[3]));

        return new UptimeInfo(
            $uptimePretty,
            $loadAvg1,
            $loadAvg5,
            $loadAvg15
        );
    }


    public function memory(): MemoryInfo
    {
        $process = $this->processFactory->create(['free', '--bytes']);
        $process->mustRun();
        $output = $process->getOutput();
        $ok = preg_match(self::RE_MEM, $output, $matches);
        if ($ok === false) {
            $msg = sprintf('RegEx error: %s', preg_last_error());
            throw new LogicException($msg);
        }
        if ($ok === 0) {
            $msg = sprintf('No regex match for memory info. RegEx: "%s", text: "%s"', self::RE_MEM, $output);
            throw new LogicException($msg);
        }
        $memTotal = $matches[1];
        $memUsed = $matches[2];
        $ok = preg_match(self::RE_SWAP, $output, $matches);
        if ($ok === false) {
            $msg = sprintf('RegEx error: %s', preg_last_error());
            throw new LogicException($msg);
        }
        if ($ok === 0) {
            $msg = sprintf('No regex match for swap info. RegEx: "%s", text: "%s"', self::RE_SWAP, $output);
            throw new LogicException($msg);
        }
        $swapTotal = $matches[1];
        $swapUsed = $matches[2];

        return new MemoryInfo($memTotal, $memUsed, $swapTotal, $swapUsed);
    }


    public function getUnitInfo(string $unit): AbstractUnitInfo
    {
        $id = $this->systemCtl->showProperty($unit, 'Id');
        if (empty($id)) {
            $msg = sprintf('Unable to get Id of %s: Unit seems to be unknown.', $unit);
            throw new UnexpectedValueException($msg);
        }
        $ext = pathinfo($id, PATHINFO_EXTENSION);
        if ($ext === 'service') {
            return $this->getServiceInfo($unit);
        } else if ($ext === 'timer') {
            return $this->getTimerInfo($unit);
        }
        $msg = sprintf('Unable to get info for %s: Unsupported unit type %s.', $id, $ext);
        throw new UnexpectedValueException($msg);
    }


    public function getTimerInfo(string $unit): TimerInfo
    {
        $statusText = $this->systemCtl->status($unit, 0);
        return new TimerInfo(
            $this->systemCtl->showProperty($unit, 'Id'),
            $this->systemCtl->showProperty($unit, 'Description'),
            $this->systemCtl->isActive($unit),
            $this->systemCtl->isEnabled($unit),
            $this->systemCtl->getActiveStatus($unit),
            $this->systemCtl->getEnabledStatus($unit),
            substr($statusText, strpos($statusText, "\n") + 1),
            $this->systemCtl->showPropertyDate($unit, 'LastTriggerUSec'),
            $this->systemCtl->showPropertyDate($unit, 'NextElapseUSecRealtime'),
            $this->systemCtl->showProperty($unit, 'Unit')
        );
    }


    public function getServiceInfo(string $unit): ServiceInfo
    {
        $statusText = $this->systemCtl->status($unit, 0);
        return new ServiceInfo(
            $this->systemCtl->showProperty($unit, 'Id'),
            $this->systemCtl->showProperty($unit, 'Type'),
            $this->systemCtl->showProperty($unit, 'Description'),
            $this->systemCtl->isActive($unit),
            $this->systemCtl->isEnabled($unit),
            $this->systemCtl->getActiveStatus($unit),
            $this->systemCtl->getEnabledStatus($unit),
            substr($statusText, strpos($statusText, "\n") + 1),
            $this->systemCtl->showPropertyBool($unit, 'CanStart'),
            $this->systemCtl->showPropertyBool($unit, 'CanStop'),
            $this->systemCtl->showPropertyBool($unit, 'CanReload'),
            $this->systemCtl->showPropertyDate($unit, 'StateChangeTimestamp'),
            $this->systemCtl->showProperty($unit, 'User')
        );
    }


}
