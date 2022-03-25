<?php
namespace App\Libraries\Packages;

use App\Models\ServiceCodeModel;
use App\Models\ServiceCodeUseModel;

class ServiceCode{

    private $serviceCodeModel;
    private $serviceCodeUseModel;
    private $companyId;
    private $lockerId;
    private $actions = ['reset-size-b', 'reset-size-a'];
    private $currentServiceCode;
    private $currentServiceCodeUse;

    public function __construct(int $companyId, int $lockerId){
        $this->serviceCodeModel = new ServiceCodeModel();
        $this->serviceCodeUseModel = new ServiceCodeUseModel();
        $this->companyId = $companyId;
        $this->lockerId = $lockerId;
    }

    public function LockerHasTasksToExecute(){
        return $this->serviceCodeUseModel->codesToExecuteExist($this->lockerId);
    }

    public function isSreviceCode($code){
        return $this->serviceCodeModel->isCompanyServiceCode($this->companyId, $code);
    }

    public function newTask($code){
        if(!$this->companyId || !$this->lockerId || !$code){
            throw new \Exception('Invalid input');
        }

        $serviceCode = $this->serviceCodeModel->getCompanyServiceCode($this->companyId, $code);
        $this->createTask($serviceCode->id);
    }

    public function createTask($codeId){ //-> createServiceCodeUse
        $serviceCodeUse = new \App\Entities\ServiceCodeUse();
        $serviceCodeUse->locker_id = $this->lockerId;
        $serviceCodeUse->code_id = $codeId;
        $serviceCodeUse->status = 'execute';

        $this->serviceCodeUseModel->save($serviceCodeUse);
    }

    public function cancelTasks(){

        if(!$this->lockerId){
            throw new \Exception('Invalid input');
        }

        $this->serviceCodeUseModel->cancelLockerExecuteCodes($this->lockerId);
    }



    public function getTask(){

        $this->currentServiceCodeUse = $this->serviceCodeUseModel->getToExecute($this->lockerId);

        //$serviceCodeModel = new ServiceCodeModel();
        $this->serviceCode = $this->serviceCodeModel->get($this->currentServiceCodeUse->code_id);
        return $this->serviceCode->action;


       /* if($this->serviceCode->action == 'reset-size-b'){
            //return action (?)
            $this->package->resetAndSizeTo('b');

        }elseif($this->serviceCode->action == 'reset-size-a'){

            $this->package->resetAndSizeTo('a');

        }*/

        

    }

    public function setTaskExecutedAndCancelOthers(){
        if(in_array($this->serviceCode->action, $this->actions)){
            $this->currentServiceCodeUse->status = 'executed';
            $this->serviceCodeUseModel->save($this->currentServiceCodeUse);
            $this->cancelTasks($this->lockerId);
        }
    }
}