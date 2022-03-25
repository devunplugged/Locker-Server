<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\API\ResponseTrait;
use App\Models\CellModel;
//use App\Models\LockerModel;
use CodeIgniter\Database\Exceptions\DataException;

class Cell extends BaseController
{
    use ResponseTrait;
    
    public function add()
    {
        $rules = [
            'size' => ['rules' => 'required|max_length[1]'],
            'locker_id' => ['rules' => 'required|max_length[255]|locker_exists'],
            'status' => ['rules' => 'permit_empty|max_length[255]'],
            'service' => ['rules' => 'permit_empty|max_length[1]'],
        ];

        if(!$this->validate($rules)){
            return $this->setResponseFormat('json')->fail($this->validator->getErrors(), 409, 123, 'Invalid Inputs');
        }

        $cellModel = new CellModel();
        $data = [
            'cell_sort_id' => $cellModel->getNextCellSortId(decodeHashId($this->request->getVar('locker_id'))),
            'size'    => $this->request->getVar('size'),
            'package_id' => null,
            'locker_id' => decodeHashId($this->request->getVar('locker_id')),
            'status' => 'closed',
            'package_inserted_at' => null,
        ];
        $cellModel->save($data);
            
        return $this->respond(['message' => 'Cell added Successfully'], 200);
    }

    public function update(){

        $rules = [
            'cell_sort_id' => ['rules' => 'required|max_length[255]'],
            'locker_id' => ['rules' => 'required|max_length[255]|locker_exists'],
            'size' => ['rules' => 'permit_empty|max_length[1]'],
            'status' => ['rules' => 'permit_empty|max_length[255]'],
            'service' => ['rules' => 'permit_empty|max_length[1]'],
            
        ];

        if(!$this->validate($rules)){
            return $this->setResponseFormat('json')->fail($this->validator->getErrors(), 409, 123, 'Invalid Inputs');
        }

        $cellModel = new CellModel();
        $cell = $cellModel->getCellByLockerAndSortId(decodeHashId($this->request->getVar('locker_id')), $this->request->getVar('cell_sort_id'));

        if($this->request->getVar('size')){
            $cell->size = $this->request->getVar('size');
        }

        if($this->request->getVar('status')){
            $cell->status = $this->request->getVar('status');
        }

        try{
            $result = $cellModel->save($cell);
            return $this->setResponseFormat('json')->respond(['status' => 200, 'result' => $result], 200);
        }catch(DataException $e){
            return $this->fail(['error'=>$e->getMessage()] , 409);
        }
        
    }

    public function list(){

        $rules = [
            'locker_id' => ['rules' => 'required|max_length[255]|locker_exists'],
        ];

        if(!$this->validate($rules)){
            return $this->setResponseFormat('json')->fail($this->validator->getErrors(), 409, 123, 'Invalid Inputs');
        }

        $cellModel = new CellModel();

        if( $this->request->getVar('locker_id') ){
            $clients = $cellModel->getByLocker(decodeHashId($this->request->getVar('locker_id')));
        }else{
            $clients = $cellModel->getAll();
        }

        return $this->setResponseFormat('json')->respond(['status' => 200, 'cells' => hashId($clients)], 200);

    }

    public function get(){

        $rules = [
            'cell_sort_id' => ['rules' => 'required|is_not_unique[cells.cell_sort_id]'],
            'locker_id' => ['rules' => 'required|is_not_unique_hash[cells.locker_id]'],
        ];

        if(!$this->validate($rules)){
            return $this->setResponseFormat('json')->fail($this->validator->getErrors(), 409, 123, 'Invalid Inputs');
        }

        $cellModel = new CellModel();
        $cell = $cellModel->getCellByLockerAndSortId(decodeHashId($this->request->getVar('locker_id')), $this->request->getVar('cell_sort_id'));

        if($cell){
            return $this->setResponseFormat('json')->respond(['status' => 200, 'cell' => hashId($cell)], 200);
        }else{
            //return $this->setResponseFormat('json')->respond(['status' => 404, 'cell' => 'no cell found'], 404);
            return $this->setResponseFormat('json')->fail(['cell' => 'no cell found'], 404, 123, 'Invalid Inputs');
        }
    }

    public function delete(){
        $rules = [
            'id' => ['rules' => 'required|is_not_unique[cells.cell_sort_id]'],
            'locker_id' => ['rules' => 'required|is_not_unique_hash[cells.locker_id]'],
        ];

        if(!$this->validate($rules)){
            return $this->setResponseFormat('json')->fail($this->validator->getErrors(), 409, 123, 'Invalid Inputs');
        }

        $cellModel = new CellModel();
        $result = $cellModel->deleteByLockerAndSortId(decodeHashId($this->request->getVar('locker_id')), $this->request->getVar('id'));

        return $this->setResponseFormat('json')->respond(['status' => 200, 'result' => $result], 200);
    }

    
}
