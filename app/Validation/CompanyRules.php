<?php
namespace App\Validation;

use App\Models\ApiClientModel;
use App\Libraries\Logger\Logger;

class CompanyRules
{
    public function company_exists(string $id): bool
    {
        $apiClientModel = new ApiClientModel();
        Logger::log(90,decodeHashId($id),"company_exists" );
        $company = $apiClientModel->getCompany(decodeHashId($id));
        if($company){
            return true;
        }
        return false;
    }
/*
    public function company_exists_or_null(?string $hash): bool
    {
        if($hash === null)
            return true;

        return $this->company_exists($hash);
    }*/
}