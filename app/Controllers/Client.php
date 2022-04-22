<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\API\ResponseTrait;

use App\Models\ApiClientModel;
use App\Models\DetailModel;
use App\Models\CellModel;
use App\Libraries\Packages\Task;
use App\Libraries\Packages\JwtHandler;
use App\Libraries\Packages\ClientValidationRules;
use App\Libraries\Logger\Logger;
use App\Libraries\Packages\ServiceCodeGenerator;

class Client extends BaseController
{
    use ResponseTrait;


    public function add()
    {

        if (!$this->request->getVar('type')) {
            return $this->setResponseFormat('json')->fail(['type' => $this->request->getVar('type'),'generalErrors' => ['type' => 'type is required']], 409, 123, 'Invalid Inputs');
        }

        if ($this->request->getVar('type') != 'staff' && $this->request->decodedJwt->client == 'company') {
            return $this->setResponseFormat('json')->fail(['type' => $this->request->getVar('type'),'generalErrors' => ['type' => 'Nie masz uprawnień do dodawania klienta tego typu']], 409, 123, 'Invalid Inputs');
        }

        $clientValidationRules = new ClientValidationRules();
        $rules = $clientValidationRules->getSaveRules($this->request->getVar('type'));

        if (!$this->validate($rules)) {
            return $this->setResponseFormat('json')->fail(['validationErrors' => $this->validator->getErrors()], 409, 123, 'Invalid Inputs');
        }

        $apiClientModel = new ApiClientModel();
        $detailModel = new DetailModel();

        $apiClient = new \App\Entities\ApiClient();


        $apiClient->company_id = $this->request->getVar('company_id') ? decodeHashId($this->request->getVar('company_id')) : null;
        $apiClient->name = $this->request->getVar('name');
        $apiClient->type = $this->request->getVar('type');
        $apiClient->active = 1;



        $clientId = $apiClientModel->create($apiClient);
        $detailModel->saveFromRequest($this->request, $clientId);


        
        //$token = JwtHandler::generateForClient($clientId);

        $response = ['status' => 200, 'message' => 'Poprawnie dodano klienta', 'id' => hashId($clientId), /*'token' => $token, */'details' => $detailModel->getDetails($clientId)];

        if ($apiClient->type == 'company') {
            $serviceCodeGenerator = new ServiceCodeGenerator($clientId);
            $serviceCodeGenerator->generateCodes();
            $response['codes'] = $serviceCodeGenerator->getCodesArray();
        }

        return $this->setResponseFormat('json')->respond($response, 200);
    }

    public function update()
    {

        if (!$this->request->getVar('type')) {
            return $this->setResponseFormat('json')->fail(['generalErrors' => ['type' => 'type is required']], 409, 123, 'Invalid Inputs');
        }

        if (
            $this->request->getVar('type') != 'staff' && 
            $this->request->decodedJwt->client == 'company' && 
            $this->request->decodedJwt->clientId != decodeHashId($this->request->getVar('id')) 
        ) {
            return $this->setResponseFormat('json')->fail(['type' => $this->request->getVar('type'),'generalErrors' => ['type' => 'Nie masz uprawnień do edycji klienta tego typu']], 409, 123, 'Invalid Inputs');
        }

        $clientValidationRules = new ClientValidationRules();
        $rules = $clientValidationRules->getUpdateRules($this->request->getVar('type'));


        if (!$this->validate($rules)) {
            return $this->setResponseFormat('json')->fail(['validationErrors' => $this->validator->getErrors()], 409, 123, 'Invalid Inputs');
        }

        $apiClientModel = new ApiClientModel();
        $detailModel = new DetailModel();

        //typ nie do zmiany
        // usuwanie pustych wartosci
        //aktualizowanie lub nowy zapis

        $client = $apiClientModel->get(decodeHashId($this->request->getVar('id')));
        $objectChanged = false;

        if ($this->request->getVar('company_id') && $client->company_id != $this->request->getVar('company_id')) {
            $client->company_id = decodeHashId($this->request->getVar('company_id'));
            $objectChanged = true;
        }

        if ($this->request->getVar('name') && $client->name != $this->request->getVar('name')) {
            $client->name = $this->request->getVar('name');
            $objectChanged = true;
        }

        if ($this->request->getVar('type') && $client->type != $this->request->getVar('type')) {
            $client->type = $this->request->getVar('type');
            $objectChanged = true;
        }

        if ($objectChanged) {
            $apiClientModel->save($client);
        }
        $detailModel->updateFromRequest($this->request, decodeHashId($this->request->getVar('id')));

        $response = ['status' => 200, 'message' => 'Klient zaktualizowany', 'client' => hashId($client), 'details' => $detailModel->getDetails(decodeHashId($this->request->getVar('id')))];

        if ($client->type == 'company' && $this->request->getVar('regenerate_servicecodes')) {
            $serviceCodeGenerator = new ServiceCodeGenerator($client->id);
            $serviceCodeGenerator->generateCodes();
            $response['codes'] = $serviceCodeGenerator->getCodesArray();
        }

        return $this->setResponseFormat('json')->respond($response, 200);
    }


    public function list()
    {
        $data['page'] = $this->request->getVar('page') ?? 1;
        $data['limit'] = $this->request->getVar('limit') ?? 20;
        $data['type'] = $this->request->getVar('type') ?? 'all';

        $apiClientModel = new ApiClientModel();

        if(in_array($this->request->decodedJwt->client, ['staff', 'company'])){
            $data['company'] = $this->request->decodedJwt->companyId;
        }

        $clients = $apiClientModel->getClients($data);
        return $this->setResponseFormat('json')->respond(['status' => 200, 'results' => hashId($clients['results']), 'count' => $clients['count'], 'post' => $data], 200);
    }

    public function get($clientIdHash)
    {
        // $apiClientModel = new ApiClientModel();
        // $detailModel = new DetailModel();

        $clientId = decodeHashId($clientIdHash);

       // $client = $apiClientModel->get($clientId);
        $client = \App\Libraries\Packages\ClientFactory::create($clientId);

        //to do check if user is authorized to get the data

        if (!$client->getClient()) {
            return $this->setResponseFormat('json')->fail(['generalErrors' => ['client' => 'not found']], 409, 123, 'Invalid Inputs');
        }

        if(!$client->clientCanView($this->request->decodedJwt->clientId)){
            return $this->setResponseFormat('json')->fail(['generalErrors' => ['uprawnienia' => 'brak uprawnień']], 409, 123, 'Invalid Inputs');
        }

        $company = new \App\Libraries\Packages\Client($client->getClient()->company_id);

        $response = [
            'status' => 200,
            'client' => hashId($client->getClient()),
            'details' => $client->getDetails(),
            'company' => hashId($company->getClient()),
            'companyDetails' => $company->getDetails(),
        ];

        if ($client->getClient()->type == 'company') {
            $response['workers'] = hashId($client->getWorkers(true));
        }

        if ($client->getClient()->type == 'locker') {
            $response['cells'] = hashId($client->getCellsAndPackages());
            $response['tasks'] = $client->getTasks(false);
        }

        return $this->setResponseFormat('json')->respond(
            $response,
            200
        );
    }

    public function delete()
    {

        $rules = [
            'id' => ['rules' => 'required|client_exists[apiclients.id_hash]'],
        ];

        if (!$this->validate($rules)) {
            return $this->setResponseFormat('json')->fail(['validationErrors' => $this->validator->getErrors()], 409, 123, 'Invalid Inputs');
        }

        $apiClientModel = new ApiClientModel();
        if ($apiClientModel->delete(decodeHashId($this->request->getVar('id')))) {
            return $this->setResponseFormat('json')->respond(['status' => 200, 'message' => 'Usunięto klienta', 'result' => 'deleted'], 200);
        } else {
            return $this->setResponseFormat('json')->respond(['status' => 409, 'result' => 'failed'], 409);
        }
    }

    public function myAccount()
    {
        $client = new \App\Libraries\Packages\Client($this->request->decodedJwt->clientId);

        if (!$client->getClient()) {
            return $this->setResponseFormat('json')->respond(['status' => 404, 'error' => 'Invalid client'], 404);
        }

        return $this->setResponseFormat('json')->respond(['status' => 200, 'client' => hashId($client->getClient()), 'details' => $client->getDetails()], 200);
    }

    public function getAccountTypeFromToken()
    {
        $token = JwtHandler::extractFromHeader($this->request->getHeader("Authorization"));
        helper('errorMsg');

        if (is_null($token) || empty($token)) {
            return $this->setResponseFormat('json')->respond(['status' => 200, 'type' => null], 200);
        }

        $decoded = JwtHandler::decode($token);
        return $this->setResponseFormat('json')->respond(['status' => 200, 'type' => $decoded->client], 200);
    }
}
