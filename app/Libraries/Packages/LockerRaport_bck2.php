<?php
namespace App\Libraries\Packages;

use App\Models\PackageModel;
use App\Models\TaskModel;
use App\Models\CellModel;
use App\Models\FailedTaskModel;
use App\Models\HeartbeatModel;

use App\Exceptions\ValidationException;
use App\Libraries\Logger\Logger;
use App\Libraries\Packages\TaskDataFilter;
use App\Libraries\Packages\Locker;
use App\Libraries\Packages\Package;
use App\Libraries\Packages\Task;
use App\Libraries\Packages\FailedTask;

class LockerRaport{
    private $lockerId;
    private $cells;
    private $currentRaportedCellSortId;
    private $currentRaportedCellStatus;
    private $cellsToOpenAgain = [];
    private $cellModel;
    private $packageModel;
    private $failedTask;
    private $locker;
    private $currentCell;
    private $package;

    public function __construct($lockerId, $cells){
        $this->lockerId = $lockerId;
        $this->cells = $cells;
        $this->cellModel = new CellModel();
        $this->packageModel = new PackageModel();
        $this->task = new Task($lockerId);
        $this->failedTask = new FailedTask($lockerId);
        $this->locker = new Locker($lockerId);
    }

    public function ReadRaport(){
        //$this->saveHeartbeat();
        $this->locker->saveHeartBeat();

        foreach($this->cells as $cell){
            $this->currentRaportedCellSortId = $cell->id;
            $this->currentRaportedCellStatus = $cell->status;
            $this->currentCell = $this->locker->getCellBySortId($this->currentRaportedCellSortId);
            $this->setCellAndPackageStatus();
        }

        return $this->cellsToOpenAgain;
    }

    public function setCellAndPackageStatus(){

        if(!$this->currentCell){
            return;
        }

        //skip out-of-order cells
        if($this->currentCell->status == 'out-of-order'){
            return;
        }

        if($this->currentCell->status != $this->currentRaportedCellStatus){
            $this->cellStatusChanged();
        }else{
            $this->cellStatusNotChanged();
        }
        
    }

    private function cellStatusChanged(){
        $this->setCellStatus();
        $this->setPackageStatus();
    }

    private function cellStatusNotChanged(){
        $packages = $this->packageModel->getInsertOrRemoveReadyPackagesForCell($this->lockerId, $this->currentCell->cell_sort_id);
        Logger::log(13, $packages, "cellStatusNotChanged ".$this->lockerId.", ".$this->currentCell->cell_sort_id, 'locker', $this->lockerId);

        if(!$packages){
            return;
        }

        //if more than one package throw exception and save log
        if(count($packages) > 1){
            Logger::log(99, 'Więcej niż jedna paczka insert/remove-ready przypisana do skrytki ' . $this->currentCell->id, '', 'locker', $this->lockerId);
            throw new Exception('More than one package assigned to cell');
        }

        $this->package = new Package($packages[0]->id);

        $this->manageFailedAttempt();

    }

    private function manageFailedAttempt(){

        if($this->locker->isCellOutOfOrder($this->currentRaportedCellSortId)){
            $this->cellOutOfOrder();
        }else{ //czy mozna to zastapic uzyciem Tasks ?
            $this->manageFailedTask();
        }
        
    }

    private function manageFailedTask(){

        switch($this->package->package->status){
            case 'insert-ready': $this->createFailedTask($this->package->package->enter_code_entered_at); break;
            case 'remove-ready': $this->createFailedTask($this->package->package->recipient_code_entered_at); break;
        }

    }

    private function createFailedTask($codeEnteredAt){
        $failedTaskDate = date('Y-m-d H:i:s', time() - TASK_FAIL_TIME);

        if($codeEnteredAt < $failedTaskDate || $this->currentCell->status == 'closed'){

            $this->failedTask->create($this->currentCell->cell_sort_id);
            if($this->currentCell->status == 'closed'){
                $this->task->create('open-cell', $this->currentCell->cell_sort_id);
            }
        }
    }

    private function cellOutOfOrder(){

        switch($this->package->package->status){
            case 'insert-ready': $this->cellOutOfOrderInsertReadyPackage(); break;
            case 'remove-ready': $this->cellOutOfOrderRemoveReadyPackage(); break;
        }

        $this->currentCell->status = 'out-of-order';
        $this->cellModel->save($this->currentCell);

        Logger::log(99, 'Skrytka '.$this->currentCell->id.' ('.$this->currentCell->cell_sort_id.') uszkodzona', '', 'locker', $this->lockerId);

    }

    private function cellOutOfOrderInsertReadyPackage(){

        $this->package->resetPackage();
        $this->package->save();

        Logger::log(99, 'Skrytka i status paczki '.$this->package->package->id.' zresetowane ze wzgledu na uszkodzenie skrytki', '', 'package', $this->package->package->id);
    }

    private function cellOutOfOrderRemoveReadyPackage(){
        Logger::log(99, 'cellOutOfOrderRemoveReadyPackage', '');
        if($this->currentCell->status == 'open'){
            Logger::log(99, 'cell status open', '');
            //to do: send notifications to staff and admin
        }elseif($this->currentCell->status == 'closed'){
            Logger::log(99, 'Nie da się wyciągnąć paczki '.$this->package->package->id.' ze skrytki ' . $this->currentCell->id, '', 'locker',  $this->lockerId);
            //$package->status = 'locked';
            //$this->packageModel->save($package);
            $this->package->makeLocked();
            //to do: notify staff, admin, client
        }
    }

    private function setCellStatus(){

        $this->currentCell->status = $this->currentRaportedCellStatus;
        $this->locker->saveCell($this->currentCell);

    }

    public function setPackageStatus(){
        
        if($this->currentRaportedCellStatus == 'open'){
            $this->setRemovePackageStatus();
        }elseif ($this->currentRaportedCellStatus == 'closed'){
            $this->setInsertPackageStatus();
        }
        
    }

    public function setRemovePackageStatus(){

        $package = new Package();
        if($package->loadToRemoveFromLockerAndCellSortId($this->lockerId, $this->currentRaportedCellSortId)){
            $package->makeRemoved();
        }

    }

    public function setInsertPackageStatus(){
        Logger::log(54,"setInsertPackageStatus","setInsertPackageStatus");
        $package = new Package();
        if($package->loadToInsertFromLockerAndCellSortId($this->lockerId, $this->currentRaportedCellSortId)){
            Logger::log(55,$package,"setInsertPackageStatus");
            $package->makeInLocker();
        }

    }

}