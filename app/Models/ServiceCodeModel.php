<?php

namespace App\Models;

use CodeIgniter\Model;

class ServiceCodeModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'servicecodes';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $insertID         = 0;
    protected $returnType       = \App\Entities\ServiceCode::class;
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['company_id', 'code', 'action', 'value'];

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


    public function isCompanyServiceCode($companyId, $code){
        $code = $this->getCompanyServiceCode($companyId, $code);
        if($code){
            return true;
        }
        return false;
    }

    public function getCompanyServiceCode($companyId, $code){
        return $this->where('company_id', $companyId)->where('code', $code)->first();
    }

    public function getCompanyActionCode($companyId, $action){
        return $this->where('company_id', $companyId)->where('action', $action)->first();
    }

    public function get($id){
        return $this->where('id', $id)->first();
    }

    public function getCompanyCodesArray($companyId){
        $codes = $this->where('company_id', $companyId)->findAll();
        $codesArray = [];
        foreach($codes as $code){
            $codesArray[$code->action] = $code->code; 
        }
        return $codesArray;
    }
}
