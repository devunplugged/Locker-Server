<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\API\ResponseTrait;
//use App\Libraries\Packages\JwtGenerator;
use App\Libraries\Packages\JwtHandler;

class Token extends BaseController
{
    use ResponseTrait;

    public function add()
    {
        $rules = [
            'client_id' => ['rules' => 'required|is_not_unique_hash[apiclients.id]'],
        ];

        if(!$this->validate($rules)){
            $response = [
                'errors' => $this->validator->getErrors(),
                'message' => 'Invalid Inputs'
            ];
            return $this->setResponseFormat('json')->fail($response , 409);
        }

        return $this->setResponseFormat('json')->respond(['token' => JwtHandler::generateForClient(decodeHashId($this->request->getVar('client_id')))], 200);
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
}
