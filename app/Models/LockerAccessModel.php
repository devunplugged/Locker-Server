<?php

namespace App\Models;

use CodeIgniter\Model;

class LockerAccessModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'lockeraccesses';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $insertID         = 0;
    protected $returnType       = \App\Entities\LockerAccess::class;
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['company_id', 'locker_id'];

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



    public function setAccess($companyId, $lockerId, $hasAccess)
    {
        $accessId = $this->companyHasAccess($companyId, $lockerId);
        if ($accessId) {
            if(!$hasAccess){
                //delete access
                $this->where('id', $accessId)->delete();
            }
        }elseif($hasAccess){
            //new access
            $access = new \App\Entities\LockerAccess();
            $access->company_id = $companyId;
            $access->locker_id = $lockerId;
            $this->save($access);
        }
    }

    public function companyHasAccess($companyId, $lockerId)
    {
        $access = $this->where('company_id', $companyId)->where('locker_id', $lockerId)->first();
        if (!$access) {
            return false;
        }
        return $access->id;
    }

}
