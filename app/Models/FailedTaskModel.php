<?php

namespace App\Models;

use CodeIgniter\Model;

class FailedTaskModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'failedtasks';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $insertID         = 0;
    protected $returnType       = \App\Entities\FailedTask::class;
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['locker_id', 'cell_id'];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    // Validation
    protected $validationRules      = [];
    protected $validationMessages   = [];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert   = [];
    protected $afterInsert    = [];
    protected $beforeUpdate   = [];
    protected $afterUpdate    = [];
    protected $beforeFind     = [];
    protected $afterFind      = [];
    protected $beforeDelete   = [];
    protected $afterDelete    = [];

    public function create($lockerId, $cellSortId){
        $failedTask = new \App\Entities\FailedTask();
        $failedTask->locker_id = $lockerId;
        $failedTask->cell_id = $cellSortId;
        $this->save($failedTask);
    }

    public function countTaskAttempts($lockerId, $cellSortId){
        $startDate = date('Y-m-d H:i:s', time() - FAILED_TASKS_INTERVAL);
        $attempts = $this->where('locker_id', $lockerId)->where('cell_id', $cellSortId)->where('created_at>=', $startDate)->findAll();

        return count($attempts);
    }
}
