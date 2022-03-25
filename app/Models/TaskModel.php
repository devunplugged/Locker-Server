<?php

namespace App\Models;

use CodeIgniter\Model;
use App\Libraries\Logger\Logger;

class TaskModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'tasks';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $insertID         = 0;
    protected $returnType       = \App\Entities\Task::class;
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['locker_id', 'type', 'value', 'attempts', 'done_at', 'failed_at', 'sent_at'];

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

    public function create($lockerId, $type, $value){
        $task = new \App\Entities\Task();
        $task->locker_id = $lockerId;
        $task->type = $type;
        $task->value = $value;
        return $this->save($task);
    }

    public function existsActive($lockerId, $type, $value){
        $task = $this->where('locker_id', $lockerId)->where('type', $type)->where('value', $value)->where('done_at', NULL)->where('failed_at', NULL)->first();
        if($task){
            return true;
        }
        return false;
    }

    public function getForLocker($lockerId){
        //return $this->where('locker_id', $lockerId)->where('sent_at', NULL)->orderBy('id','ASC')->findAll();
        return $this->where('locker_id', $lockerId)->where('done_at', NULL)->where('failed_at', NULL)->orderBy('id','ASC')->findAll();
    }

    public function getToExecuteByLocker($lockerId){
        //return $this->where('locker_id', $lockerId)->where('sent_at', NULL)->orderBy('id','ASC')->findAll();
        return $this->where('locker_id', $lockerId)->where('type!=', 'close-cell')->where('done_at', NULL)->where('failed_at', NULL)->orderBy('id','ASC')->findAll();
    }

    public function cellIsPendingOpen($lockerId, $cell_sort_id){
        //$tasks = $this->where('locker_id', $lockerId)->where('type', 'open-cell')->where('value', $cell_sort_id)->where('sent_at', NULL)->orderBy('id','ASC')->findAll();
        $tasks = $this->where('locker_id', $lockerId)->where('type', 'open-cell')->where('value', $cell_sort_id)->where('done_at', NULL)->where('failed_at', NULL)->orderBy('id','ASC')->findAll();

        if($tasks){
            return true;
        }
        return false;
    }

    public function cellHasFailed($lockerId, $cellSortId){

        $task = $this->where('locker_id', $lockerId)->where('value', $cellSortId)->where('done_at', NULL)->where('failed_at', NULL)->orderBy('id','DESC')->first();
        if($task){
            Logger::log(49, 'cellHasFailed', $cellSortId, 'YES', $lockerId);
            return $task->attempts > MAX_FAILED_TASKS;
        }else{ 
            Logger::log(49, 'cellHasFailed', $cellSortId, 'NO', $lockerId);
            return false;
        }
        /*
        $startDate = date('Y-m-d H:i:s', time() - FAILED_TASKS_INTERVAL);
        $tasks = $this->where('locker_id', $lockerId)->groupStart()->where('type', 'open-cell')->orWhere('type', 'close-cell')->groupEnd()->where('value', $cellSortId)->where('failed_at>=', $startDate)->findAll();

        $attempts = 0;
        foreach($tasks as $task){
            $attempts += $task->attempts;
        }

        return $attempts;*/
    }

    public function failUnfinished($lockerId, $cellSortId){
        $this->set('failed_at', date('Y-m-d H:i:s'))->where('locker_id', $lockerId)->where('value', $cellSortId)->where('done_at', NULL)->where('failed_at', NULL)->groupStart()->where('type', 'open-cell')->orWhere('type', 'close-cell')->groupEnd()->update();
    }

    public function complete($type, $lockerId, $cellSortId){
        $this->set('done_at', date('Y-m-d H:i:s'))->where('locker_id', $lockerId)->where('value', $cellSortId)->where('type', $type)->where('done_at', NULL)->where('failed_at', NULL)->update();
    }
}
