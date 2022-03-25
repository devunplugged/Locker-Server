<?php

namespace App\Models;

use CodeIgniter\Model;
use App\Libraries\Logger\Logger;

class ServiceCodeUseModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'servicecodeuses';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $insertID         = 0;
    protected $returnType       = \App\Entities\ServiceCodeUse::class;
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['locker_id', 'code_id', 'status'];

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

    public function codesToExecuteExist($lockerId){
        $codes = $this->where('locker_id', $lockerId)->where('status', 'execute')->orderBy('id', 'DESC')->find();
        if($codes){
            return true;
        }
        return false;
    }

    public function getToExecute($lockerId){
        return $this->where('locker_id', $lockerId)->where('status', 'execute')->orderBy('id', 'DESC')->first();
    }

    public function cancelLockerExecuteCodes($lockerId){
        $this->db->query('UPDATE servicecodeuses SET `status` = "canceled" WHERE locker_id = ? AND `status` = "execute"', [$lockerId]);
    }
}
