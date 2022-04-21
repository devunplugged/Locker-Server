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

class LockerRequest{
    private $request;
    private $requestType;
    private $package;


    public function __construct($request){
        $this->request = $request;

        $this->getType();
    }

    public function getType(){

        

        //check if code is a servicecode
        $serviceCodeModel = new ServiceCodeModel();
        if($serviceCodeModel->isCompanyServiceCode($this->request->companyData->id, $this->request->getVar('code'))){
            $this->requestType = 'createService';
            return;
        }

        $packageModel = new PackageModel();
        $this->package = $packageModel->getPackageByCodeAndLocker($this->request);//->getVar('code')

        if(count($this->package) > 0){
            $this->requestType = 'in';
            return;
        }

        //package is active for as long as its in a locker cell plus additional time for reopening of a cell door
        //note: package retrive codes are only unique among active packages
        $this->package = $packageModel->getActivePackageByRecipientCodeAndLocker($this->request);

        if(count($this->package) > 0){
            $this->requestType = 'out';
            return;
        }
    }

    public function manageRequest(){

        //$this->isLockerWorkingHours();

        if($this->requestType === 'runService'){
            Logger::log(10, $this->request, 'Executing service task', 'locker', $this->request->decodedJwt->clientId);
            return $this->runServiceCodeTask();
        }

        if($this->requestType === 'createService'){
            Logger::log(10, $this->request, 'Creating service code use task', 'locker', $this->request->decodedJwt->clientId);
            return $this->createServiceCodeUse();
        }

        if($this->requestType === 'in'){
            Logger::log(0, $this->request, 'Insert package request', 'locker', $this->request->decodedJwt->clientId);
            return $this->in();
        }

        if($this->requestType === 'out'){
            Logger::log(0, $this->request, 'Remove package request', 'locker', $this->request->decodedJwt->clientId);
            return $this->out();
        }

        
        throw new ValidationException('Package not found or is no longer active', 201, 404);
    }

    

    private function createServiceCodeUse(){

        $this->clientNotAllowed();

        $this->saveHeartbeat();

        $serviceCodeModel = new ServiceCodeModel();
        $serviceCode = $serviceCodeModel->getCompanyServiceCode($this->request->companyData->id, $this->request->getVar('code'));

        Logger::log(98, $serviceCode, 'serviceCode');

        $serviceCodeUse = new \App\Entities\ServiceCodeUse();
        $serviceCodeUse->locker_id = $this->request->decodedJwt->clientId;
        $serviceCodeUse->code_id = $serviceCode->id;
        $serviceCodeUse->status = 'execute';

        $serviceCodeUseModel = new ServiceCodeUseModel();
        $serviceCodeUseModel->save($serviceCodeUse);

        return [
            'response' => ['status' => 200],
            'code' => 200,
        ];
    }

    private function in(){

        $this->clientNotAllowed();

        $this->saveHeartbeat();

        $this->packageDoesntExists();

        $this->packageBelongsToOther();

        if($this->packageRemoved()){
            throw new ValidationException('Package already removed', 207, 409);
        }

        if($this->serviceTaskExist()){
            $this->runServiceCodeTask();
        }

        //is there other insert/remove ready package? One insert at a time, block others
        $this->lockerIsBusyWithOtherPackage();

        if($this->packageInLocker() || $this->packageReadyToInsert()){
            $this->packageInLockerBeyondAdditionalTime();

            return [
                'response' => ['status' => 200, 'tasks' => [$this->getOpenCellTask($this->package[0]->cell_id)]],
                'code' => 200,
            ];
        }

        $cellModel = new CellModel;
        $emptyCells = $cellModel->getEmptyCells($this->request->decodedJwt->clientId, $this->package[0]->size);

        $this->noEmptyCells($emptyCells);

        
      
        $this->makePackageInsertReady($emptyCells[0]->cell_sort_id);

       // Logger::log(5, 'before filter', 'filter key');
        return [
            'response' => ['status' => 200, 'tasks' => [$this->getOpenCellTask($emptyCells[0]->cell_sort_id)]],
            'code' => 200,
        ];
    }

    private function out(){

        $this->clientNotAllowed();

        $this->saveHeartbeat();

        $this->packageDoesntExists();
        
        $this->packageNotInLockerYet();

        $this->lockerIsBusyWithOtherPackage();

        if($this->packageRemoved()){
            //no longer used; database query gets only active packages
            
            return [
                'response' => ['status' => 200, 'tasks' => [$this->getOpenCellTask($this->package[0]->cell_id)]],
                'code' => 200,
            ];
        }
        
        $this->makePackageRemoveReady();
        //tmp here; should be moved after cell closed confirmation (?)
        //$this->removePackage();


        return [
            'response' => ['status' => 200, 'tasks' => [$this->getOpenCellTask($this->package[0]->cell_id)]],
            'code' => 200,
        ];
    }

    private function runServiceCodeTask(){

        $serviceCodeUseModel = new ServiceCodeUseModel();

        if($this->package[0]->status != 'new' && $this->package[0]->status != 'insert-ready'){

            $serviceCodeUseModel->cancelLockerExecuteCodes($this->request->decodedJwt->clientId);
            throw new ValidationException('Package cannot be changed', 3, 403);

        }

        $serviceCodeUse = $serviceCodeUseModel->getToExecute($this->request->decodedJwt->clientId);

        $serviceCodeModel = new ServiceCodeModel();
        $serviceCode = $serviceCodeModel->get($serviceCodeUse->code_id);

        if($serviceCode->action == 'reset-size-b'){

            $this->package[0]->size = 'b';
            $this->package[0]->status = 'new';
            $this->package[0]->cell_id = null;
            $this->package[0]->enter_code_entered_at = null;
            $packageModel = new PackageModel();
            $packageModel->save($this->package[0]);

        }elseif($serviceCode->action == 'reset-size-a'){

            $this->package[0]->size = 'a';
            $this->package[0]->status = 'new';
            $this->package[0]->cell_id = null;
            $this->package[0]->enter_code_entered_at = null;
            $packageModel = new PackageModel();
            $packageModel->save($this->package[0]);

        }

        if($serviceCode->action == 'reset-size-b' || $serviceCode->action == 'reset-size-a'){
            $serviceCodeUse->status = 'executed';
            $serviceCodeUseModel->save($serviceCodeUse);
            $serviceCodeUseModel->cancelLockerExecuteCodes($this->request->decodedJwt->clientId);
        }

    }

    private function makePackageRemoveReady(){
        $this->package[0]->recipient_code_entered_at = date('Y-m-d H:i:s');
        $this->package[0]->status = 'remove-ready';
        $packageModel = new PackageModel();
        $packageModel->save($this->package[0]);
    }

    //save inserted_at and cell id in package entity
    
    private function makePackageInsertReady($cellId){
        Logger::log(0, '', 'Package scanned by locker', 'locker', $this->request->decodedJwt->clientId);
        $this->package[0]->status = 'insert-ready';
        $this->package[0]->cell_id = $cellId;
        $this->package[0]->enter_code_entered_at = date('Y-m-d H:i:s');
        //$this->package[0]->inserted_at = date("Y-m-d H:i:s");
        $packageModel = new PackageModel();
        $packageModel->save($this->package[0]);
    }

    //save cell remove_at in package entity
   /* private function removePackage(){
        Logger::log(0, '', 'Package removed', 'locker', $this->request->decodedJwt->clientId);

        $this->package[0]->removed_at = date("Y-m-d H:i:s");
        $packageModel = new PackageModel();
        $packageModel->save($this->package[0]);
    }*/

    //save tasks in db and then send it back
    private function getOpenCellTask($cellId){
        

        ///////////create some object for all task related staff
        $task = new \App\Entities\Task();
        $task->locker_id = $this->request->decodedJwt->clientId;
        $task->type = 'open-cell';
        $task->value = $cellId;
        $taskModel = new TaskModel();
        $taskModel->save($task);

        $task = new \stdClass();
        $task->task = 'open-cell';
        $task->value = $cellId;
        return $task;
    }

    private function saveHeartbeat(){
        $heartBeatModel = new HeartbeatModel();
        $heartBeatModel->saveBeat($this->request->decodedJwt->clientId);
    }

    //check if locker is working with other package
    private function lockerIsBusyWithOtherPackage(){
        $packageModel = new PackageModel();
        if($packageModel->insertOrRemoveReadyPackagesExistForLockerExcept($this->request->decodedJwt->clientId, $this->package[0]->id)){
            throw new ValidationException('Locker is busy. Try again later', 3, 403);
        }
    }

    private function serviceTaskExist(){
        $serviceCodeUseModel = new ServiceCodeUseModel();
        return $serviceCodeUseModel->codesToExecuteExist($this->request->decodedJwt->clientId);
    }

    private function clientNotAllowed(){
        //only locker clients allowed
        if($this->request->decodedJwt->client != 'locker'){
            throw new ValidationException('Unauthorized client', 3, 403);
        }
    }

    private function packageDoesntExists(){
        //package not found
        if(!$this->package){
            throw new ValidationException('No package found', 202, 404);
        }
    }

    private function packageBelongsToOther(){
        //package not found
        if($this->package[0]->locker_id != $this->request->decodedJwt->clientId){
            throw new ValidationException('Wrong locker for that package', 202, 404);
        }
    }

    private function packageNotInLockerYet(){
        //package not in a locker yet
        if(!$this->package[0]->inserted_at){
            throw new ValidationException('Package is not in a locker yet', 203, 409);
        }
    }

    private function packageReadyToInsert(){
        //package in a locker already
        if(!$this->package[0]->inserted_at && $this->package[0]->cell_id){
            return true;
        }
        return false;
    }

    private function packageInLocker(){
        //package in a locker already
        return $this->package[0]->inserted_at ? true : false;
    }

    private function packageInLockerBeyondAdditionalTime(){
        if($this->package[0]->inserted_at && $this->package[0]->inserted_at < date("Y-m-d H:i:s", time() - PACKAGE_ADDITIONAL_ACTIVE_TIME)){
            throw new ValidationException('Package is in the locker already and '.PACKAGE_ADDITIONAL_ACTIVE_TIME.' sec. have passed', 204 , 409);
        }
    }

    private function noEmptyCells($emptyCells){
        //no empty cells
        if(count($emptyCells) === 0){
            throw new ValidationException('No empty cells', 205, 409);
        }
    }

    private function packageRemoved(){
        return $this->package[0]->removed_at ? true : false;
    }

    private function packageRemovedAndLocked(){
        if($this->package[0]->removed_at < date("Y-m-d H:i:s", time() - PACKAGE_ADDITIONAL_ACTIVE_TIME)){
            throw new ValidationException('Package already removed and '.PACKAGE_ADDITIONAL_ACTIVE_TIME.' sec. have passed', 206, 409);
        }
    }

    private function isLockerWorkingHours(){
        //CHECK IF ITS OPEN HOURS FOR THAT LOCKER
        $detailModel = new DetailModel();
        $details = $detailModel->getDetails($this->request->decodedJwt->clientId);

        if(!isset($details['works_from']) && !isset($details['works_to'])){
            throw new ValidationException('Locker has no working hours set');
        }

        $currentHour = date('H');

        if($currentHour < $details['works_from'] || $currentHour > $details['works_to']){
            throw new ValidationException('This lockers working hours are: ' . $details['works_from'] . '-' . $details['works_to']);
        }
    }
}