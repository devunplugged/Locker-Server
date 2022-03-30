<?php

namespace App\Models;

use CodeIgniter\Model;
use App\Libraries\Packages\JwtHandler;

class TokenIssueModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'tokenissues';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $insertID         = 0;
    protected $returnType       = \App\Entities\TokenIssue::class;
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['client_id','old_token_id','new_token_id','new_token','status','old_token_uses'];

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

    public function create($clientId)
    {
        $newToken = JwtHandler::generateForClient($clientId);
        $decodedNewToken = JwtHandler::decode($newToken);

        $issue = new \App\Entities\TokenIssue();
        $issue->client_id = $clientId;
        $issue->new_token_id = $decodedNewToken->tokenId;
        $issue->new_token = $newToken;
        $this->save($issue);
    }

    public function hasNewTokenIssued($clientId)
    {
        $issue = $this->where('client_id', $clientId)->where('status', 'new')->first();
        if($issue){
            return $issue;
        }
        return false;
    }
}
