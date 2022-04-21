<?php

namespace App\Libraries\Packages;

use App\Libraries\Packages\Client;
use App\Libraries\Packages\Locker;
use App\Models\ApiClientModel;
use App\Libraries\Logger\Logger;

class ClientFactory
{
    public static function create(int $clientId)
    {
        //$client = new Client($clientId);
        $apiClientModel = new ApiClientModel();
        $client = $apiClientModel->get($clientId);

        if($client->type == 'locker'){
            return new Locker($clientId);
        }

        return new Client($clientId);
    }
}
