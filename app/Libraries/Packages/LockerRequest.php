<?php

namespace App\Libraries\Packages;

use App\Models\PackageModel;
use App\Models\TaskModel;
use App\Models\CellModel;
use App\Models\DetailModel;
use App\Models\HeartbeatModel;
use App\Models\ServiceCodeModel;
use App\Models\ServiceCodeUseModel;

use App\Exceptions\ValidationException;
use App\Libraries\Logger\Logger;
use App\Libraries\Packages\TaskDataFilter;
use App\Libraries\Packages\Package;
use App\Libraries\Packages\Locker;
use App\Libraries\Packages\ServiceCode;
use App\Libraries\Packages\LockerServiceCode;
use App\Libraries\Packages\Task;

class LockerRequest
{
    private $request;
    private $requestType;
    private $package;
    private $locker;
    private $serviceCode;
    private $lockerServiceCode;
    private $task;

    public function __construct($request)
    {
        $this->request = $request;
        $this->package = new Package();
        $this->locker = new Locker($this->request->decodedJwt->clientId);
        $this->serviceCode = new ServiceCode($this->request->companyData->id, $this->request->decodedJwt->clientId);
        $this->lockerServiceCode = new LockerServiceCode($this->request->decodedJwt->clientId);
        $this->task = new Task($this->request->decodedJwt->clientId);
        $this->getType();
    }

    public function getType()
    {
        Logger::log(48, 'LOCKER REQUEST', 'getType', 'locker', $this->request->decodedJwt->clientId);
        Logger::log(48, $this->request->getVar('code'), 'LOCKER REQUEST CODE', 'locker', $this->request->decodedJwt->clientId);

        if ($this->lockerServiceCode->isLockerSreviceCode($this->request->getVar('code'))) {
            $this->requestType = 'lockerServiceCode';
            Logger::log(48, 'LOCKER REQUEST', 'isLockerSreviceCode', 'locker', $this->request->decodedJwt->clientId);
            return;
        }

        //check if code is a servicecode
        if ($this->serviceCode->isSreviceCode($this->request->getVar('code'))) {
            Logger::log(48, 'LOCKER REQUEST', 'isLockerSreviceCode', 'locker', $this->request->decodedJwt->clientId);
            $this->requestType = 'createService';
            return;
        }

        if ($this->package->loadFromInsertCodeAndLocker($this->request->getVar('code'), $this->request->decodedJwt->clientId)) {
            Logger::log(48, 'LOCKER REQUEST', 'isLockerSreviceCode', 'locker', $this->request->decodedJwt->clientId);
            $this->requestType = 'in';
            return;
        }

        //package is active for as long as its in a locker cell plus additional time for reopening of a cell door
        //note: package retrive codes are only unique among active packages
        if ($this->package->loadActiveFromRecipientCodeAndLocker($this->request->getVar('code'), $this->request->decodedJwt->clientId)) {
            Logger::log(48, 'LOCKER REQUEST', 'isLockerSreviceCode', 'locker', $this->request->decodedJwt->clientId);
            $this->requestType = 'out';
            return;
        }
    }

    public function manageRequest()
    {

        //$this->isLockerWorkingHours();
        Logger::log(48, $this->request, 'manageRequest', 'locker', $this->request->decodedJwt->clientId);

        if ($this->requestType === 'lockerServiceCode') {
            Logger::log(48, $this->request, 'Executing locker service code', 'locker', $this->request->decodedJwt->clientId);
            return $this->runLockerServiceCode();
        }

        if ($this->requestType === 'runService') {
            Logger::log(10, $this->request, 'Executing service task', 'locker', $this->request->decodedJwt->clientId);
            return $this->runServiceCodeTask();
        }

        if ($this->requestType === 'createService') {
            Logger::log(10, $this->request, 'Creating service code use task', 'locker', $this->request->decodedJwt->clientId);
            return $this->createServiceCodeUse();
        }

        if ($this->requestType === 'in') {
            Logger::log(0, $this->request, 'Insert package request', 'locker', $this->request->decodedJwt->clientId);
            return $this->in();
        }

        if ($this->requestType === 'out') {
            Logger::log(0, $this->request, 'Remove package request', 'locker', $this->request->decodedJwt->clientId);
            return $this->out();
        }

        Logger::log(48, 'NO ACTION FOUND', 'manageRequest', 'locker', $this->request->decodedJwt->clientId);
        throw new ValidationException('Package not found or is no longer active', 201, 404);
    }

    private function runLockerServiceCode()
    {
        $this->lockerServiceCode->execute($this->request->getVar('code'));
    }

    private function createServiceCodeUse()
    {

        $this->clientNotAllowed();

        $this->locker->saveHeartbeat();

        $this->serviceCode->newTask($this->request->getVar('code'));

        return true;
    }

    private function in()
    {

        $this->clientNotAllowed();

        $this->locker->saveHeartbeat();

        if (!$this->package->exists()) {
            throw new ValidationException('No package found', 202, 404);
        }

        if (!$this->package->belongsToLocker($this->request->decodedJwt->clientId)) {
            throw new ValidationException('Wrong locker for that package', 202, 404);
        }

        if ($this->package->isRemoved()) {
            throw new ValidationException('Package already removed', 207, 409);
        }

        if ($this->serviceCode->LockerHasTasksToExecute()) {
            $this->runServiceCodeTask();
        }

        if ($this->locker->isBusyWithPackageOtherThan($this->package->package->id)) {
            throw new ValidationException('Locker is busy. Try again later', 3, 403);
        }

        if ($this->package->isInLocker() || $this->package->isInsertReady()) {
            if ($this->package->isInLockerForGood()) {
                throw new ValidationException('Package is in the locker already and ' . PACKAGE_ADDITIONAL_ACTIVE_TIME . ' sec. have passed', 204, 409);
            }
            //$this->createOpenCellTask($this->package->package->cell_sort_id);
            $this->task->create('open-cell', $this->package->package->cell_sort_id);
            return true;
        }

        if ($this->locker->isNoEmptyCells($this->package->package->size)) {
            throw new ValidationException('No empty cells', 205, 409);
        }

        $this->package->makeInsertReady($this->locker->emptyCells[0]->cell_sort_id);
        //$this->createOpenCellTask($this->package->package->cell_sort_id);
        $this->task->create('open-cell', $this->package->package->cell_sort_id);

        return true;
    }

    private function out()
    {

        $this->clientNotAllowed();

        $this->locker->saveHeartbeat();

        if (!$this->package->exists()) {
            throw new ValidationException('No package found', 202, 404);
        }

        if (!$this->package->isInLocker()) {
            throw new ValidationException('Package is not in a locker yet', 203, 409);
        }

        if ($this->locker->isBusyWithPackageOtherThan($this->package->package->id)) {
            throw new ValidationException('Locker is busy. Try again later', 3, 403);
        }

        if ($this->package->isRemoved()) {
            //no longer used; database query gets only active packages
            //$this->createOpenCellTask($this->package->package->cell_sort_id);
            $this->task->create('open-cell', $this->package->package->cell_sort_id);
            return true;
        }

        $this->package->makeRemoveReady();
        $this->task->create('open-cell', $this->package->package->cell_sort_id);
        //$this->createOpenCellTask($this->package->package->cell_sort_id);

        return true;
    }

    private function runServiceCodeTask()
    {

        if ($this->package->package->status != 'new' && $this->package->package->status != 'insert-ready') {

            $this->serviceCode->cancelTasks($this->request->decodedJwt->clientId);
            throw new ValidationException('Package cannot be changed', 3, 403);
        }

        switch ($this->serviceCode->getTask()) {
            case 'reset-size-a':
                $this->package->resetAndSizeTo('a');
                break;
            case 'reset-size-b':
                $this->package->resetAndSizeTo('b');
                break;
        }

        $this->serviceCode->setTaskExecutedAndCancelOthers();
    }

    //save tasks in db and then send it back
    /*private function createOpenCellTask($cellId){

        $task = new Task($this->request->decodedJwt->clientId);
        $task->create('open-cell', $cellId);

    }*/



    private function serviceTaskExist()
    {
        $serviceCodeUseModel = new ServiceCodeUseModel();
        return $serviceCodeUseModel->codesToExecuteExist($this->request->decodedJwt->clientId);
    }

    private function clientNotAllowed()
    {
        //only locker clients allowed
        if ($this->request->decodedJwt->client != 'locker') {
            throw new ValidationException('Unauthorized client', 3, 403);
        }
    }
}
