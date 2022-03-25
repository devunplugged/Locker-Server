<?php
namespace App\Libraries\Packages;

use App\Models\FailedTaskModel;


class FailedTask{

    private $failedTaskModel;
    private $lockerId;

    public function __construct($lockerId){
        $this->failedTaskModel = new FailedTaskModel();
        $this->lockerId = $lockerId;
    }

    public function create($cellSortId){
        $this->failedTaskModel->create($this->lockerId, $cellSortId);
    }

    
}