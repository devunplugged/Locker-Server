<?php
namespace App\Controllers;


use App\Models\ApiClientModel;
use CodeIgniter\API\ResponseTrait;

class Ajax extends BaseController
{
    use ResponseTrait;

    public function clientsByType(){
        $rules = [
            'type'    => ['rules' => 'required'],
        ];

        if(!$this->validate($rules)){
            $response = [
                'errors' => $this->validator->getErrors(),
            ];
            return $this->setResponseFormat('json')->fail($this->validator->getErrors(), 409, 123, 'Invalid Inputs');
        }

        $apiClientModel = new ApiClientModel();
        $clients = $apiClientModel->getByType($this->request->getVar('type'));

        return $this->respond(['clients' => $clients], 200);
    }

}
