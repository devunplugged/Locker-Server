<?php
namespace App\Validation;

use App\Models\ApiClientModel;
use App\Models\CellModel;

class ClientRules
{
    public function allowed_client_type(string $str): bool
    {
        helper('clients');
        $allowedTypes = getAllowedClientTypes();
        return in_array($str, $allowedTypes);
    }

    public function allowed_client_type_or_null(?string $str): bool
    {
        if($str === null){
            return true;
        }
        return $this->allowed_client_type($str);
    }

    public function client_exists(string $str){
        $apiClientModel = new ApiClientModel();
        $company = $apiClientModel->get(decodeHashId($str));
        if($company){
            return true;
        }
        return false;
    }

    public function locker_exists(string $str){
        $apiClientModel = new ApiClientModel();
        $company = $apiClientModel->getLocker(decodeHashId($str));
        if($company){
            return true;
        }
        return false;
    }

    public function company_exists(string $id): bool
    {
        $apiClientModel = new ApiClientModel();
        $company = $apiClientModel->getCompany(decodeHashId($id));
        if($company){
            return true;
        }
        return false;
    }

    public function cell_exists(string $id):bool
    {
        $cellModel = new CellModel();
        if($cellModel->get(decodeHashId($id))){
            return true;
        }
        return false;
    }

    public function has_cell_size(string $cellSize, ?string $fields, array $data): bool
    {
        if(!isset($data[$fields])){
            return false;
        }
        $cellModel = new CellModel();
        return $cellModel->lockerHasCellSize($data[$fields], $cellSize);
    }

    public function can_set_type(string $type):bool
    {
        $request = service('request');

        switch($type){
            case 'admin':   return $request->clientData->type == 'admin'; break;
            case 'company': return $request->clientData->type == 'admin'; break;
            case 'staff':   return $request->clientData->type == 'admin' || $request->clientData->type == 'company'; break;
            case 'locker':  return $request->clientData->type == 'admin'; break;
        }

        return false;
    }
}