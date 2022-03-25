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
use App\Libraries\Packages\Task;

class UserRequest{
    private $request;
    private $requestType;
    private $package;
    private $locker;
    private $serviceCode;
    private $task;

    public function __construct($request){
        $this->request = $request;
        $this->package = new Package();
        
        $this->getType();
        
        
    }

    public function getPackage(){
        return $this->package->package;
    }

    public function getType(){

        if($this->package->loadActiveFromInsertCode($this->request->getVar('code'))){
            $this->locker = new Locker($this->package->package->locker_id);
            $this->task = new Task($this->package->package->locker_id);
            $this->requestType = 'in';
            return;
        }

        //package is active for as long as its in a locker cell plus additional time for reopening of a cell door
        //note: package retrive codes are only unique among active packages
        if($this->package->loadActiveFromRecipientCode($this->request->getVar('code'))){
            $this->locker = new Locker($this->package->package->locker_id);
            $this->task = new Task($this->package->package->locker_id);
            $this->requestType = 'out';
            return;
        }
    }

    public function manageRequest(){

        //$this->isLockerWorkingHours();

        if($this->requestType === 'in'){
   
            return $this->in();
        }

        if($this->requestType === 'out'){

            return $this->out();
        }

        
        throw new ValidationException('Package not found or is no longer active', 201, 404);
    }


    private function in(){


        $this->locker->saveHeartbeat();

        if(!$this->package->exists()){
            throw new ValidationException('No package found', 202, 404);
        }

        if(!$this->package->permissionCheck()){ 
            throw new ValidationException('Brak dostępu do paczki', 202, 409);
        }

       /* if(!$this->package->belongsToLocker($this->request->decodedJwt->clientId)){
            throw new ValidationException('Wrong locker for that package', 202, 404);
        }*/

        if($this->package->isRemoved()){
            throw new ValidationException('Package already removed', 207, 409);
        }

        //if($this->serviceCode->LockerHasTasksToExecute()){
        //    $this->runServiceCodeTask();
        //}

        if($this->locker->isBusyWithPackageOtherThan($this->package->package->id)){
            throw new ValidationException('Locker is busy. Try again later', 3, 403);
        }

        if($this->package->isInLocker() || $this->package->isInsertReady()){
            if($this->package->isInLockerForGood()){
                throw new ValidationException('Package is in the locker already and '.PACKAGE_ADDITIONAL_ACTIVE_TIME.' sec. have passed', 204 , 409);
            }
            //$this->createOpenCellTask($this->package->package->cell_sort_id);
            $this->task->create('open-cell', $this->package->package->cell_sort_id);
            return true;
        }

        if($this->locker->isNoEmptyCells($this->package->package->size)){
            throw new ValidationException('No empty cells', 205, 409);
        }

        $this->package->makeInsertReady($this->locker->emptyCells[0]->cell_sort_id);
        //$this->createOpenCellTask($this->package->package->cell_sort_id);
        $this->task->create('open-cell', $this->package->package->cell_sort_id);
        
        return true;
    }

    private function out(){



        $this->locker->saveHeartbeat();

        if(!$this->package->exists()){
            throw new ValidationException('No package found', 202, 404);
        }
        
        if(!$this->package->isInLocker()){
            throw new ValidationException('Package is not in a locker yet', 203, 409);
        }

        if($this->locker->isBusyWithPackageOtherThan($this->package->package->id)){
            throw new ValidationException('Locker is busy. Try again later', 3, 403);
        }

        if($this->package->isRemoved()){
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



}