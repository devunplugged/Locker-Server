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

class LockerRaport{
    private $lockerId;
    private $cells;
    private $currentCellSortId;
    private $currentCellStatus;
    private $cellsToOpenAgain = [];
    private $cellModel;
    private $packageModel;

    public function __construct($lockerId, $cells){
        $this->lockerId = $lockerId;
        $this->cells = $cells;
        $this->cellModel = new CellModel();
        $this->packageModel = new PackageModel();
    }

    public function ReadRaport(){
        $this->saveHeartbeat();

        foreach($this->cells as $cell){
            $this->currentCellSortId = $cell->id;
            $this->currentCellStatus = $cell->status;
            $this->setCellAndPackageStatus();
        }

        return $this->cellsToOpenAgain;
    }

    public function setCellAndPackageStatus(){
        
        $cell = $this->cellModel->getCellByLockerAndSortId($this->lockerId, $this->currentCellSortId);

        if(!$cell){
            return;
        }

        //skip out-of-order cells
        if($cell->status == 'out-of-order'){
            return;
        }

 //Logger::log(44, "$cell->status != $this->currentCellStatus", "setCellAndPackageStatus", 'locker', $this->lockerId);
        if($cell->status != $this->currentCellStatus){
            $this->cellStatusChanged($cell);
        }else{
            $this->cellStatusNotChanged($cell);
        }
        
    }

    private function cellStatusChanged($cell){
        $this->setCellStatus($cell);
        $this->setPackageStatus();
    }

    private function cellStatusNotChanged($cell){
        $packages = $this->packageModel->getInsertOrRemoveReadyPackagesForCell($this->lockerId, $cell->cell_sort_id);
        Logger::log(13, $packages, "cellStatusNotChanged $this->lockerId, $cell->cell_sort_id", 'locker', $this->lockerId);

        if(!$packages){
            return;
        }

        //if more than one package throw exception and save log
        if(count($packages) > 1){
            Logger::log(99, 'Więcej niż jedna paczka insert/remove-ready przypisana do skrytki ' . $cell->id, '', 'locker', $this->lockerId);
            throw new Exception('More than one package assigned to cell');
        }

        $this->manageFailedAttempt($cell, $packages[0]);

    }

    private function manageFailedAttempt($cell, $package){

        if($this->isCellOutOfOrder()){
            $this->cellOutOfOrder($cell, $package);
        }else{
            $this->createFailedTask($cell, $package);
        }
        
    }

    private function createFailedTask($cell, $package){
        

        if($package->status == 'insert-ready'){
            $this->createFailedInsertTask($cell, $package);

        }elseif($package->status == 'remove-ready'){
            $this->createFailedRemoveTask($cell, $package);
        }

    }

    private function createFailedInsertTask($cell, $package){
        $failedTaskDate = date('Y-m-d H:i:s', time() - TASK_FAIL_TIME);
        if($package->enter_code_entered_at < $failedTaskDate || $cell->status == 'closed'){
            $failedTaskModel = new FailedTaskModel();
            $failedTaskModel->create($this->lockerId, $cell->cell_sort_id);
            $this->cellsToOpenAgain[] = $cell->cell_sort_id;
        }
    }

    private function createFailedRemoveTask($cell, $package){
        $failedTaskDate = date('Y-m-d H:i:s', time() - TASK_FAIL_TIME);
        if($package->recipient_code_entered_at < $failedTaskDate || $cell->status == 'closed'){
            $failedTaskModel = new FailedTaskModel();
            $failedTaskModel->create($this->lockerId, $cell->cell_sort_id);
            $this->cellsToOpenAgain[] = $cell->cell_sort_id;
        }
    }

    private function isCellOutOfOrder(){
        $failedTaskModel = new FailedTaskModel();
        return $failedTaskModel->countTaskAttempts($this->lockerId, $this->currentCellSortId) > MAX_FAILED_TASKS;
    }

    private function cellOutOfOrder($cell, $package){
        
        

        switch($package->status){
            case 'insert-ready': $this->cellOutOfOrderInsertReadyPackage($package); break;
            case 'remove-ready': $this->cellOutOfOrderRemoveReadyPackage($package, $cell); break;
        }

        $cell->status = 'out-of-order';
        $this->cellModel->save($cell);

        Logger::log(99, 'Skrytka '.$cell->id.' ('.$cell->cell_sort_id.') uszkodzona', '', 'locker', $this->lockerId);
/*
        if($this->currentCellStatus == 'open'){
            //cell cant be closed
            Logger::log(99, 'Problem z zamknięciem skrytki ' . $cell->id .  ' (' . $cell->cell_sort_id . ')', '', 'locker', $this->lockerId);


        }elseif ($this->currentCellStatus == 'closed'){
            //cell cant be opened
            //TO DO: inform admin and customer about mulfunction
            Logger::log(99, 'Paczka wewnątrz zablokowanej skrytki ' . $cell->id .  ' (' . $cell->cell_sort_id . ')', '', 'locker', $this->lockerId);
        }*/
    }

    private function cellOutOfOrderInsertReadyPackage($package){
        $package->status = 'new';
        $package->cell_id = NULL;
        $this->packageModel->save($package);
        Logger::log(99, 'Skrytka i status paczki '.$package->id.' zresetowane ze wzgledu na uszkodzenie skrytki', '', 'package', $package->id);
    }

    private function cellOutOfOrderRemoveReadyPackage($package, $cell){
        Logger::log(99, 'cellOutOfOrderRemoveReadyPackage', '');
        if($cell->status == 'open'){
            Logger::log(99, 'cell status open', '');
            //to do: send notifications to staff and admin
        }elseif($cell->status == 'closed'){
            Logger::log(99, 'Nie da się wyciągnąć paczki '.$package->id.' ze skrytki ' . $cell->id, '', 'locker',  $this->lockerId);
            $package->status = 'locked';
            $this->packageModel->save($package);
            //to do: notify staff, admin, client
        }
    }

    private function setCellStatus($cell){

        
        $cell->status = $this->currentCellStatus;
        $this->cellModel->save($cell);

    }

    public function setPackageStatus(){
        
        if($this->currentCellStatus == 'open'){
            $this->setRemovePackageStatus();
        }elseif ($this->currentCellStatus == 'closed'){
            $this->setInsertPackageStatus();
        }
        
    }

    public function setRemovePackageStatus(){
        //$packageModel = new PackageModel();
        $package = $this->packageModel->getPackageToRemove($this->lockerId, $this->currentCellSortId);
        if(!$package){
            return;
        }
        $package->status = 'removed';
        $package->removed_at = date('Y-m-d H:i:s');
        $this->packageModel->save($package);

    }

    public function setInsertPackageStatus(){
        //$packageModel = new PackageModel();
        $package = $this->packageModel->getPackageToInsert($this->lockerId, $this->currentCellSortId);
        if(!$package){
            return;
        }
        $package->recipient_code = $this->packageModel->generateRecipientCode(13);
        $package->inserted_at = date('Y-m-d H:i:s');
        $package->status = 'in-locker';
        $this->packageModel->save($package);

    }

    private function saveHeartbeat(){
        $heartBeatModel = new HeartbeatModel();
        $heartBeatModel->saveBeat($this->lockerId);
    }
}