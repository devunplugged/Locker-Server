<?php

namespace App\Models;

use CodeIgniter\Model;
use App\Libraries\Logger\Logger;

class HeartbeatModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'heartbeats';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $insertID         = 0;
    protected $returnType       = \App\Entities\Heartbeat::class;
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['client_id', 'last_call_at'];

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


    public function exists($clientId){
        $hb = $this->where('client_id', $clientId)->first();
        return !empty($hb);
    }

    public function get($clientId){
        return $this->where('client_id', $clientId)->first();
    }

    public function create($lockerId){
        $beat = $this->where('client_id', $lockerId)->first();

        if($beat){
            $this->updateCall($beat);
        }else{
            $this->newCall($lockerId);
        }
    }

    public function updateCall($beat){
        $now = date('Y-m-d H:i:s');
        
        if($beat->last_call_at == $now)
            return;

        $beat->last_call_at = date('Y-m-d H:i:s');
        $this->save($beat);
    }

    public function newCall($lockerId){
        $beat = new \App\Entities\Heartbeat();
        $beat->client_id = $lockerId;
        $beat->last_call_at = date('Y-m-d H:i:s');
        $this->save($beat);
    }

}
