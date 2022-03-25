<?php

namespace App\Libraries\Packages;

use App\Models\LockerServiceCodeModel;
use App\Libraries\Packages\Task;
use App\Libraries\Logger\Logger;

class LockerServiceCode
{

    private $lockerId;
    private $lockerServiceCodeModel;
    private $actions = [
        'open-cell' => 'executeOpenCell',
    ];


    public function __construct(int $lockerId)
    {
        $this->lockerId = $lockerId;
        $this->lockerServiceCodeModel = new LockerServiceCodeModel();
    }

    public function isLockerSreviceCode($code)
    {
        Logger::log(48, $code, 'isLockerSreviceCode', 'locker', $this->lockerId);
        if (!str_contains($code, 'lscode')) {
            Logger::log(48, $code, 'not service code', 'locker', $this->lockerId);
            return false;
        }

        return $this->lockerServiceCodeModel->lockerCodeExists($code, $this->lockerId);
    }

    public function execute($code)
    {
        Logger::log(48, $code, 'execute CODE', 'locker', $this->lockerId);
        $lockerServiceCode = $this->lockerServiceCodeModel->get($code);

        if (!$lockerServiceCode) {
            Logger::log(48, $code, 'no code found', 'locker', $this->lockerId);
            return;
        }

        if (!isset($this->actions[$lockerServiceCode->action])) {
            Logger::log(48, $code, 'Code not in whitelist array', 'locker', $this->lockerId);
            return;
        }

        $methodName = $this->actions[$lockerServiceCode->action];

        $this->$methodName($lockerServiceCode);
    }

    private function executeOpenCell($lockerServiceCode)
    {
        Logger::log(48, $lockerServiceCode, 'executeOpenCell', 'locker', $this->lockerId);
        $task = new Task($this->lockerId);
        $task->create('open-cell', $lockerServiceCode->value);
    }

    public function getCodes()
    {
        return $this->lockerServiceCodeModel->getForLocker($this->lockerId);
    }
}
