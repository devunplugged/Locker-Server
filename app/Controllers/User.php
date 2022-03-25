<?php

namespace App\Controllers;
 
use App\Controllers\BaseController;
use CodeIgniter\API\ResponseTrait;
use Myth\Auth\Models\UserModel;
 
class User extends BaseController
{
    use ResponseTrait;
     
    public function add(){
        $rules = [
            'name' => ['rules' => 'required|max_length[255]|is_unique[lockers.name]'],
            'type' => ['rules' => 'required|max_length[255]'],
        ];

        if($this->validate($rules)){
            $model = new LockerModel();
            $data = [
                'name'      => $this->request->getVar('name'),
                'type'      => 'user',
                'active'    => 1,
            ];
            $model->save($data);
             
            return $this->respond(['message' => 'Locker added Successfully'], 200);
        }else{
            $response = [
                'errors' => $this->validator->getErrors(),
                'message' => 'Invalid Inputs'
            ];
            return $this->fail($response , 409);
             
        }
    }
}
