<?php

namespace App\Libraries\Packages;

use App\Models\PackageModel;
use App\Models\PackageAddressModel;
use App\Models\PackageLogModel;
use App\Libraries\Logger\Logger;
use App\Libraries\Packages\Mailer;
use \chillerlan\QRCode\QRCode;

class Package
{

    public $package;
    private $packageModel;
    private $packageLogModel;
    private $packageAddressModel;
    private $request;

    private $addresses;
    private $logs;
    private $company;

    public function __construct(?int $packageId = null)
    {

        $this->packageModel = new PackageModel();
        $this->packageLogModel = new PackageLogModel();
        $this->packageAddressModel = new PackageAddressModel();
        $this->request = service('request');

        if ($packageId != null) {
            $this->package = $this->packageModel->get($packageId);
        }
    }

    public function getLog(bool $reload = false)
    {
        //Logger::log(45,$this->package->id);
        if(!$this->logs || $reload){
            $this->logs = $this->packageLogModel->getPackageLog($this->package->id);
        }
        return $this->logs;
    }

    public function getAddress(bool $reload = false)
    {
        //Logger::log(46,$this->package->id);
        if(!$this->addresses || $reload){
            $this->addresses = $this->packageAddressModel->get($this->package->id);
        }
        return $this->addresses;
    }

    public function getCompany(bool $reload = false)
    {
        if(!$this->company || $reload){
            $this->company = [];
            $apiClientModel = new \App\Models\ApiClientModel();
            $this->company['company'] = $apiClientModel->getCompany($this->package->company_id);
            if($this->company['company']){
                $this->company['companyAddress'] = (new \App\Models\DetailModel())->get($this->company['company']->id);
            }
        }

        return $this->company;
    }

    public function createFromRequest()
    {
        $data = [
            'size'    => $this->request->getVar('size'),
            'status' => 'new',
            'locker_id' => decodeHashId($this->request->getVar('locker_id')),
            'cell_sort_id' => NULL,
            'company_id' => $this->request->companyData->id,
            'created_by' => $this->request->decodedJwt->clientId,
        ];

        $packageId = $this->packageModel->create($data);

        if (!$packageId) {
            //return $this->setResponseFormat('json')->fail(['errors' => ['package' => 'Błąd podczas tworzenia paczki']] , 409);
            throw new \Exception('Błąd podczas tworzenia paczki');
        }

        $packageAddressModel = new PackageAddressModel();
        $packageAddressModel->saveFromRequest($this->request, $packageId);

        $this->package = $this->packageModel->get($packageId);


        $this->packageLogModel->create($packageId, "Utworzono paczkę", $this->request->decodedJwt->clientId);

        return $packageId;
    }

    public function updateFromRequest()
    {
        $data = [
            'id'            => decodeHashId($this->request->getVar('id')),
            'size'          => $this->request->getVar('size'),
        ];

        $packageId = $this->packageModel->save($data);

        if (!$packageId) {
            //return $this->setResponseFormat('json')->fail(['errors' => ['package' => 'Błąd podczas tworzenia paczki']] , 409);
            throw new \Exception('Błąd podczas aktualizowania paczki');
        }

        $packageAddressModel = new PackageAddressModel();
        $packageAddressModel->saveFromRequest($this->request, $packageId);

        $this->package = $this->packageModel->get($packageId);


        $this->packageLogModel->create($packageId, "Aktualizowano paczkę", $this->request->decodedJwt->clientId);

        return $packageId;
    }

    /////////Package loaders///////////////////
    public function loadFromInsertCodeAndLocker($code, $lockerId)
    {
        $this->package = $this->packageModel->getPackageByInsertCodeAndLocker($code, $lockerId);
        return $this->exists();
    }

    public function loadActiveFromInsertCode($code)
    {
        $this->package = $this->packageModel->getActivePackageByInsertCode($code);
        return $this->exists();
    }

    public function loadActiveFromRecipientCodeAndLocker($code, $lockerId)
    {
        $this->package = $this->packageModel->getActivePackageByRecipientCodeAndLocker_2($code, $lockerId);
        return $this->exists();
    }

    public function loadActiveFromRecipientCode($code)
    {
        $this->package = $this->packageModel->getActivePackageByRecipientCode($code);
        return $this->exists();
    }

    public function loadToInsertFromLockerAndCellSortId($lockerId, $cellSortId)
    {
        $this->package = $this->packageModel->getPackageToInsert($lockerId, $cellSortId);
        return $this->exists();
    }

    public function loadToRemoveFromLockerAndCellSortId($lockerId, $cellSortId)
    {
        $this->package = $this->packageModel->getPackageToRemove($lockerId, $cellSortId);
        return $this->exists();
    }
    /////////END Package loaders///////////////////

    public function changeSizeTo($size)
    {
        $this->package->size = $size;

        $this->packageLogModel->create($this->package->id, "Rozmiar zmieniony na $size", $this->request->decodedJwt->clientId);
    }

    public function resetPackage()
    {
        $this->package->status = 'new';
        $this->package->cell_sort_id = null;
        $this->package->enter_code_entered_at = null;

        $this->packageLogModel->create($this->package->id, "Paczka usunięta z paczkomatu", $this->request->decodedJwt->clientId);
    }

    public function resetAndSizeTo($size)
    {
        $this->resetPackage();
        $this->changeSizeTo($size);
        $this->save();

        $this->packageLogModel->create($this->package->id, "Paczka w paczkomacie", $this->request->decodedJwt->clientId);
    }

    public function makeInsertReady($cellId)
    {
        $this->package->status = 'insert-ready';
        $this->package->cell_sort_id = $cellId;
        $this->package->enter_code_entered_at = date('Y-m-d H:i:s');
        $this->save();

        $this->packageLogModel->create($this->package->id, "Podano kod włożenia paczki", $this->request->decodedJwt->clientId);
    }

    public function makeRemoveReady()
    {
        $this->package->recipient_code_entered_at = date('Y-m-d H:i:s');
        $this->package->status = 'remove-ready';
        $this->save();

        //request JWT might be empty for package retrival
        $clientId = isset($this->request->decodedJwt->clientId) ? $this->request->decodedJwt->clientId : 0;

        $this->packageLogModel->create($this->package->id, "Podano kod wyjęcia paczki", $clientId);
    }

    public function makeInLocker()
    {
        $this->package->recipient_code = $this->packageModel->generateRecipientCode(13);
        $this->package->inserted_at = date('Y-m-d H:i:s');
        $this->package->status = 'in-locker';
        $this->save();

        $this->sendInLockerEmailToRecipient();

        $this->packageLogModel->create($this->package->id, "Paczka w paczkomacie", $this->request->decodedJwt->clientId);
    }

    public function makeRemoved()
    {
        $this->package->status = 'removed';
        $this->package->removed_at = date('Y-m-d H:i:s');
        $this->save();

        $this->packageLogModel->create($this->package->id, "Paczka odebrana", $this->request->decodedJwt->clientId);
    }

    public function makeLocked()
    {
        $this->package->status = 'locked';
        $this->save();

        $this->packageLogModel->create($this->package->id, "Paczka w utknęła w skrytce", $this->request->decodedJwt->clientId);
    }

    public function save()
    {
        $this->packageModel->save($this->package);
    }

    ////////////EMAILS///////////////

    public function sendInLockerEmailToRecipient()
    {
        $packageAddress = $this->getAddress();
        $mailer = new Mailer(true);
        $mailer->addAddress($packageAddress['recipients_email']);
        $mailer->setSubject('Twoja paczka jest w paczkomacie');

        $body = '<h1>Twoja paczka od ' . $packageAddress['senders_name']. ' jest w paczkomacie</h1>';

        $local_name = time() . '.png';
        $imagePath = ROOTPATH . "writable/tmp/" . $local_name;
        (new QRCode)->render($this->package->recipient_code, $imagePath);
        $mailer->addEmbeddedImage($imagePath, 'qr-code', $local_name);

        $body .= '<img alt="'.$this->package->recipient_code.'" src="cid:qr-code">';
        $body .= '<div>Kod odbioru: '.$this->package->recipient_code.'</div>';

        echo "<br>TO: " . $packageAddress['recipients_email'];
        echo "<br>subject: " . 'Twoja paczka jest w paczkomacie';
        echo "<br>body: ";
        echo $body;


        $mailer->setBody($body);
        $mailer->send();

        unlink($imagePath);
    }

    /////////VALIDATIONS//////////////
    public function isRemovedForGood()
    {
        return $this->package->removed_at < date("Y-m-d H:i:s", time() - PACKAGE_ADDITIONAL_ACTIVE_TIME);
    }

    public function isRemoved()
    {
        return $this->package->removed_at ? true : false;
    }

    public function isInLockerForGood()
    {
        return $this->package->inserted_at && $this->package->inserted_at < date("Y-m-d H:i:s", time() - PACKAGE_ADDITIONAL_ACTIVE_TIME);
    }

    public function isInLocker()
    {
        return $this->package->status == 'in-locker' || $this->package->status == 'remove-ready' || $this->package->status == 'locked';
    }

    public function isInsertReady()
    {
        return $this->package->status == 'insert-ready';
    }

    public function belongsToLocker($lockerId)
    {
        return $this->package->locker_id == $lockerId;
    }

    public function exists()
    {
        return is_object($this->package);
    }
    ////////END VALIDATIONS/////////////


    public function permissionCheck()
    {
        if ($this->request->decodedJwt->client == 'admin') {
            return true;
        }

        if ($this->request->decodedJwt->companyId) {
            if ($this->request->decodedJwt->companyId == $this->package->company_id) {
                return true;
            } else {
                return false;
            }
        }

        if ($this->request->decodedJwt->client == 'company' && $this->request->decodedJwt->clientId == $this->package->company_id) {
            return true;
        } else {
            return false;
        }

        // if($this->request->decodedJwt->client == 'locker' && $this->package->company_id == ){
        //     return true;
        // }else{
        //     return false;
        // }
    }
}
