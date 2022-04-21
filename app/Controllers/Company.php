<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\API\ResponseTrait;
use App\Models\CompanyModel;
use App\Models\ApiClientModel;
use App\Models\LockerAccessModel;
use App\Libraries\Packages\ServiceCode;

class Company extends BaseController
{
    use ResponseTrait;
    /*
    public function add()
    {
        $rules = [
            'name' => ['rules' => 'required|max_length[255]|is_unique[companies.name]'],
            'address' => ['rules' => 'required|max_length[255]'],
            'city' => ['rules' => 'required|max_length[255]'],
        ];

        if(!$this->validate($rules)){
            return $this->setResponseFormat('json')->fail($this->validator->getErrors(), 409, 123, 'Invalid Inputs');
        }

        $companyModel = new CompanyModel();
        $data = [
            'name'     => $this->request->getVar('name'),
            'address'  => $this->request->getVar('address'),
            'city'     => $this->request->getVar('city'),
            'active'   => '1',
        ];
        $companyId = $companyModel->create($data);

        if(!$companyId){
            return $this->setResponseFormat('json')->fail('Failed to add company', 409, 123, 'Invalid Inputs');
        }

        $serviceCode = new ServiceCode($companyId);
        $serviceCode->generateCodes();
            
        return $this->setResponseFormat('json')->respond(['status' => 200, 'message' => 'Company added Successfully', 'codes' => $serviceCode->getCodesArray()], 200);
    }

    public function update(){

        $rules = [
            'id' => ['rules' => 'required|max_length[255]|is_not_unique[companies.id_hash]'],
            'name' => ['rules' => 'permit_empty|max_length[255]|is_unique[companies.name,id_hash,{id}]'],
            'address' => ['rules' => 'permit_empty|max_length[255]'],
            'city' => ['rules' => 'permit_empty|max_length[255]'],
            'active' => ['rules' => 'permit_empty|numeric|max_length[1]'],
        ];

        if(!$this->validate($rules)){
            return $this->setResponseFormat('json')->fail($this->validator->getErrors(), 409, 123, 'Invalid Inputs');
        }
        
        $companyModel = new CompanyModel();
        $company = $companyModel->getByHash($this->request->getVar('id'));

        if(!$company){
            return $this->setResponseFormat('json')->fail(['company' => 'doesn\' exist'], 409, 123, 'Invalid Inputs');
        }

        if($this->request->getVar('name')){
            $company->name = $this->request->getVar('name');
        }

        if($this->request->getVar('address')){
            $company->address = $this->request->getvar('address');
        }

        if($this->request->getVar('city')){
            $company->city = $this->request->getVar('city');
        }

        if($this->request->getVar('active')){
            $company->active = $this->request->getVar('active');
        }

        if($this->request->getVar('regenerate_service_codes')){
            $serviceCode = new ServiceCode($companyId);
            $serviceCode->generateCodes();
        }

        if($companyModel->save($company)){
            return $this->setResponseFormat('json')->respond(['status' => 200, 'result' => 'saved changes', 'codes' => $serviceCode->getCodesArray()], 200);
        }else{
            return $this->setResponseFormat('json')->respond(['status' => 409, 'result' => 'save failed'], 409);
        }
        
    }*/

    public function list()
    {

        $apiClientModel = new ApiClientModel();
        if($this->request->decodedJwt->client == 'admin'){
            $companies = $apiClientModel->getCompanies();
        }else{
            $companies = [];
            $companies[]= $apiClientModel->getCompany($this->request->decodedJwt->companyId);
        }
        return $this->setResponseFormat('json')->respond(['status' => 200, 'companies' => hashId($companies)], 200);
    }

    public function get($companyId)
    {
        $apiClientModel = new ApiClientModel();
        $company = $apiClientModel->get(decodeHashId($companyId));

        if ($company) {
            return $this->setResponseFormat('json')->respond(['status' => 200, 'client' => $company], 200);
        } else {
            return $this->setResponseFormat('json')->respond(['status' => 404, 'result' => 'no company found'], 404);
        }
    }

    public function delete()
    {

        $rules = [
            'id' => ['rules' => 'required|is_not_unique[companies.id_hash]'],
        ];

        if (!$this->validate($rules)) {
            return $this->setResponseFormat('json')->fail($this->validator->getErrors(), 409, 123, 'Invalid Inputs');
        }

        $companyModel = new CompanyModel();

        if ($companyModel->deleteByHash($this->request->getVar('id'))) {
            return $this->setResponseFormat('json')->respond(['status' => 200, 'result' => 'deleted'], 200);
        } else {
            return $this->setResponseFormat('json')->respond(['status' => 409, 'result' => 'failed'], 409);
        }
    }

    public function getLockerAccess($lockerId)
    {
        if (empty($lockerId)) {
            return $this->setResponseFormat('json')->fail(['id' => 'ID paczkomatu jest wymagane'], 409, 123, 'Invalid Inputs');
        }

        $apiClientModel = new ApiClientModel();
        $companiesAccess = $apiClientModel->getCompaniesLockerAccess(decodeHashId($lockerId));

        return $this->setResponseFormat('json')->respond(['status' => 200, 'result' => $companiesAccess], 200);
    }

    public function setLockerAccess($lockerId)
    {
        if (empty($lockerId)) {
            return $this->setResponseFormat('json')->fail(['id' => 'ID paczkomatu jest wymagane'], 409, 123, 'Invalid Inputs');
        }

        $rules = [
            'access' => ['rules' => 'required'],
        ];

        if (!$this->validate($rules)) {
            return $this->setResponseFormat('json')->fail($this->validator->getErrors(), 409, 123, 'Invalid Inputs');
        }

        foreach($this->request->getVar('access') as $companyId => $hasAccess){
            $lockerAccessModel = new LockerAccessModel();
            $lockerAccessModel->setAccess($companyId, decodeHashId($lockerId), $hasAccess);
        }

        $apiClientModel = new ApiClientModel();
        $companiesAccess = $apiClientModel->getCompaniesLockerAccess(decodeHashId($lockerId));

        return $this->setResponseFormat('json')->respond(['status' => 200, 'result' => $companiesAccess], 200);
    }
}
