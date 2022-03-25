<?php
namespace App\Libraries\Packages;

use App\Models\PackageModel;
use App\Models\TaskModel;
use App\Models\CellModel;
use App\Exceptions\ValidationException;
use App\Libraries\Logger\Logger;


class TaskRaport{
    private $request;
    private $task;
    private $tasksModel;
    private $tasksRaport;
    private $currentTaskRaport;
    private $taskRaportResponse = [];

    public function __construct($request){

        $this->request = $request;
        $this->taskModel = new TaskModel();

    }

    public function validate($tasksRaport){
        
        foreach($tasksRaport as $this->currentTaskRaport){
            try{
                $this->saveTaskResult();
                $this->taskRaportResponse[] = [$this->currentTaskRaport->id => 'done'];
            }catch(ValidationException $e){
                $this->taskRaportResponse[] = [$this->currentTaskRaport->id => 'failed', 'error' => $e->getMessage()];
            }
        }

        return [
            'response' => $this->taskRaportResponse,
            'code' => 200,
        ];
    }

    private function saveTaskResult(){
        Logger::log(88, decodeHashId($this->currentTaskRaport->id), "THIS TASK");
        $this->task = $this->taskModel->get(decodeHashId($this->currentTaskRaport->id));

        $this->clientNotAllowed();

        $this->taskDoesntExist();

        $this->taskBelongsToOther();

        $this->taskAlreadyRaported();
        /*if($this->taskAlreadyRaported()){
            return;
        }*/

        $this->task->status = $this->currentTaskRaport->status;
        $this->task->done_at = date("Y-m-d H:i:s");
        $this->taskModel->save($this->task); 
        $this->setCellStatus();
        $this->insertPackage();
        $this->removePackage();
    }

    private function setCellStatus(){
        if($this->task->status != 'done')
            return;

        $cellModel = new CellModel();

        switch($this->task->task){
            case 'open-cell': $cellModel->setCellStatus($this->task, 'open'); break;
            case 'close-cell': $cellModel->setCellStatus($this->task, 'closed'); break;
        }
    }

    private function insertPackage(){
        if($this->task->status != 'done')
            return;

        if($this->task->type == 'package-insert' && $this->task->task == 'close-cell'){
            $packageModel = new PackageModel();
            $package = $packageModel->find($this->task->package_id);
            $package->recipient_code = $packageModel->generateRecipientCode(13);
            $package->cell_sort_id = $this->task->value;
            $package->inserted_at = date("Y-m-d H:i:s");
            $packageModel->save($package);
        }
    }

    private function removePackage(){
        if($this->task->status != 'done')
            return;

        if($this->task->type == 'package-remove' && $this->task->task == 'open-cell'){
            //Logger::log(0, '', 'Package removed', 'locker', $this->request->decodedJwt->clientId);
            $packageModel = new PackageModel();
            $package = $packageModel->find($this->task->package_id);
            $package->removed_at = date("Y-m-d H:i:s");
            $packageModel->save($package);
        }
    }

    private function clientNotAllowed(){

        //only locker clients allowed
        if($this->request->decodedJwt->client != 'locker'){
            throw new ValidationException('unauthorized client', ['status' => 'error', 'errors' => ['client'], 'message' => 'unauthorized client'], 403);
        }

    }

    private function taskDoesntExist(){
        
        if(!$this->task){
            throw new ValidationException('no task found', ['status' => 'error', 'errors' => ['id'], 'taskRaport' => $this->currentTaskRaport, 'message' => 'task doesnt exist'], 403);
        }

    }

    private function taskAlreadyRaported(){
        
        if($this->task->status){
            //return true;
            throw new ValidationException('task already raported', ['status' => 'error', 'errors' => ['id'], 'taskRaport' => $this->currentTaskRaport, 'message' => 'task already raported'], 403);
        }
        //return false;

    }

    private function taskBelongsToOther(){
   
        if($this->task->client_id != $this->request->decodedJwt->clientId){
            throw new ValidationException('unauthorized client', ['status' => 'error', 'errors' => ['id'], 'taskRaport' => $this->currentTaskRaport, 'message' => 'wrong client'], 403);
        }

    }
}