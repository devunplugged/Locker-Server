<?php

namespace App\Models;

use CodeIgniter\Model;
use Hashids\Hashids;
use App\Libraries\Logger\Logger;

class CompanyModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'companies';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $insertID         = 0;
    protected $returnType       = \App\Entities\Company::class;
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['id_hash', 'name', 'address', 'city', 'active'];

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


    public function create($data){

        if($this->save($data)){
            return $this->getInsertID();
        }else{
            return false;
        }
        
    }

    public function getByHash($companyId){
        return $this->where('id_hash', $companyId)->first();
    }

    public function getAll(){
        return $this->findAll();
    }

    public function deleteByHash($companyIdHash){
        return $this->where('id_hash', $companyIdHash)->delete();
    }
}
