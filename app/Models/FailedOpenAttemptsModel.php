<?php

namespace App\Models;

use CodeIgniter\Model;


class FailedOpenAttemptsModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'failedopenattempts';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $insertID         = 0;
    protected $returnType       = \App\Entities\FailedOpenAttempts::class;
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['client_id'];

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

    public function create($clientId){
        
        $data = [
            'client_id' => $clientId,
        ];
        $this->save($data);
    }

    public function bruteForceDetected($clientId){
        
        $attempts = $this->where('client_id', $clientId)->where('created_at>', date('Y-m-d H:i:s', time() - LOCKER_OPEN_ATTEMPTS_TIME))->find();
        if(count($attempts) >= LOCKER_MAX_OPEN_ATTEMPTS)
            return true;
        return false;

    }
}
