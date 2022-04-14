<?php

namespace App\Libraries\Packages;

use App\Models\PackageModel;
use App\Models\CellModel;
use App\Models\ApiClientModel;
use App\Models\HeartbeatModel;
use App\Models\FailedTaskModel;
use App\Models\LockerAccessModel;
use App\Models\TaskModel;
use App\Models\DetailModel;
use App\Libraries\Packages\Mailer;
use App\Libraries\Logger\Logger;

class Locker
{

    public $locker;
    public $details;
    public $emptyCells = null;

    private $heartBeatModel;
    private $apiClientModel;
    private $cellModel;
    private $packageModel;
    private $detailModel;
    private $failedTaskModel;

    public function __construct(int $lockerId)
    {

        $this->heartBeatModel = new HeartbeatModel();
        $this->apiClientModel = new ApiClientModel();
        $this->cellModel = new CellModel();
        $this->packageModel = new PackageModel();
        $this->failedTaskModel = new FailedTaskModel();
        $this->detailModel = new DetailModel();

        $this->locker = $this->apiClientModel->getLocker($lockerId);
        // $this->details = $this->detailModel->get($lockerId);
        //disabled; locker object has to be accesible to simple user; without company. To retrive the package
        // $request = service('request');
        // if(!$this->companyHasAccess($request->companyData->id)){
        //     throw new \Exception("Company locker access denied");
        // }
    }

    public function getDetails(bool $reload = false)
    {
        //Logger::log(46,$this->package->id);
        if (!$this->details || $reload) {
            $this->details = $this->detailModel->get($this->locker->id);
        }
        return $this->details;
    }

    public function getAddressString()
    {
        $lockerDetails = $this->getDetails();
        $lockerAddress = $lockerDetails['street'];
        if (isset($lockerDetails['building']) && !empty($lockerDetails['building'])) {
            $lockerAddress .= ' ' . $lockerDetails['building'];
        }
        if (isset($lockerDetails['apartment']) && !empty($lockerDetails['apartment'])) {
            $lockerAddress .= '/' . $lockerDetails['apartment'];
        }

        return $lockerAddress;
    }

    public function getPostcodeString()
    {
        $lockerDetails = $this->getDetails();
        $lockerPost = $lockerDetails['post_code'];
        if (isset($lockerDetails['city']) && !empty($lockerDetails['city'])) {
            $lockerPost .= ' ' . $lockerDetails['city'];
        }
        return $lockerPost;
    }

    public function getEmptyCells($size)
    {
        return $this->cellModel->getEmptyCells($this->locker->id, $size);
    }

    public function isNoEmptyCells($size)
    {
        if ($this->emptyCells === NULL) {
            $this->emptyCells = $this->getEmptyCells($size);
        }
        return count($this->emptyCells) === 0;
    }

    public function isBusyWithPackageOtherThan($packageId)
    {
        return $this->packageModel->insertOrRemoveReadyPackagesExistForLockerExcept($this->locker->id, $packageId);
    }

    public function isCellOutOfOrder($cellSortId)
    {
        //return $this->failedTaskModel->countTaskAttempts($this->locker->id, $cellSortId) > MAX_FAILED_TASKS;
        $taskModel = new TaskModel();
        return $taskModel->cellHasFailed($this->locker->id, $cellSortId);
    }

    public function getCellBySortId($cellSortId)
    {
        return $this->cellModel->getCellByLockerAndSortId($this->locker->id, $cellSortId);
    }

    public function saveCell($cell)
    {
        $this->cellModel->save($cell);
    }

    public function hasHeartbeat()
    {
        $beat = $this->heartBeatModel->get($this->locker->id);

        if (!$beat) {
            return false;
        }

        return $beat->last_call_at > date('Y-m-d H:i:s', time() - LOCKER_ACTIVE_TIME);
    }

    public function saveHeartBeat()
    {
        $this->heartBeatModel->create($this->locker->id);
    }

    public function companyHasAccess($companyId)
    {
        $lockerAccessModel = new LockerAccessModel();
        return $lockerAccessModel->companyHasAccess($companyId, $this->locker->id);
    }

    public function companySetAccess($companyId, $hasAccess)
    {
        $lockerAccessModel = new LockerAccessModel();
        return $lockerAccessModel->setAccess($companyId, $this->locker->id, $hasAccess);
    }

    ////////////EMAILS///////////////

    public function sendOutOfOrderOpenCellEmailNotification($cellSortId)
    {
        $mailer = new Mailer(true);
        $mailer->addAddress(NOTIFICATION_EMAIL);
        $mailer->setSubject('Drzwi paczkomatu są otwarte od dłuzszego czasu');


        $details = $this->getDetails();

        $body = '<h1>Drzwi paczkomatu są otwarte od dłuzszego czasu</h1>';

        $body .= '<p>Adres paczkomatu: ' . $details['street'] . '</p>';



        $mailer->setBody($body);
        $mailer->send();
        Logger::log(661, 'sendOutOfOrderOpenCellEmailNotification', 'email sent');
    }

    public function sendOutOfOrderClosedCellEmailNotification($cellSortId)
    {
        $mailer = new Mailer(true);
        $mailer->addAddress(NOTIFICATION_EMAIL);
        $mailer->setSubject('Drzwi paczkomatu nie chcą się otworzyć');


        $details = $this->getDetails();

        $body = '<h1>Drzwi paczkomatu nie chcą się otworzyć</h1>';

        $body .= '<p>Adres paczkomatu: ' . $details['street'] . '</p>';



        $mailer->setBody($body);
        $mailer->send();
        Logger::log(661, 'sendOutOfOrderClosedCellEmailNotification', 'email sent');
    }
}
