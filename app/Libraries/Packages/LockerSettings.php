<?php
namespace App\Libraries\Packages;

use App\Models\DetailModel;

class LockerSettings{

    public static function get($lockerId){

        $detailModel = new DetailModel();
        $settings = new \stdClass();
        $settings->request_interval = $detailModel->getValue($lockerId, 'request_interval');
        return $settings;

    }
}