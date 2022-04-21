<?php

namespace App\Models;

use CodeIgniter\Model;
use App\Libraries\Logger\Logger;

class DetailModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'details';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $insertID         = 0;
    protected $returnType       = \App\Entities\Detail::class;
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['type', 'client_id', 'name', 'value'];

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

    protected $allowedDetails = ['name', 'first_name', 'sur_name', 'post_code', 'city', 'street', 'building', 'appartment', 'phone', 'email', 'geolocate', 'works_from', 'works_to', 'request_interval'];
    protected $allowedForPublic = ['street', 'city', 'post_code', 'geolocate'];

    public function saveFromRequest($request ,$clientId){
        
       // $allowedFields = ['first_name', 'sur_name', 'street', 'city', 'post_code', 'geolocate'];
        $detailModel = new DetailModel();

        foreach($this->allowedDetails as $field){
            if($request->getVar($field)){
                $detail = new \App\Entities\Detail();
                $detail->client_id = $clientId;
                $detail->name = $field;
                $detail->value = $request->getVar($field);
                $detailModel->save($detail);
            }
        }

    }

    public function updateFromRequest($request ,$clientId){
        
       // $allowedFields = ['first_name', 'sur_name', 'street', 'city', 'post_code', 'geolocate', 'works_from', 'works_to'];
        //$detailModel = new DetailModel();

        foreach($this->allowedDetails as $field){
            if($request->getVar($field) !== null){

                if($request->getVar($field) == ''){
                    $this->where('client_id', $clientId)->where('name', $field)->delete();
                }else{
                    $this->saveSingle($clientId, $field, $request->getVar($field));
                }

            }
        }

    }

    public function saveSingle($clientId, $field, $value){

        $detail = $this->where('client_id', $clientId)->where('name', $field)->first();

        if($detail){ //if exists update
            if($detail->value != $value){
                $detail->value = $value;
                $this->save($detail);
            }
        }else{//save new
            $detail = new \App\Entities\Detail();
            $detail->client_id = $clientId;
            $detail->name = $field;
            $detail->value = $value;
            $this->save($detail);
        }

        
    }

    public function getDetails($clientId, $forPublic = false){

        $details = $this->where('client_id', $clientId)->findAll();
        $detailsArray = [];

        foreach($details as $detail){
            if($forPublic && in_array($detail->name, $this->allowedForPublic)){
                $detailsArray[$detail->name] = $detail->value;
            }elseif(!$forPublic){
                $detailsArray[$detail->name] = $detail->value;
            }
        }

        return $detailsArray;
    }

    public function getValue($clientId, $detailName){
        $detail = $this->where('client_id', $clientId)->where('name', $detailName)->first();
        if($detail)
            return $detail->value;

        return null;
    }
}
