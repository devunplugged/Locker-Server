<?php
namespace App\Libraries\Packages;

use \App\Models\PackageModel;
use \App\Models\TaskModel;

class Retriver{
    private $request;

    public function __construct($request){
        $this->request = $request;
    }

    public function retrive(){

        if($this->request->decodedJwt->client != 'locker'){
            return [
                'response' => ['status' => 'error', 'errors' => ['client'], 'message' => 'unauthorized client'],
                'code' => 409,
            ];
        }

        

        $packageModel = new PackageModel();
        $package = $packageModel->getPackageByRecipientCodeAndLockerId($this->request->getVar('recipient_code'), $this->request->decodedJwt->clientId);

        if(!$package){
            return [
                'response' => ['status' => 'error', 'errors' => ['recipient_code', 'client_id'], 'message' => 'No package found'],
                'code' => 409,
            ];
        }

        $taskId = $this->createOpenCellTask($package[0]->cell_sort_id);

        return [
            'response' => ['status' => 'ok', 'task_id' => $taskId, 'type' => 'open-cell', 'value' => $package[0]->cell_sort_id],
            'code' => 200,
        ];
    }

    private function createOpenCellTask($cellId){

        $taskModel = new TaskModel();
        $data = [
            'type' => 'open-cell',
            'value' => $cellId,
        ];
        $taskModel->save($data);

        return $taskModel->getInsertID();

    }
}