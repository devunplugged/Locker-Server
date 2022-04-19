<?php

namespace App\Libraries\Packages;

use App\Models\PackageModel;
use App\Models\PackageAddressModel;
use App\Models\PackageLogModel;
//use App\Models\EmailLogModel;
// use App\Models\ApiClientModel;
// use App\Models\DetailModel;
use App\Libraries\Logger\Logger;
use App\Libraries\Packages\Mailer;
use \chillerlan\QRCode\QRCode;
use \chillerlan\QRCode\QROptions;

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
            'ref_code' => $this->request->getVar('ref_code'),
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
        // $data = [
        //     'id'            => decodeHashId($this->request->getVar('id')),
        //     'size'          => $this->request->getVar('size'),
        //     'ref_code'          => $this->request->getVar('ref_code'),
        // ];
        $this->package = $this->packageModel->get(decodeHashId($this->request->getVar('id')));

        if(!$this->package){
            throw new \Exception('Paczka nie istnieje');
        }

        if(!$this->permissionCheck()){
            throw new \Exception('Brak uprawnień do edycji tej paczki');
        }

        if(!in_array($this->package->status, ['new'])){
            throw new \Exception('Edytować można tylko paczki ze statusem "nowa"');
        }

        $this->package->size = $this->request->getVar('size');
        $this->package->ref_code = $this->request->getVar('ref_code');

        $packageId = $this->packageModel->save($this->package);

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

    public function loadLockedFromLockerAndCellSortId($lockerId, $cellSortId)
    {
        $this->package = $this->packageModel->geLockedPackageForCell($lockerId, $cellSortId);
        return $this->exists();
    }
    /////////END Package loaders///////////////////

    public function resize($size)
    {
        $this->package->size = $size;

        $this->packageLogModel->create($this->package->id, "Rozmiar zmieniony na $size", $this->request->decodedJwt->clientId);
    }

    public function reset()
    {
        $this->package->status = 'new';
        $this->package->cell_sort_id = null;
        $this->package->enter_code_entered_at = null;
        $this->package->inserted_at = null;
        $this->package->removed_at = null;
        $this->package->canceled_at = null;
        $this->packageLogModel->create($this->package->id, "Paczka zresetowana", $this->request->decodedJwt->clientId);
    }

    public function resetPackage()
    {
        $this->reset();
        //save added; package would not reset when doors wont close
        $this->save();
        $this->sendResetEmailToSender();
        
    }

    public function resetAndSizeTo($size)
    {
        $this->reset();
        $this->resize($size);
        $this->save();

        $this->sendResetEmailToSender();
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
        //if canceled_at is set than set status to canceled; removed otherwise
        $this->package->status = $this->package->canceled_at ? 'canceled' : 'removed';
        $this->package->removed_at = date('Y-m-d H:i:s');
        $this->save();

        $this->sendRemovedEmailToRecipient();

        $this->packageLogModel->create($this->package->id, "Paczka odebrana", $this->request->decodedJwt->clientId);
    }

    public function makeLocked()
    {
        $this->package->status = 'locked';
        $this->save();

        $this->packageLogModel->create($this->package->id, "Paczka w utknęła w skrytce", $this->request->decodedJwt->clientId);
        $this->sendLockedEmailToRecipient();
        $this->sendLockedEmailToSender();
    }

    public function makeCanceled()
    {
        //set canceled status only if package is not in locker
        //if it is in locker set remove-ready status, canceled status is set on removal
        if(!in_array($this->package->status, ['in-locker','locked'])){
            $this->package->status = 'canceled';
        }else{
            $this->package->status = 'remove-ready';
        }
        $this->package->canceled_at = date("Y-m-d H:i:s");
        $this->save();

        $this->packageLogModel->create($this->package->id, "Paczka została anulowana", $this->request->decodedJwt->clientId);
        $this->sendCanceledEmailToRecipient();
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

    ////////////EMAILS///////////////

    public function sendInLockerEmailToRecipient($auto = true)
    {
        $packageAddress = $this->getAddress();
        $mailer = new Mailer(true);
        $mailer->addAddress($packageAddress['recipients_email']);
        $mailer->setSubject('Twoja paczka jest w paczkomacie');

        $body = '<h1>Twoja paczka od ' . $packageAddress['senders_name']. ' jest w paczkomacie</h1>';

        $local_name = time() . '.png';
        $imagePath = ROOTPATH . "writable/tmp/" . $local_name;
        (new QRCode(new QROptions([ 'imageTransparent'    => false ])))->render($this->package->recipient_code, $imagePath);
        $mailer->addEmbeddedImage($imagePath, 'qr-code', $local_name);

        $body .= '<p style="background:white;padding:10px;text-align:center;"><img alt="'.$this->package->recipient_code.'" src="cid:qr-code"></p>';
        $body .= '<div>Kod odbioru: '.$this->package->recipient_code.'</div>';

        // echo "<br>TO: " . $packageAddress['recipients_email'];
        // echo "<br>subject: " . 'Twoja paczka jest w paczkomacie';
        // echo "<br>body: ";
        // echo $body;
        $locker = new \App\Libraries\Packages\Locker($this->package->locker_id);
        $lockerDetails = $locker->getDetails();

        $body .= '<p>Adres paczkomatu: '.$lockerDetails['street'].'</p>';

        $mailer->setBody($body);
        $mailer->send();

        unlink($imagePath);
        Logger::log(661,'sendInLockerEmailToRecipient','email sent');
        Logger::log(111,'emailLog',$auto);
        Logger::emailLog($this->package->company_id, $packageAddress['recipients_email'], 'in-locker', $auto);
    }

    public function sendRemovedEmailToRecipient()
    {
        $packageAddress = $this->getAddress();
        $mailer = new Mailer(true);
        $mailer->addAddress($packageAddress['recipients_email']);
        $mailer->setSubject('Twoja paczka została wyjęta');

        $body = '<h1>Twoja paczka od ' . $packageAddress['senders_name']. ' została wyjęta z paczkomatu</h1>';


        $locker = new \App\Libraries\Packages\Locker($this->package->locker_id);
        $body .= '<p>Adres paczkomatu: '.$locker->getAddressString().' '.$locker->getPostcodeString().'</p>';

        $mailer->setBody($body);
        $mailer->send();

        Logger::log(661,'sendRemovedEmailToRecipient','email sent');
    }

    public function sendLockedEmailToRecipient()
    {
        $packageAddress = $this->getAddress();
        $mailer = new Mailer(true);
        $mailer->addAddress($packageAddress['recipients_email']);
        $mailer->setSubject('Twoja paczka została zatrzaśnięta w paczkomacie');

        $body = '<h1>Twoja paczka od ' . $packageAddress['senders_name']. ' jest zamknięta w paczkomacie</h1>';



        $body .= '<div>Drzwi paczkomatu nie chcą się otworzyć. Odezwiemy się do Ciebie jak tylko odzyskamy Twoją paczkę.</div>';

        $locker = new \App\Libraries\Packages\Locker($this->package->locker_id);
        $lockerDetails = $locker->getDetails();

        $body .= '<p>Adres paczkomatu: '.$lockerDetails['street'].'</p>';

        $mailer->setBody($body);
        $mailer->send();
        Logger::log(661,'sendLockedEmailToRecipient','email sent');
    }

    public function sendLockedEmailToSender()
    {
        $packageAddress = $this->getAddress();
        $mailer = new Mailer(true);
        $mailer->addAddress($packageAddress['recipients_email']);
        $mailer->setSubject('Twoja paczka została zatrzaśnięta w paczkomacie');

        $body = '<h1>Paczka od ' . $packageAddress['senders_name']. ' jest zamknięta w paczkomacie</h1>';



        $body .= '<div>Drzwi paczkomatu nie chcą się otworzyć.</div>';

        $locker = new \App\Libraries\Packages\Locker($this->package->locker_id);
        $lockerDetails = $locker->getDetails();

        $body .= '<p>Adres paczkomatu: '.$lockerDetails['street'].'</p>';

        $mailer->setBody($body);
        $mailer->send();
        Logger::log(661,'sendLockedEmailToSender','email sent');
    }

    public function sendCanceledEmailToRecipient()
    {
        $packageAddress = $this->getAddress();
        $mailer = new Mailer(true);
        $mailer->addAddress($packageAddress['recipients_email']);
        $mailer->setSubject('Twoja paczka została anulowana');

        $body = '<h1>Twoja paczka od ' . $packageAddress['senders_name']. ' została anulowana</h1>';
        $body .= '<div>Odezwiemy się do Ciebie jak paczka ruszy w dalszą drogę.</div>';

        $mailer->setBody($body);
        $mailer->send();
        Logger::log(661,'sendCanceledEmailToRecipient','email sent');
    }

    public function sendResetEmailToSender()
    {
        $packageAddress = $this->getAddress();
        $mailer = new Mailer(true);
        $mailer->addAddress($packageAddress['senders_email']);
        $mailer->setSubject('Paczka została zresetowana');

        $body = '<h1>Paczka ('.hashId($this->package->id).') do ' . $packageAddress['recipients_name']. ' ' . $packageAddress['recipients_phone']. 'została zresetowana</h1>';

        $body .= '<p>Paczka została zresetowana z powodu uszkodzenia skrytki. Możesz zeskanaować ją ponownie, aby umieścić ja w innej skrytce.</p>';

        $locker = new \App\Libraries\Packages\Locker($this->package->locker_id);
        $lockerDetails = $locker->getDetails();

        $body .= '<p>Adres paczkomatu: '.$lockerDetails['street'].'</p>';

        $mailer->setBody($body);
        $mailer->send();
        Logger::log(661,'sendResetEmailToSender','email sent');
        
    }

    
}
