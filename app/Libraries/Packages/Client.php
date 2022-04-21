<?php

namespace App\Libraries\Packages;

use App\Models\ApiClientModel;
use App\Models\LockerAccessModel;
use App\Models\TaskModel;
use App\Models\DetailModel;
use App\Libraries\Packages\Mailer;
use App\Libraries\Logger\Logger;

class Client
{
    private $apiClientModel;
    private $detailModel;

    public $client;
    public $details;
    public $workers;
    

    public function __construct(int $clientId)
    {
        $this->apiClientModel = new ApiClientModel();
        $this->detailModel = new DetailModel();
        $this->client = $this->apiClientModel->get($clientId);
    }

    public function getClient(bool $reload = false)
    {
        //Logger::log(46,$this->package->id);
        if (!$this->client || $reload) {
            $this->client = $this->apiClientModel->get($this->client->id);
        }
        return $this->client;
    }

    public function getDetails(bool $reload = false)
    {
        //Logger::log(46,$this->package->id);
        if (!$this->details || $reload) {
            $this->details = $this->detailModel->get($this->client->id);
        }
        return $this->details;
    }

    public function getAddressString()
    {
        $lockerDetails = $this->getDetails();
        $lockerAddress = $lockerDetails['street'];
        if (isset($lockerDetails['building']) && !empty($lockerDetails['building'])) {
            $lockerAddress .= ' ' . $lockerDetails['building'];
        }
        if (isset($lockerDetails['apartment']) && !empty($lockerDetails['apartment'])) {
            $lockerAddress .= '/' . $lockerDetails['apartment'];
        }

        return $lockerAddress;
    }

    public function getPostcodeString()
    {
        $lockerDetails = $this->getDetails();
        $lockerPost = $lockerDetails['post_code'];
        if (isset($lockerDetails['city']) && !empty($lockerDetails['city'])) {
            $lockerPost .= ' ' . $lockerDetails['city'];
        }
        return $lockerPost;
    }

    public function getWorkers($withPersonalData = false, $reload = false)
    {
        if($this->client->type != 'company'){
            return null;
        }

        if (!$this->workers || $reload) {
            $this->workers = $this->apiClientModel->getWorkers($this->client->id, $withPersonalData);
        }

        return $this->workers;
    }

    public function clientCanEdit($clientId)
    {
        $client = new Client($clientId);

        if($client->getClient()->type == 'admin'){
            return true;
        }

        switch($this->client->type){
            case 'company':
                    if(in_array($client->getClient()->type, ['company'])){
                        return $client->getClient()->company_id == $this->client->company_id;
                    }
                break;
            case 'staff':
                    if(in_array($client->getClient()->type, ['company'])){
                        return $client->getClient()->company_id == $this->client->company_id;
                    }
                break;
            case 'locker':
                    return false;
                break;
        }

        return false;
    }

    public function clientCanView($clientId)
    {
        $client = new Client($clientId);

        if($client->getClient()->type == 'admin'){
            return true;
        }

        switch($this->client->type){
            case 'company':
                    if(in_array($client->getClient()->type, ['company', 'staff'])){
                        return $client->getClient()->company_id == $this->client->company_id;
                    }
                break;
            case 'staff':
                    if(in_array($client->getClient()->type, ['company', 'staff'])){
                        return $client->getClient()->company_id == $this->client->company_id;
                    }
                break;
            case 'locker':
                    if(in_array($client->getClient()->type, ['company', 'staff'])){
                        return $client->getClient()->company_id == $this->client->company_id;
                    }
                break;
        }
        return false;
    }
}
