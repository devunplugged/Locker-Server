<?php
namespace App\Validation;

use App\Models\ApiClientModel;
use App\Libraries\Packages\Locker;

class PackageRules
{
    public function locker_exists(string $id): bool
    {
        $apiClientModel = new ApiClientModel();
        $locker = $apiClientModel->find(decodeHashId($id));
        if($locker){
            return true;
        }
        return false;
    }

    public function has_locker_access(string $id): bool
    {
        $locker = new Locker((int)$id);
        $request = service('request');
        
        if($locker->companyHasAccess($request->decodedJwt->companyId)){
            return true;
        }

        return false;
    }
}