<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\API\ResponseTrait;

use App\Models\ApiClientModel;
use App\Models\LockerModel;
use App\Models\CellModel;
use App\Models\PackageModel;
use App\Models\TaskModel;
use App\Models\FailedOpenAttemptsModel;
use App\Models\DetailModel;
use App\Models\DiagnosticModel;
use App\Models\LockerServiceCodeModel;

use App\Libraries\Packages\LockerRequest;
use App\Libraries\Packages\TaskRaport;
//use App\Libraries\Packages\Heartbeat;
use App\Libraries\Packages\LockerRaport;
use App\Libraries\Packages\LockerSettings;
use App\Libraries\Packages\Task;
use App\Libraries\Packages\LockerServiceCodePrinter;
use App\Libraries\Packages\TokenIssuer;
use App\Libraries\Logger\Logger;

use App\Exceptions\ValidationException;


class Locker extends BaseController
{
    use ResponseTrait;

    public function add()
    {
        $rules = [
            'company_id' => ['rules' => 'required|max_length[64]'],
            'name' => ['rules' => 'required|max_length[255]|is_unique[apiclients.name]'],
            'address' => ['rules' => 'required|max_length[255]'],
            'city'  => ['rules' => 'required'],
            'geolocate'  => ['rules' => 'required'],
        ];

        if (!$this->validate($rules)) {
            $response = [
                'errors' => $this->validator->getErrors(),
                'message' => 'Invalid Inputs'
            ];
            //return $this->fail($response , 409);
            return $this->setResponseFormat('json')->fail($this->validator->getErrors(), 409, 123, 'Invalid Inputs');
        }

        $apiClientModel = new ApiClientModel();
        $data = [
            'company_id' => $this->request->getVar('company_id'),
            'name'      => $this->request->getVar('name'),
            'type'      => 'locker',
            'address'   => $this->request->getVar('address'),
            'city'      => $this->request->getVar('city'),
            'geolocate' => $this->request->getVar('geolocate'),
            'active'    => 1,
        ];
        $apiClientModel->create($data);

        //return $this->respond(['message' => 'Locker added Successfully'], 200);
        return $this->setResponseFormat('json')->respond(['status' => 200, 'message' => 'Locker added Successfully'], 200);
    }

    public function companyLockerList()
    {
        $apiClientModel = new ApiClientModel;
        return $this->respond(['lockers' => $apiClientModel->getCompanyLockers($this->request->companyData->id_hash)], 200);
    }

    public function list()
    {
        $apiClientModel = new ApiClientModel;
        return $this->respond(['lockers' => hashId($apiClientModel->getLockers())], 200);
    }


    /*public function info($lockerId){
       
        $apiClientModel = new ApiClientModel;
        $cellModel = new CellModel;
        $packageModel = new PackageModel;

        $lockerInfo = [];
        $lockerId = decodeHashId($lockerId);
        $locker = $apiClientModel->getLocker($lockerId);
        

        if(!$locker){
            return $this->setResponseFormat('json')->fail(['locker_id' => 'no locker found'], 409, 123, 'Invalid Inputs');
        }

        

        $lockerInfo['locker'] = hashId();
        $lockerInfo['status'] = hashId($cellModel->getLockerCellsStatus($lockerId));
        $lockerInfo['cells'] = hashId($cellModel->getLockerCells($lockerId));

        
        $lockerInfo['packages'] = hashId($packageModel->getLockerPackages($lockerId));

        return $this->respond($lockerInfo, 200);
    }*/

    public function code()
    {

        //CHECK IF BRUTEFORCE
        $failedOpenAttemptsModel = new FailedOpenAttemptsModel();

        if ($failedOpenAttemptsModel->bruteForceDetected($this->request->decodedJwt->clientId)) {
            return $this->setResponseFormat('json')->fail('Too many failed attempts. Wait (' . LOCKER_OPEN_ATTEMPTS_TIME . 's) and try again', 409, 123);
        }

        //VALIDATE INPUTS
        $rules = [
            'code' => ['rules' => 'required'],
        ];

        //Logger::log(48, 'CODE1-5', $this->request->getVar('code'), 'locker', $this->request->decodedJwt->clientId);
        if (!$this->validate($rules)) {
            $response = [
                'status' => 'error',
                'errors' => $this->validator->getErrors(),
                'message' => 'Invalid Inputs',
            ];

            //Logger::log(48, 'NO-CODE-PROVIDED', $this->request->getVar('code'), 'locker', $this->request->decodedJwt->clientId);
            return $this->setResponseFormat('json')->fail($response, 400);
        }

        //Logger::log(33, $details, 'locker details', 'locker', $this->request->decodedJwt->clientId);

        try {
            $lockerRequest = new LockerRequest($this->request);
            $lockerRequest->manageRequest();

            //send response with tasks
            $task = new Task($this->request->decodedJwt->clientId);
            return $this->setResponseFormat('json')->respond(['status' => 200, 'tasks' => $task->getForLocker()], 200);
        } catch (ValidationException $e) {
            $failedOpenAttemptsModel->create($this->request->decodedJwt->clientId);
            return $this->setResponseFormat('json')->fail($e->getMessage(), $e->getCode(), $e->getErrorCode());
        }
    }

    public function task()
    {
        $taskModel = new TaskModel();
        $tasks = removeId($taskModel->clientTasks($this->request->decodedJwt->clientId));
        return $this->setResponseFormat('json')->respond($tasks, 200);
    }

    public function taskRaport()
    {

        $taskRaport = new TaskRaport($this->request);
        $result = $taskRaport->validate($this->request->getVar('tasks'));
        return $this->setResponseFormat('json')->respond($result, 200);

        /*
        $taskRaport = new TaskRaport($this->request);
        try{
            $result = $taskRaport->validate($this->request->getVar('tasks'));
            return $this->setResponseFormat('json')->respond($result, 200);
        }catch(ValidationException $e){
            return $this->setResponseFormat('json')->fail($e->getMessage(), $e->getCode(), $e->getErrorCode() );
        }
 */
    }

    /*    public function heartbeat(){
        new Heartbeat($this->request);

        $taskModel = new TaskModel();
        $tasks = [];//removeId($taskModel->getLockerActiveTasks($this->request->decodedJwt->clientId));

        $response = ['status' => 200];
        $response['tasks'] = $tasks;

        return $this->setResponseFormat('json')->respond($response, 200);
    }*/

    public function test()
    {
        $cellModel = new CellModel;
        $result = $cellModel->getEmptyCells($this->request->decodedJwt->clientId, 'a');

        return $this->setResponseFormat('json')->respond($result, 200);
    }

    public function raport()
    {
        $rules = [
            'locker_id' => ['rules' => 'required|max_length[64]|locker_exists'],
            //'cells' => ['rules' => 'required|cells_status']
        ];

        if (!$this->validate($rules)) {
            //return $this->setResponseFormat('json')->fail(, 409, 123, 'Invalid Inputs');
            return $this->setResponseFormat('json')->fail(['generalErrors' => $this->validator->getErrors()], 409);
        }

        $lockerId = decodeHashId($this->request->getVar('locker_id'));
        Logger::log(10, 'locker raport', $this->request->getVar('locker_id'), 'locker', $lockerId);
        //Logger::log(88, 'Diagnostics', $this->request->getVar('temperature') . ' ' . $this->request->getVar('humidity') . ' ' . $this->request->getVar('voltage'), 'locker', $lockerId );
        $lockerRaport = new LockerRaport($lockerId, $this->request->getVar('cells'));
        $lockerRaport->ReadRaport();

        $diagnosticModel = new DiagnosticModel();
        $diagnosticModel->save(
            [
                'locker_id' => $this->request->decodedJwt->clientId,
                'temperature' => $this->request->getVar('temperature'),
                'humidity' => $this->request->getVar('humidity'),
                'voltage' => $this->request->getVar('voltage')
            ]
        );

        Logger::log(10, 'GETTING TASKS', 'FOR', 'locker', $this->request->decodedJwt->clientId);
        $task = new Task($this->request->decodedJwt->clientId);

        $response = ['status' => 200, 'settings' => LockerSettings::get($lockerId), 'tasks' => $task->getForLocker()];

        $tokenIssuer = new TokenIssuer();
        $newToken = $tokenIssuer->checkToken();
        if($newToken){
            $response['token'] = $newToken;
        }

        return $this->setResponseFormat('json')->respond($response, 200);
    }

    public function get($id)
    {
        $lockerId = decodeHashId($id);

        $apiClientModel = new ApiClientModel();
        $locker = $apiClientModel->getLocker($lockerId);

        if (!$locker) {
            //return $this->setResponseFormat('json')->fail('Locker doesn\'t exist', 404, 404);
            return $this->setResponseFormat('json')->fail(['generalErrors' => ['id' => 'Locker doesn\'t exist']], 400);
        }

        $detailModel = new DetailModel();
        $details = $detailModel->get($lockerId, true);
        return $this->respond(['locker' => hashId($locker), 'details' => $details], 200);
    }

    public function info($id)
    {
        //not used anymore?
        //get locker data from user controller


        $lockerId = decodeHashId($id);

        $cellModel = new CellModel();
        $cellsAndPackages = $cellModel->getLockerCellsAndPackages($lockerId);
        //save result to file (cache) ??

        if (!$cellsAndPackages) {
            //return $this->setResponseFormat('json')->fail(['locker_id' => 'no locker found'], 409, 123, 'Invalid Inputs');
            return $this->setResponseFormat('json')->fail(['generalErrors' => ['id' => 'no locker found']], 404);
        }

        $task = new Task($lockerId);
        $tasks = $task->getForLocker(false);

        $apiClientModel = new ApiClientModel();
        $locker = $apiClientModel->getLocker($lockerId);
        $company = $apiClientModel->getCompany($locker->company_id);

        $detailModel = new DetailModel();
        $lockerDetails = $detailModel->get($locker->id, true);
        $companyDetails = $detailModel->get($locker->company_id, true);

        return $this->respond(
            [
                'info' => hashId($cellsAndPackages),
                'tasks' => $tasks,
                'locker' => hashId($locker),
                'lockerDetails' => $lockerDetails,
                'company' => hashId($company),
                'companyDetails' => $companyDetails
            ],
            200
        );
    }

    public function createOpenCellTask()
    {

        $rules = [
            'cell_id' => ['rules' => 'required|max_length[64]|cell_exists'],
            //'cells' => ['rules' => 'required|cells_status']
        ];

        if (!$this->validate($rules)) {
            return $this->setResponseFormat('json')->fail($this->validator->getErrors(), 409, 123, 'Invalid Inputs');
        }

        $cellModel = new CellModel();
        $cell = $cellModel->get(decodeHashId($this->request->getVar('cell_id')));

        //to do: czy ta osoba moze zarzadzac paczkomatem??

        $this->task = new Task($cell->locker_id);
        $this->task->create('open-cell', $cell->cell_sort_id);
        return $this->respond(['result' => 'success', 'cell' => hashId($cell)], 200);
    }


    public function generateLockerServiceCodes($lockerId)
    {
        $lockerId = decodeHashId(($lockerId));
        $apiClientModel = new ApiClientModel();
        $locker = $apiClientModel->getLocker($lockerId);

        if(!$locker){
            return $this->setResponseFormat('json')->fail(['generalErrors' => ['locker' => 'not found']], 409, 123, 'Invalid Inputs');
        }

        $lockerServiceCodeModel = new LockerServiceCodeModel();
        $codes = [];
        $codes['open-cell'] = $lockerServiceCodeModel->generateForLockerCells($lockerId, 'open-cell');
        
        return $this->setResponseFormat('json')->respond(['status' => 200, 'codes' => $codes], 200);
    }

    public function printLockerServiceCodes($lockerId)
    {
        $LockerServiceCodePrinter = new LockerServiceCodePrinter();
        $LockerServiceCodePrinter->setLocker(decodeHashId($lockerId));
        $LockerServiceCodePrinter->generateLockerCodesDocument();
        $this->response->setHeader('Content-Type', 'application/pdf');
        $LockerServiceCodePrinter->output();
    }
}
