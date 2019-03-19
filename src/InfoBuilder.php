<?php
/**
 * Created by PhpStorm.
 * User: ts
 * Date: 2019-03-08
 * Time: 00:42
 */

namespace MototokCloud\System;


use MototokCloud\System\Info\AbstractUnitInfo;
use MototokCloud\System\Info\MemoryInfo;
use MototokCloud\System\Info\ServiceInfo;
use MototokCloud\System\Info\TimerInfo;
use MototokCloud\System\Info\UptimeInfo;
use Symfony\Component\Process\Process;

class InfoBuilder
{


    /** @var SystemCtl */
    private $systemCtl;

    /**
     * InfoBuilder constructor.
     * @param SystemCtl $systemCtl
     */
    public function __construct(SystemCtl $systemCtl)
    {
        $this->systemCtl = $systemCtl;
    }


    public function uptime(): UptimeInfo
    {
        $process = (new Process('uptime -p'));
        $uptimePretty = trim($process->mustRun()->getOutput());

        $process = new Process('uptime');
        $output = $process->mustRun()->getOutput();
        $ok = preg_match('/load averages?: ([0-9]+(?:,|.)[0-9]+),? ([0-9]+(?:,|.)[0-9]+),? ([0-9]+(?:,|.)[0-9]+)/', $output, $matches);
        if (!$ok) {
            throw new \LogicException();
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
        $process = new Process('free --bytes');
        $process->run();
        $output = $process->getOutput();

        $ok = preg_match('/^Mem:\h+([0-9]+)\h+([0-9]+)/m', $output, $matches);
        if (!$ok) {
            throw new \LogicException();
        }
        $memTotal = $matches[1];
        $memUsed = $matches[2];

        $ok = preg_match('/^Swap:\h+([0-9]+)\h+([0-9]+)/m', $output, $matches);
        if (!$ok) {
            throw new \LogicException();
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
            throw new \UnexpectedValueException($msg);
        }
        $ext = pathinfo($id, PATHINFO_EXTENSION);
        if ($ext === 'service') {
            return $this->getServiceInfo($unit);
        } else if ($ext === 'timer') {
            return $this->getTimerInfo($unit);
        }
        $msg = sprintf('Unable to get info for %s: Unsupported unit type %s.', $id, $ext);
        throw new \UnexpectedValueException($msg);
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