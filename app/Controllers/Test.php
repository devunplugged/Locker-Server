<?php

namespace App\Controllers;
 
use App\Controllers\BaseController;
use App\Libraries\Packages\Package;
use CodeIgniter\API\ResponseTrait;
use App\Models\ApiClientModel;
 
class Test extends BaseController
{
    public function test()
    {
        $apiClientModel = new ApiClientModel();
        echo "<pre>";
        print_r($apiClientModel->getCompanyAccessLockers(2));
        echo "</pre>";
    }
}
