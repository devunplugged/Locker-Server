<?php
namespace App\Libraries\Packages;

use App\Models\TaskModel;
use App\Models\CellModel;
use App\Libraries\Packages\TaskDataFilter;
use App\Libraries\Packages\Package;
use App\Libraries\Logger\Logger;


class Task{
    private $lockerId;
    private $taskModel;

    public function __construct($lockerId){
        $this->lockerId = $lockerId;
        $this->taskModel = new TaskModel();
    }

    public function create($type, $value){

        if(!$this->taskModel->existsActive($this->lockerId, $type, $value)){
            $this->taskModel->create($this->lockerId, $type, $value);
        }
    }

    public function update($task, $data){

        foreach($data as $key => $value){
            if(property_exists($task, $key)){
                $task->$key = $value;
            }
        }
        $this->taskModel->save($task);
    }

    public function cellIsPendingOpen($cell_sort_id){
       // return $this->taskModel->hasPendingOpenSpecifiedCell($this->lockerId, $cell_sort_id);
        return $this->taskModel->existsActive($this->lockerId, 'open-cell', $cell_sort_id);
    }

    public function cellIsPendingOpenOrClose($cell_sort_id){
        return $this->taskModel->existsActive($this->lockerId, 'open-cell', $cell_sort_id) || $this->taskModel->existsActive($this->lockerId, 'close-cell', $cell_sort_id);
    }

    public function markDone(){

        $cellModel = new CellModel();
        $cells = $cellModel->getLockerCells($this->lockerId);

        //open/close tasks
        $tasks = $this->taskModel->getForLocker($this->lockerId);

        foreach($tasks as $task){

            if($task->type == 'open-cell' && $task->sent_at != NULL){ //wszystkie nie zakonczone 'open-cell' (done_at / failed_at NULL)
                Logger::log(881, 'open-cell', 'task', 'locker', $this->lockerId);
                foreach($cells as $cell){

                    if($cell->cell_sort_id == $task->value && $cell->status == 'open'){
                        Logger::log(881, 'status open', 'task', 'locker', $this->lockerId);
                        $this->taskModel->save(['id' => $task->id, 'done_at' => date('Y-m-d H:i:s')]);

                        $this->create('close-cell', $cell->cell_sort_id);

                        //nieudane proby sa zliczane po uplywie TASK_FAIL_TIME sekund, X nieudanych prob zablokuje skrytke w LockerRaport
                    }elseif($cell->cell_sort_id == $task->value && $cell->status != 'open' && $task->sent_at < date('Y-m-d H:i:s', time() - TASK_FAIL_TIME)){

                        $this->taskModel->save(['id' => $task->id, 'attempts' => $task->attempts+1]);

                    }/*elseif($cell->cell_sort_id == $task->value && $cell->status != 'open' && $task->sent_at >= date('Y-m-d H:i:s', time() - TASK_FAIL_TIME)){

                        $this->taskModel->save(['id' => $task->id, 'attempts' => $task->attempts+1]);

                    }elseif($cell->cell_sort_id == $task->value && $cell->status != 'open' && $task->sent_at < date('Y-m-d H:i:s', time() - TASK_FAIL_TIME)){

                        $this->taskModel->save(['id' => $task->id, 'failed_at' => date('Y-m-d H:i:s')]);

                    }*/
                }
            }elseif($task->type == 'close-cell'){ //wszystkie nie zakonczone 'close-cell' (done_at / failed_at NULL)
                
                foreach($cells as $cell){

                    if($cell->cell_sort_id == $task->value && $cell->status == 'closed'){

                        $this->taskModel->save(['id' => $task->id, 'done_at' => date('Y-m-d H:i:s')]);

                        //nieudane proby sa zliczane po uplywie TASK_FAIL_TIME sekund, X nieudanych prob zablokuje skrytke w LockerRaport
                    }elseif($cell->cell_sort_id == $task->value && $cell->status != 'closed' && $task->created_at < date('Y-m-d H:i:s', time() - TASK_FAIL_TIME)){
                        
                        $this->taskModel->save(['id' => $task->id, 'attempts' => $task->attempts+1]);

                    }/*elseif($cell->cell_sort_id == $task->value && $cell->status != 'closed' && $task->created_at >= date('Y-m-d H:i:s', time() - TASK_FAIL_TIME)){
                        
                        $this->taskModel->save(['id' => $task->id, 'attempts' => $task->attempts+1]);

                    }elseif($cell->cell_sort_id == $task->value && $cell->status != 'closed' && $task->created_at < date('Y-m-d H:i:s', time() - TASK_FAIL_TIME)){

                        $this->taskModel->save(['id' => $task->id, 'failed_at' => date('Y-m-d H:i:s')]);

                    }*/
                }
            }
        }
    }

    public function complete($cellStatus, $cellSortId){
        if($cellStatus == 'open'){
            $this->taskModel->complete('open-cell', $this->lockerId, $cellSortId);
        }elseif($cellStatus == 'closed'){
            $this->taskModel->complete('close-cell', $this->lockerId, $cellSortId);
        }
    }

    public function failUnfinished($cellSortId){
        Logger::log(661, 'failUnfinished', $cellSortId, 'locker', $this->lockerId);
        $this->taskModel->failUnfinished($this->lockerId, $cellSortId);
    }

    public function getForLocker($markSent = true){

        $this->markDone();

        //only open-cell tasks
        $tasks = $this->taskModel->getToExecuteByLocker($this->lockerId);

        if(!$tasks){
            return [];
        }

        

        if($markSent){
            foreach($tasks as $task){
                if(!$task->sent_at){
                    $task->sent_at = date('Y-m-d H:i:s');
                    $this->taskModel->save($task);
                }
            }
        }

        return TaskDataFilter::filter($tasks);
    }


}