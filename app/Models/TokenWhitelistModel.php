<?php

namespace App\Models;

use CodeIgniter\Model;
use App\Libraries\Logger\Logger;

class TokenWhitelistModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'tokenwhitelists';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $insertID         = 0;
    protected $returnType       = \App\Entities\TokenWhitelist::class;
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['client_id', 'description'];

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


    public function create($clientIdHash, $description = ''){

        $tokenWhitelist = new \App\Entities\TokenWhitelist();
        $tokenWhitelist->client_id = $clientIdHash;
        $tokenWhitelist->description = $description;
        $this->save($tokenWhitelist);
        return $this->getInsertID();

    }

    public function isOnWhitelist($tokenId){

        Logger::log(87, $tokenId, 'isOnWhitelist');
        if($this->find($tokenId)){
            Logger::log(87, "TRUE", 'isOnWhitelist');
            return true;
        }
        Logger::log(87, "FALSE", 'isOnWhitelist');
        return false;
    }
}
