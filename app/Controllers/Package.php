<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\API\ResponseTrait;
use App\Models\PackageModel;
use App\Models\PackageAddressModel;
use App\Models\PackageLogModel;
use App\Models\EmailLogModel;
//use App\Libraries\Packages\Package;
use App\Libraries\Packages\Retriver;
use App\Libraries\Packages\Printer;
use App\Libraries\Packages\Locker;
use App\Libraries\Packages\UserRequest;
use App\Libraries\Packages\Task;

use CodeIgniter\Database\Exceptions\DataException;
use App\Libraries\Logger\Logger;
use App\Exceptions\ValidationException;

class Package extends BaseController
{
    use ResponseTrait;

    public function add()
    {
        $rules = [
            'locker_id' => [
                'rules' => 'required|max_length[255]|locker_exists|has_locker_access',
                'errors' => [
                    'required' => 'Pole paczkomat jest wymagane',
                    'locker_exists' => 'Nie znaleziono wybranego paczkomatu',
                    'has_locker_access' => 'Nie masz dostępu do tego paczkomatu',
                ]
            ],
            'size' => [
                'rules' => 'required|max_length[1]|has_cell_size[locker_id]',
                'errors' => [
                    'has_cell_size' => 'Wybrany paczkomat nie ma skrytek w tym rozmiarze',
                ]
            ],
            'ref_code' => [
                'rules' => 'required|max_length[255]',
                'errors' => [
                    'required' => 'Pole numer referencyjny jest wymagane',
                ]
            ],
            'senders_name' => [
                'rules' => 'required|max_length[255]',
                'errors' => [
                    'required' => 'Pole nazwa podmiotu jest wymagane'
                ]
            ],
            'senders_postcode' => [
                'rules' => 'required|max_length[6]|regex_match[/\d{2}-\d{3}/]',
                'errors' => [
                    'required' => 'Pole kod pocztowy nadawcy jest wymagane',
                    'max_length' => 'Zbyt długi kod pocztowy',
                    'regex_match' => 'Zły format kodu (00-000)',
                ]
            ],
            'senders_city' => [
                'rules' => 'required|max_length[255]',
                'errors' => [
                    'required' => 'Pole miasto nadawcy jest wymagane'
                ]
            ],
            'senders_street' => [
                'rules' => 'required|max_length[255]',
                'errors' => [
                    'required' => 'Pole ulica nadawcy jest wymagane'
                ]
            ],
            'senders_building' => [
                'rules' => 'required|max_length[255]',
                'errors' => [
                    'required' => 'Pole numer budynku nadawcy jest wymagane'
                ]
            ],
            'senders_apartment' => ['rules' => 'permit_empty|max_length[255]'],
            'senders_firstname' => ['rules' => 'permit_empty|max_length[255]'],
            'senders_surname' => ['rules' => 'permit_empty|max_length[255]'],
            'senders_phone' => [
                'rules' => 'required|max_length[255]',
                'errors' => [
                    'required' => 'Pole telefon nadawcy jest wymagane',
                ]
            ],
            'senders_email' => [
                'rules' => 'required|max_length[255]|valid_email',
                'errors' => [
                    'required' => 'Pole e-mail nadawcy jest wymagane',
                ]
            ],

            'recipients_name' => [
                'rules' => 'required|max_length[255]',
                'errors' => [
                    'required' => 'Pole nazwa odbiorcy jest wymagane',
                ]
            ],
            'recipients_postcode' => [
                'rules' => 'required|max_length[6]|regex_match[/\d{2}-\d{3}/]',
                'errors' => [
                    'required' => 'Pole kod pocztowy odbiorcy jest wymagane',
                    'max_length' => 'Zbyt długi kod pocztowy',
                    'regex_match' => 'Zły format kodu (00-000)',
                ]
            ],
            'recipients_city' => [
                'rules' => 'required|max_length[255]',
                'errors' => [
                    'required' => 'Pole miasto odbiorcy jest wymagane',
                ]
            ],
            'recipients_street' => [
                'rules' => 'required|max_length[255]',
                'errors' => [
                    'required' => 'Pole ulica odbiorcy jest wymagane',
                ]
            ],
            'recipients_building' => [
                'rules' => 'required|max_length[255]',
                'errors' => [
                    'required' => 'Pole numer budynku odbiorcy jest wymagane',
                ]
            ],
            'recipients_apartment' => ['rules' => 'permit_empty|max_length[255]'],
            'recipients_firstname' => ['rules' => 'permit_empty|max_length[255]'],
            'recipients_surname' => ['rules' => 'permit_empty|max_length[255]'],
            'recipients_phone' => [
                'rules' => 'required|max_length[255]',
                'errors' => [
                    'required' => 'Pole telefon odbiorcy jest wymagane',
                ]
            ],
            'recipients_email' => [
                'rules' => 'required|max_length[255]|valid_email',
                'errors' => [
                    'required' => 'Pole e-mail odbiorcy jest wymagane',
                ]
            ],

        ];

        if (!$this->validate($rules)) {
            return $this->setResponseFormat('json')->fail(['validationErrors' => $this->validator->getErrors()], 409, 123, 'Invalid Inputs');
        }

        try {
            $locker = new Locker(decodeHashId($this->request->getVar('locker_id')));
            if (!$locker->hasHeartbeat()) {
                return $this->setResponseFormat('json')->fail(['generalErrors' => ['locker_id' => 'Brak komunikacji z paczkomatem! (Wyslij raport/kod z paczkomatu, aby aktywowac paczkomat). This locker is not active at the moment. Please try again later or pick another locker.']], 409);
            }
        } catch (\Exception $e) {
            return $this->setResponseFormat('json')->fail(['generalErrors' => ['locker' => $e->getMessage()]], 409);
        }
        //$packageModel = new PackageModel();
        /*$data = [
            'size'    => $this->request->getVar('size'),
            //'recipient_email' => $this->request->getVar('recipient_email'),
            //'recipient_phone' => $this->request->getVar('recipient_phone'),
            'status' => 'new',
            'locker_id' => decodeHashId($this->request->getVar('locker_id')),
            'cell_sort_id' => NULL,
            'company_id' => $this->request->companyData->id,
            'created_by' => $this->request->decodedJwt->clientId,
        ];
        
        $packageId = $packageModel->create($data);

        if(!$packageId){
            return $this->setResponseFormat('json')->fail(['errors' => ['package' => 'Błąd podczas tworzenia paczki']] , 409);
        }

        $packageAddressModel = new PackageAddressModel();
        $packageAddressModel->saveFromRequest($this->request, $packageId);*/



        try {
            $package = new \App\Libraries\Packages\Package();
            $package->createFromRequest();
            return $this->setResponseFormat('json')->respond(['status' => '200', 'message' => 'Utworzono paczkę', 'package' => hashId($package->package)], 200);
        } catch (\Exception $e) {
            return $this->setResponseFormat('json')->fail(['generalErrors' => ['package' => $e->getMessage()]], 409);
        }
    }

    public function update()
    {
        $rules = [
            'id' => [
                'rules' => 'required|max_length[64]|is_not_unique_hash[packages.id]',
                'errors' => [
                    'is_not_unique_hash' => 'Nie znaleziono wybranej paczki',
                ]
            ],
            'size' => [
                'rules' => 'required|max_length[1]|has_cell_size[locker_id]',
                'errors' => [
                    'has_cell_size' => 'Wybrany paczkomat nie ma skrytek w tym rozmiarze',
                ]
            ],
            'ref_code' => [
                'rules' => 'required|max_length[255]',
                'errors' => [
                    'required' => 'Pole numer referencyjny jest wymagane',
                ]
            ],
            'status' => ['rules' => 'permit_empty|max_length[16]'],
            'locker_id' => [
                'rules' => 'permit_empty|max_length[64]|locker_exists|has_locker_access',
                'errors' => [
                    'locker_exists' => 'Nie znaleziono wybranego paczkomatu',
                    'has_locker_access' => 'Nie masz dostępu do tego paczkomatu',
                ]
            ],
            'cell_sort_id' => ['rules' => 'permit_empty|max_length[255]|numeric'],
            'company_id' => ['rules' => 'permit_empty|max_length[64]|is_not_unique_hash[companies.id]'],
            'created_by' => ['rules' => 'permit_empty|max_length[64]|is_not_unique_hash[apiclients.id]'],
            //'inserted_by' => ['rules' => 'permit_empty|max_length[255]|is_not_unique_hash[apiclients.id]'],
            //'insert_method' => ['rules' => 'permit_empty|max_length[64]'],
            //'remove_method' => ['rules' => 'permit_empty|max_length[64]'],
            //'insert_cancelled_by' => ['rules' => 'permit_empty|max_length[64]|is_not_unique_hash[apiclients.id]'],
            //'insert_cancelled_at' => ['rules' => 'permit_empty|max_length[24]'],

            'senders_name' => [
                'rules' => 'required|max_length[255]',
                'errors' => [
                    'required' => 'Pole nazwa podmiotu jest wymagane'
                ]
            ],
            'senders_postcode' => [
                'rules' => 'required|max_length[6]|regex_match[/\d{2}-\d{3}/]',
                'errors' => [
                    'required' => 'Pole kod pocztowy nadawcy jest wymagane',
                    'max_length' => 'Zbyt długi kod pocztowy',
                    'regex_match' => 'Zły format kodu (00-000)',
                ]
            ],
            'senders_city' => [
                'rules' => 'required|max_length[255]',
                'errors' => [
                    'required' => 'Pole miasto nadawcy jest wymagane'
                ]
            ],
            'senders_street' => [
                'rules' => 'required|max_length[255]',
                'errors' => [
                    'required' => 'Pole ulica nadawcy jest wymagane'
                ]
            ],
            'senders_building' => [
                'rules' => 'required|max_length[255]',
                'errors' => [
                    'required' => 'Pole numer budynku nadawcy jest wymagane'
                ]
            ],
            'senders_apartment' => ['rules' => 'permit_empty|max_length[255]'],
            'senders_firstname' => ['rules' => 'permit_empty|max_length[255]'],
            'senders_surname' => ['rules' => 'permit_empty|max_length[255]'],
            'senders_phone' => [
                'rules' => 'required|max_length[255]',
                'errors' => [
                    'required' => 'Pole telefon nadawcy jest wymagane',
                ]
            ],
            'senders_email' => [
                'rules' => 'required|max_length[255]|valid_email',
                'errors' => [
                    'required' => 'Pole e-mail nadawcy jest wymagane',
                ]
            ],

            'recipients_name' => [
                'rules' => 'required|max_length[255]',
                'errors' => [
                    'required' => 'Pole nazwa odbiorcy jest wymagane',
                ]
            ],
            'recipients_postcode' => [
                'rules' => 'required|max_length[6]|regex_match[/\d{2}-\d{3}/]',
                'errors' => [
                    'required' => 'Pole kod pocztowy odbiorcy jest wymagane',
                    'max_length' => 'Zbyt długi kod pocztowy',
                    'regex_match' => 'Zły format kodu (00-000)',
                ]
            ],
            'recipients_city' => [
                'rules' => 'required|max_length[255]',
                'errors' => [
                    'required' => 'Pole miasto odbiorcy jest wymagane',
                ]
            ],
            'recipients_street' => [
                'rules' => 'required|max_length[255]',
                'errors' => [
                    'required' => 'Pole ulica odbiorcy jest wymagane',
                ]
            ],
            'recipients_building' => [
                'rules' => 'required|max_length[255]',
                'errors' => [
                    'required' => 'Pole numer budynku odbiorcy jest wymagane',
                ]
            ],
            'recipients_apartment' => ['rules' => 'permit_empty|max_length[255]'],
            'recipients_firstname' => ['rules' => 'permit_empty|max_length[255]'],
            'recipients_surname' => ['rules' => 'permit_empty|max_length[255]'],
            'recipients_phone' => [
                'rules' => 'required|max_length[255]',
                'errors' => [
                    'required' => 'Pole telefon odbiorcy jest wymagane',
                ]
            ],
            'recipients_email' => [
                'rules' => 'required|max_length[255]|valid_email',
                'errors' => [
                    'required' => 'Pole e-mail odbiorcy jest wymagane',
                ]
            ],
        ];

        if (!$this->validate($rules)) {
            return $this->setResponseFormat('json')->fail(['validationErrors' => $this->validator->getErrors()], 409, 123, 'Invalid Inputs');
        }

        try {
            $package = new \App\Libraries\Packages\Package();
            $package->updateFromRequest();
            return $this->setResponseFormat('json')->respond(['status' => '200', 'message' => 'Zaktualizowano paczkę', 'package' => hashId($package->package)], 200);
        } catch (\Exception $e) {
            return $this->setResponseFormat('json')->fail(['generalErrors' => ['package' => $e->getMessage()]], 409);
        }
    }

    public function listOverdueLockerPackages()
    {
        $rules = [
            'locker_id' => ['rules' => 'required|max_length[255]|locker_exists'],
        ];

        if (!$this->validate($rules)) {

            return $this->setResponseFormat('json')->fail($this->validator->getErrors(), 409, 123);
        }

        $packageModel = new PackageModel();
        $packages = hashId($packageModel->getLockerOverduePackages(decodeHashId($this->request->getVar('locker_id'))));
        return $this->setResponseFormat('json')->respond(['status' => 200, 'packages' => $packages], 200);
    }

    public function list()
    {

        $data['page'] = $this->request->getVar('page') ?? 1;
        $data['limit'] = $this->request->getVar('limit') ?? 20;

        $packageModel = new PackageModel();

        if($this->request->decodedJwt->client != 'admin'){
            $data['company'] = $this->request->decodedJwt->companyId;
        }
        $packages = $packageModel->getPackages($data);

        return $this->setResponseFormat('json')->respond(['status' => 200, 'results' => hashId($packages['results']), 'count' => $packages['count'], 'post' => $data], 200);
    }

    public function cancelInsert()
    {
        $rules = [
            'package_id' => ['rules' => 'required|is_not_unique[packages.id_hash]'],
        ];

        if (!$this->validate($rules)) {
            $response = [
                'errors' => $this->validator->getErrors(),
                'message' => 'Invalid Inputs'
            ];
            return $this->setResponseFormat('json')->fail($response, 409);
        }

        $packageModel = new PackageModel();
        $packageModel->cancelInsertPackage($this->request->getVar('package_id'));
        return $this->setResponseFormat('json')->respond(['status' => 'ok'], 200);
    }

    public function companyPackageList()
    {
        $packageModel = new PackageModel();
        return $this->respond(['packages' => hashId($packageModel->getCompanyPackages($this->request->decodedJwt->companyId))], 200);
    }

    public function print($packageIdHash)
    {
        $packageId = decodeHashId($packageIdHash);
        //to do check user permissions for package
        $package = new \App\Libraries\Packages\Package($packageId);

        if (!$package->permissionCheck()) {
            return $this->setResponseFormat('json')->fail(['generalErrors' => ['permissions' => 'Brak dostępu do tej paczki']], 409, 123);
        }

        $printer = new Printer();
        $printer->setPackage($packageId);
        $printer->generatePackageDocument();
        $this->response->setHeader('Content-Type', 'application/pdf');
        $printer->output();
    }

    public function details($packageId)
    {

        $packageId = decodeHashId($packageId);
        $package = new \App\Libraries\Packages\Package($packageId);

        if (!$package->package) {
            return $this->setResponseFormat('json')->fail(['generalErrors' => ['package_id' => 'Nie znaleziono paczki']], 404, 123);
        }

        if (!$package->permissionCheck()) {
            return $this->setResponseFormat('json')->fail(['generalErrors' => ['package_id' => 'Brak dostępu do tej paczki']], 409, 123);
        }


        $locker = new \App\Libraries\Packages\Locker($package->package->locker_id);


        return $this->respond(
            [
                'package' => hashId($package->package),
                'logs' => hashId($package->getLog()),
                'address' => $package->getAddress(),
                'company' => hashId($package->getCompany()['company']),
                'companyAddress' => $package->getCompany()['companyAddress'],
                'lockerName' => $locker->getClient()->name,
                'lockerAddress' => $locker->getAddressString(),
                'lockerPostcode' => $locker->getPostcodeString(),
            ],
            200
        );
    }

    public function retrive()
    {

        $rules = [
            'code' => ['rules' => 'required'],
        ];

        if (!$this->validate($rules)) {
            return $this->setResponseFormat('json')->fail(['generalErrors' => $this->validator->getErrors()], 400);
        }

        try {
            $userRequest = new UserRequest($this->request);
            $userRequest->manageRequest();
            return $this->setResponseFormat('json')->respond(['status' => 200, 'package' => hashId($userRequest->getPackage())], 200);
        } catch (ValidationException $e) {
            return $this->setResponseFormat('json')->fail(['generalErrors' => ['error' => $e->getMessage()]], $e->getCode(), $e->getErrorCode());
        }
    }

    public function insert()
    {
        $rules = [
            'code' => ['rules' => 'required'],
        ];

        if (!$this->validate($rules)) {
            return $this->setResponseFormat('json')->fail(['generalErrors' => $this->validator->getErrors()], 400);
        }

        try {
            $userRequest = new UserRequest($this->request);
            $userRequest->manageRequest();
            return $this->setResponseFormat('json')->respond(['status' => 200, 'package' => hashId($userRequest->getPackage())], 200);
        } catch (ValidationException $e) {
            return $this->setResponseFormat('json')->fail(['generalErrors' => ['error' => $e->getMessage()]], $e->getCode(), $e->getErrorCode());
        }
    }

    public function cancel($packageId)
    {
        $packageId = decodeHashId($packageId);
        $package = new \App\Libraries\Packages\Package($packageId);

        if (!$package->package) {
            return $this->setResponseFormat('json')->fail(['generalErrors' => ['package_id' => 'Nie znaleziono paczki']], 404, 123);
        }

        if (!$package->permissionCheck()) {
            return $this->setResponseFormat('json')->fail(['generalErrors' => ['package_id' => 'Brak dostępu do tej paczki']], 409, 123);
        }
        
        if($package->package->canceled_at != null){
            return $this->setResponseFormat('json')->fail(['generalErrors' => ['package' => 'Paczka jest już anulowana']], 409, 123);
        }

        if (in_array($package->package->status, ['in-locker', 'locked'])) {
            $task = new Task($package->package->locker_id);
            $task->create('open-cell', $package->package->cell_sort_id);
        }

        $package->makeCanceled();
        return $this->setResponseFormat('json')->respond(['status' => 200, 'message' => 'Anulowano paczkę', 'package' => $package->package], 200);
    }

    public function reset($packageId)
    {
        $packageId = decodeHashId($packageId);
        $package = new \App\Libraries\Packages\Package($packageId);

        if (!$package->package) {
            return $this->setResponseFormat('json')->fail(['generalErrors' => ['package_id' => 'Nie znaleziono paczki']], 404, 123);
        }

        if (!$package->permissionCheck()) {
            return $this->setResponseFormat('json')->fail(['generalErrors' => ['package_id' => 'Brak dostępu do tej paczki']], 409, 123);
        }
        
        if(
            $package->package->status == 'new' && 
            $package->package->cell_sort_id == null && 
            $package->package->enter_code_entered_at == null &&
            $package->package->inserted_at == null &&
            $package->package->removed_at == null &&
            $package->package->canceled_at == null
        ){
            return $this->setResponseFormat('json')->fail(['generalErrors' => ['package' => 'Paczka jest już zresetowana']], 409, 123);
        }

        if (in_array($package->package->status, ['in-locker', 'locked'])) {
            $task = new Task($package->package->locker_id);
            $task->create('open-cell', $package->package->cell_sort_id);
        }


        $package->resetPackage();
        return $this->setResponseFormat('json')->respond(['status' => 200, 'message' => 'Zresetowano paczkę', 'package' => hashId($package->package)], 200);
    }

    public function emailRecipient($type, $packageId)
    {
        $allowedTypes = ['in-locker'];

        if(!in_array($type, $allowedTypes)){
            return $this->setResponseFormat('json')->fail(['generalErrors' => ['type' => 'Niedozwolony typ']], 409, 123);
        }

        $package = new \App\Libraries\Packages\Package(decodeHashId($packageId));

        if(!$package){
            return $this->setResponseFormat('json')->fail(['generalErrors' => ['type' => 'Nie znaleziono paczki']], 409, 123);
        }

        if(!$package->permissionCheck()){
            return $this->setResponseFormat('json')->fail(['generalErrors' => ['type' => 'Nie masz uprawnień do zarządzania tą paczką']], 409, 123);
        }

        $emailLogModel = new EmailLogModel();
        $count = $emailLogModel->countRecentManualOfTypeForPackage($type, $packageId);

        if($count > MANUAL_NOTIFICATIONS_MAX_COUNT){
            return $this->setResponseFormat('json')->fail(['generalErrors' => ['email' => 'Przekroczyłeś maksymalną liczbę wiadomości. Odczekaj chwilę.']], 409, 123);
        }

        if($package->package->status != 'in-locker'){
            return $this->setResponseFormat('json')->fail(['generalErrors' => ['package' => 'Paczka nie ma statusu "w-paczkomacie"']], 409, 123);
        }

        $package->sendInLockerEmailToRecipient(false);
        return $this->setResponseFormat('json')->respond(['status' => 200, 'message' => 'Wysłano powiadomienie'], 200);
    }
}
