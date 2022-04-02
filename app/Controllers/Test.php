<?php

namespace App\Controllers;
 
use App\Controllers\BaseController;
use App\Libraries\Packages\Package;
use CodeIgniter\API\ResponseTrait;
use Myth\Auth\Models\UserModel;
 
class Test extends BaseController
{
    public function testMail()
    {
        //gmail
        $package = new Package(14);
        //delta
        $package = new Package(30);



        $package->sendInLockerEmailToRecipient();

        //print_r($package);
    }
}
