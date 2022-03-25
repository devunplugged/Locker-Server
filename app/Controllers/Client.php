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
            return $this->setResponseFormat('json')->fail(['type' => 'type is required'], 409, 123, 'Invalid Inputs');
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


        /*
        $jwtGenerator = new JwtGenerator($client['idHash']);
        $token = $jwtGenerator->generateToken();*/
        $token = JwtHandler::generateForClient($clientId);

        $response = ['status' => 200, 'message' => 'Client added Successfully', 'id' => hashId($clientId), 'token' => $token, 'details' => $detailModel->get($clientId)];

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

        $response = ['status' => 200, 'message' => 'Client updated Successfully', 'client' => hashId($client), 'details' => $detailModel->get(decodeHashId($this->request->getVar('id')))];

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
        $clients = $apiClientModel->getClients($data);
        return $this->setResponseFormat('json')->respond(['status' => 200, 'results' => hashId($clients['results']), 'count' => $clients['count'], 'post' => $data], 200);
    }

    public function get($clientIdHash)
    {
        $apiClientModel = new ApiClientModel();
        $detailModel = new DetailModel();

        $clientId = decodeHashId($clientIdHash);

        $client = $apiClientModel->get($clientId);

        //to do check if user is authorized to get the data

        if (!$client) {
            return $this->setResponseFormat('json')->fail(['generalErrors' => ['client' => 'not found']], 409, 123, 'Invalid Inputs');
        }

        $companyId = $client->company_id;

        $response = [
            'status' => 200,
            'client' => hashId($client),
            'details' => $detailModel->get($clientId),
            'company' => hashId($apiClientModel->get($companyId)),
            'companyDetails' => $detailModel->get($companyId),
        ];



        if ($client->type == 'company') {
            $response['workers'] = hashId($apiClientModel->getWorkers($clientId, true));
        }

        if ($client->type == 'locker') {
            $cellModel = new CellModel();
            $response['cells'] = hashId($cellModel->getLockerCellsAndPackages($clientId));
            //save result to file (cache) ??

            //this is used after locker creation when no cells have been added
            // if (!$response['cells']) {
            //     return $this->setResponseFormat('json')->fail(['generalErrors' => ['cells' => 'no cells found']], 409, 123, 'Invalid Inputs');
            // }

            $task = new Task($clientId);
            $response['tasks'] = $task->getForLocker(false);
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
            return $this->setResponseFormat('json')->respond(['status' => 200, 'result' => 'deleted'], 200);
        } else {
            return $this->setResponseFormat('json')->respond(['status' => 409, 'result' => 'failed'], 409);
        }
    }

    public function myAccount()
    {

        $apiClientModel = new ApiClientModel();
        $client = $apiClientModel->get($this->request->decodedJwt->clientId);

        if (!$client) {
            return $this->setResponseFormat('json')->respond(['status' => 404, 'error' => 'Invalid client'], 404);
        }

        $detailModel = new DetailModel();
        $details = $detailModel->get($client->id, true);
        return $this->setResponseFormat('json')->respond(['status' => 200, 'client' => hashId($client), 'details' => $details], 200);
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
