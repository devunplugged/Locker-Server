<?php
namespace App\Validation;

use App\Models\ApiClientModel;
use App\Libraries\Packages\Locker;
use App\Libraries\Logger\Logger;

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
        Logger::log(976, $id, 'has_locker_access 1 (locker id)');
        $locker = new Locker((int)$id);
        $request = service('request');
        Logger::log(976, $request->decodedJwt->companyId, 'has_locker_access 2 (company id)');
        if($locker->companyHasAccess($request->decodedJwt->companyId)){
            return true;
        }

        return false;
    }
}