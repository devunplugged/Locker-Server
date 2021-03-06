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
use App\Libraries\Packages\Client;
use App\Libraries\Packages\Mailer;
use App\Libraries\Packages\Task;
use App\Libraries\Logger\Logger;

class Locker extends Client
{

    public $emptyCells = null;

    private $heartBeatModel;
    private $cellModel;
    private $packageModel;
    private $task;
    //private $failedTaskModel;

    public function __construct(int $lockerId)
    {
        $this->clientId = $lockerId;
        $this->apiClientModel = new ApiClientModel();
        $this->detailModel = new DetailModel();

        $this->heartBeatModel = new HeartbeatModel();
        $this->cellModel = new CellModel();
        $this->packageModel = new PackageModel();
        //$this->failedTaskModel = new FailedTaskModel();
        
        

        $this->client = $this->apiClientModel->getLocker($lockerId);
        $this->task = new Task($lockerId);

        // $this->details = $this->detailModel->get($lockerId);
        //disabled; locker object has to be accesible to simple user; without company. To retrive the package
        // $request = service('request');
        // if(!$this->companyHasAccess($request->companyData->id)){
        //     throw new \Exception("Company locker access denied");
        // }
    }

    public function getEmptyCells($size)
    {
        return $this->cellModel->getEmptyCells($this->clientId, $size);
    }

    public function getCellsAndPackages()
    {
        return $this->cellModel->getLockerCellsAndPackages($this->clientId);
    }

    public function getTasks($markSent = true)
    {
        return $this->task->getForLocker($markSent);
    }

    public function getStatus()
    {
        return $this->cellModel->getLockerCellsStatus($this->clientId);
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
        return $this->packageModel->insertOrRemoveReadyPackagesExistForLockerExcept($this->client->id, $packageId);
    }

    public function isCellOutOfOrder($cellSortId)
    {
        //return $this->failedTaskModel->countTaskAttempts($this->client->id, $cellSortId) > MAX_FAILED_TASKS;
        $taskModel = new TaskModel();
        return $taskModel->cellHasFailed($this->client->id, $cellSortId);
    }

    public function getCellBySortId($cellSortId)
    {
        return $this->cellModel->getCellByLockerAndSortId($this->client->id, $cellSortId);
    }

    public function saveCell($cell)
    {
        $this->cellModel->save($cell);
    }

    public function hasHeartbeat()
    {
        $beat = $this->heartBeatModel->get($this->client->id);

        if (!$beat) {
            return false;
        }

        return $beat->last_call_at > date('Y-m-d H:i:s', time() - LOCKER_ACTIVE_TIME);
    }

    public function saveHeartBeat()
    {
        $this->heartBeatModel->create($this->client->id);
    }

    public function companyHasAccess($companyId)
    {
        $request = service('request');

        if($request->decodedJwt->client == 'admin'){
            return true;
        }

        $lockerAccessModel = new LockerAccessModel();
        return $lockerAccessModel->companyHasAccess($companyId, $this->clientId);
    }

    public function companySetAccess($companyId, $hasAccess)
    {
        $lockerAccessModel = new LockerAccessModel();
        return $lockerAccessModel->setAccess($companyId, $this->clientId, $hasAccess);
    }

    public function clientCanEdit($clientId)
    {
        $client = new Client($clientId);

        if($client->getClient()->type == 'admin'){
            return true;
        }

        return false;
    }

    public function clientCanView($clientId)
    {
        $client = new Client($clientId);

        if($client->getClient()->type == 'admin'){
            return true;
        }

        if(in_array($client->getClient()->type, ['company', 'staff'])){
            return $this->companyHasAccess($client->getClient()->company_id);
        }

        return false;
    }

    ////////////EMAILS///////////////

    public function sendOutOfOrderOpenCellEmailNotification($cellSortId)
    {
        $mailer = new Mailer(true);
        $mailer->addAddress(NOTIFICATION_EMAIL);
        $mailer->setSubject('Drzwi paczkomatu s?? otwarte od d??uzszego czasu');


        $details = $this->getDetails();

        $body = '<h1>Drzwi paczkomatu s?? otwarte od d??uzszego czasu</h1>';

        $body .= '<p>Adres paczkomatu: ' . $details['street'] . '</p>';



        $mailer->setBody($body);
        $mailer->send();
        Logger::log(661, 'sendOutOfOrderOpenCellEmailNotification', 'email sent');
        Logger::emailLog(null, NOTIFICATION_EMAIL, 'open-cell', null, true);
    }

    public function sendOutOfOrderClosedCellEmailNotification($cellSortId)
    {
        $mailer = new Mailer(true);
        $mailer->addAddress(NOTIFICATION_EMAIL);
        $mailer->setSubject('Drzwi paczkomatu nie chc?? si?? otworzy??');


        $details = $this->getDetails();

        $body = '<h1>Drzwi paczkomatu nie chc?? si?? otworzy??</h1>';

        $body .= '<p>Adres paczkomatu: ' . $details['street'] . '</p>';



        $mailer->setBody($body);
        $mailer->send();
        Logger::log(661, 'sendOutOfOrderClosedCellEmailNotification', 'email sent');
        Logger::emailLog(null, NOTIFICATION_EMAIL, 'locked-cell', null, true);
    }
}
