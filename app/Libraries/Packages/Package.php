<?php

namespace App\Libraries\Packages;

use App\Models\PackageModel;
use App\Models\PackageAddressModel;
use App\Models\PackageLogModel;
use App\Libraries\Logger\Logger;

class Package
{

    public $package;
    private $packageModel;
    private $packageLogModel;
    private $packageAddressModel;
    private $request;

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

    public function getLog()
    {
        Logger::log(45,$this->package->id);
        return $this->packageLogModel->getPackageLog($this->package->id);
    }

    public function getAddress()
    {
        Logger::log(46,$this->package->id);
        return $this->packageAddressModel->get($this->package->id);
    }

    public function getCompany()
    {
        $apiClientModel = new \App\Models\ApiClientModel();
        $return = [];

        $return['company'] = $apiClientModel->getCompany($this->package->company_id);
        if($return['company']){
            $return['companyAddress'] = (new \App\Models\DetailModel())->get($return['company']->id);
        }
        return $return;
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
