<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\API\ResponseTrait;
//use App\Libraries\Packages\JwtGenerator;
use App\Libraries\Packages\JwtHandler;
use App\Libraries\Packages\TokenIssuer;
use App\Libraries\Packages\Client;
use App\Models\ApiClientModel;

class Token extends BaseController
{
    use ResponseTrait;

    /**
     * Generates token for specified client.
     * Accessible to Company and Admin (companyFilter)
     */
    public function add()
    {
        $rules = [
            'client_id' => ['rules' => 'required|is_not_unique_hash[apiclients.id]'],
        ];

        if(!$this->validate($rules)){
            return $this->setResponseFormat('json')->fail(['generalErrors' => $this->validator->getErrors(), 'validationErrors' => $this->validator->getErrors()]  , 409);
        }

        $clientId = decodeHashId($this->request->getVar('client_id'));

        //$apiClientModel = new ApiClientModel();
        //$client = $apiClientModel->get($clientId);
        $client = new Client($clientId);

        if( 
            ( $this->request->decodedJwt->client == 'company' && $client->getClient()->type != 'staff' ) || 
            ( $this->request->decodedJwt->client == 'company' && $client->getClient()->type == 'staff' && $client->getClient()->company_id != $this->request->decodedJwt->companyId )
        ){
            return $this->setResponseFormat('json')->fail(['generalErrors' => ['company_id' => 'brak uprawnień do wykonania tej akcji']] , 409);
        }

        return $this->setResponseFormat('json')->respond(['token' => JwtHandler::generateForClient($clientId)], 200);
    }

    public function decode()
    {
        $rules = [
            'token' => ['rules' => 'required'],
        ];

        if(!$this->validate($rules)){
            $response = [
                'errors' => $this->validator->getErrors(),
                'message' => 'Invalid Inputs'
            ];
            return $this->setResponseFormat('json')->fail($response , 409);
        }

        return $this->setResponseFormat('json')->respond(['token' => JwtHandler::decode($this->request->getVar('token'))], 200);

    }

    public function getClientType(){
        $token = JwtHandler::extractFromHeader($this->request->getHeader("Authorization"));
        $decodedToken = JwtHandler::decode($token);
        return $this->setResponseFormat('json')->respond(['client' => $decodedToken->client], 200);
    }
    
    public function issueChange()
    {
        $rules = [
            'client_id' => ['rules' => 'required|client_exists[apiclients.id]'],
        ];

        if (!$this->validate($rules)) {
            return $this->setResponseFormat('json')->fail(['generalErrors' => $this->validator->getErrors()], 400);
        }

        $tokenIssuer = new TokenIssuer();
        $tokenIssuer->issueChange(decodeHashId($this->request->getVar('client_id')));

        return $this->setResponseFormat('json')->respond(['status' => 200, 'message' => 'done'], 200);
    }
}
