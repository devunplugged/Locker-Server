<?php
namespace App\Libraries\Packages;

use CodeIgniter\HTTP\RequestInterface;

use App\Models\ApiClientModel;
use App\Models\CompanyModel;
use App\Models\CellModel;
use App\Models\PackageModel;
use App\Models\TaskModel;

use App\Libraries\Logger\Logger;

class JwtRequestData{
    private $request;

    public function __construct(RequestInterface $request)
    {
        $this->request = $request;
    }

    public function getData(){

        $this->getClientData();
        $this->getCompanyData();
        $this->getLockerData();

    }

    private function getClientData()
    {
        if(!isset($this->request->decodedJwt->clientId)){
            throw new \Exception('Token corrupted, please check your token (1)' . $this->request->decodedJwt->clientId);
        }

        $apiClientModel = new ApiClientModel();
        $this->request->clientData = $apiClientModel->find($this->request->decodedJwt->clientId);

        if($this->request->clientData === NULL){
            throw new \Exception('Token corrupted, please check your token (2)');
        }
    }

    private function getCompanyData()
    {
        if(!isset($this->request->decodedJwt->companyId)){
            throw new \Exception('Token corrupted, please check your token (3)');
        }

        $apiClientModel = new ApiClientModel();
        $this->request->companyData = $apiClientModel->get($this->request->decodedJwt->companyId);

        if(!isset($this->request->companyData->id) || !$this->request->companyData->id){
            throw new \Exception('Token corrupted, please check your token (4)' . json_encode($this->request->decodedJwt));
        }

    }

    private function getLockerData()
    {
        //That is not being used!
        //rethink this
        if($this->request->decodedJwt->client !== 'locker')
        {
            return;
        }

        $lockerData = [];

        $cellModel = new CellModel();
        $lockerData['status'] = $cellModel->getLockerCellsStatus($this->request->decodedJwt->clientId);
        $lockerData['cells'] = $cellModel->getLockerCells($this->request->decodedJwt->clientId);

        $packageModel = new PackageModel();
        $lockerData['packages'] = $packageModel->getLockerPackages($this->request->decodedJwt->clientId);

        //$taskModel = new TaskModel();
        //$lockerData['tasks'] = $taskModel->getLockerActiveTasks($this->request->decodedJwt->clientId);

        $this->request->lockerData = $lockerData;

    }
}