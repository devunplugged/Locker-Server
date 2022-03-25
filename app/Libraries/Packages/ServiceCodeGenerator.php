<?php
namespace App\Libraries\Packages;

use App\Exceptions\ValidationException;
use App\Models\ApiClientModel;
use App\Models\ServiceCodeModel;

class ServiceCodeGenerator{

    private $actions = ['reset-size-a', 'reset-size-b'];
    private $companyId;
    private $currentCode;
    private $currentAction;
    private $serviceCodeModel;

    public function __construct($companyId){

        $this->companyId = $companyId;
        $this->serviceCodeModel = new ServiceCodeModel();

    }

    public function generateCodes(){

        $this->validateCompanyId();

        foreach($this->actions as $this->currentAction){
            $this->generateCode();
            $this->persistCode();
        }

    }

    public function getCodesArray(){
        return $this->serviceCodeModel->getCompanyCodesArray($this->companyId);
    }

    private function generateCode(){

        $this->currentCode = md5($this->companyId . $this->currentAction . time());

    }

    private function persistCode(){

        $serviceCode = $this->serviceCodeModel->getCompanyActionCode($this->companyId, $this->currentAction);
        if($serviceCode){
            $this->updateCode($serviceCode);
        }else{
            $this->saveCode();
        }
       
    }

    private function saveCode(){

        $serviceCode = new \App\Entities\ServiceCode();
        $serviceCode->company_id = $this->companyId;
        $serviceCode->code = $this->currentCode;
        $serviceCode->action = $this->currentAction;
        $this->serviceCodeModel->save($serviceCode);

    }

    private function updateCode($serviceCode){

        $serviceCode->code = $this->currentCode;
        $this->serviceCodeModel->save($serviceCode);

    }

    private function validateCompanyId(){

        if(!$this->companyId){
            throw new ValidationException('Company id not found', 200, 404);
        }

        $apiClientModel = new ApiClientModel();
        $company = $apiClientModel->getCompany($this->companyId);

        if(!$company){
            throw new ValidationException('Company doesn\'t exist', 200, 404);
        }

    }
}