<?php
namespace App\Validation;

use App\Models\ApiClientModel;


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
}