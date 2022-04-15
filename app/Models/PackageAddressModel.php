<?php

namespace App\Models;

use CodeIgniter\Model;
use App\Libraries\Logger\Logger;

class PackageAddressModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'packageaddresses';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $insertID         = 0;
    protected $returnType       = \App\Entities\PackageAddress::class;
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['package_id', 'name', 'value'];

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

    protected $allowedAddressFields = 
    [
        'senders_name', 'senders_postcode', 'senders_city', 'senders_street', 'senders_building', 'senders_apartment', 'senders_firstname', 'senders_surname', 'senders_phone', 'senders_email',
        'recipients_name', 'recipients_postcode', 'recipients_city', 'recipients_street', 'recipients_building', 'recipients_apartment', 'recipients_firstname', 'recipients_surname', 'recipients_phone', 'recipients_email',
    ];

    public function saveFromRequest($request ,$packageId){
        
        // $allowedFields = ['first_name', 'sur_name', 'street', 'city', 'post_code', 'geolocate'];
        // $detailModel = new DetailModel();

        foreach($this->allowedAddressFields as $field){
            if(!$request->getVar($field)){
                continue;
            }

            if($request->getVar($field) === ''){
                $this->where('package_id', $packageId)->where('name', $field)->delete();
                continue;
            }

            $this->saveSingle($packageId, $field, $request->getVar($field));

        }
 
    }

    public function saveSingle($packageId, $field, $value){

        $detail = $this->where('package_id', $packageId)->where('name', $field)->first();

        if($detail){ //if exists update
            if($detail->value != $value){
                $detail->value = $value;
                $this->save($detail);
            }
        }else{//save new
            $detail = new \App\Entities\Detail();
            $detail->package_id = $packageId;
            $detail->name = $field;
            $detail->value = $value;
            $this->save($detail);
        }

        
    }

    public function get($packageId){
        $details = $this->where('package_id', $packageId)->findAll();
        $detailsArray = [];

        foreach($details as $detail){
            $detailsArray[$detail->name] = $detail->value;
        }

        return $detailsArray;
    }

    public function getValue($packageId, $detailName){
        $detail = $this->where('package_id', $packageId)->where('name', $detailName)->first();
        if($detail)
            return $detail->value;

        return null;
    }
}
