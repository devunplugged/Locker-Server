<?php

namespace App\Models;

use CodeIgniter\Model;
use App\Models\CellModel;
use App\Libraries\Logger\Logger;

class LockerServiceCodeModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'lockerservicecodes';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $insertID         = 0;
    protected $returnType       = \App\Entities\LockerServiceCode::class;
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['locker_id', 'code', 'action', 'value'];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    // Validation
    protected $validationRules      = [];
    protected $validationMessages   = [];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert   = [];
    protected $afterInsert    = [];
    protected $beforeUpdate   = [];
    protected $afterUpdate    = [];
    protected $beforeFind     = [];
    protected $afterFind      = [];
    protected $beforeDelete   = [];
    protected $afterDelete    = [];

    public function create($lockerId, $code, $action, $value)
    {
        $lockerServiceCode = new \App\Entities\LockerServiceCode();
        $lockerServiceCode->locker_id = $lockerId;
        $lockerServiceCode->code = $code;
        $lockerServiceCode->action = $action;
        $lockerServiceCode->value = $value;
        if ($this->save($lockerServiceCode)) {
            return $this->getInsertID();
        }

        return false;
    }

    public function lockerCodeExists($code, $lockerId)
    {
        $codeEnt = $this->where('locker_id', $lockerId)->where('code', $code)->first();
        if ($codeEnt) {
            Logger::log(10, $code, 'exists', 'locker', $lockerId);
            return true;
        }
        Logger::log(10, $code, 'doesnt exist', 'locker', $lockerId);
        return false;
    }

    public function get($code)
    {
        return $this->where('code', $code)->first();
    }

    public function getForLocker($lockerId)
    {
        return $this->where('locker_id', $lockerId)->find();
    }

    public function codeExists($code)
    {
        $code = $this->where('code', $code)->first();
        if ($code) {
            return true;
        }
        return false;
    }

    public function generateForLockerCells($lockerId, $action)
    {
        $cellModel = new CellModel();
        $lockerCells = $cellModel->getByLocker($lockerId);
        $codes = [];

        $this->where('locker_id', $lockerId)->delete();

        foreach ($lockerCells as $lockerCell) {
            $code = $this->generateCode();
            $this->create($lockerId, $code, $action, $lockerCell->cell_sort_id);
            $codes[] = ['cell_sort_id' => $lockerCell->cell_sort_id, 'code' => $code];
        }

        return $codes;
    }


    public function generateCode()
    {
        $serviceCode = '';
        do {
            $serviceCode = 'lscode';
            $serviceCode .= $this->generateRandom();
        } while ($this->codeExists($serviceCode));

        return $serviceCode;
    }

    // public function generateRandom($length = 8)
    // {
    //     if (!isset($length) || intval($length) <= 8) {
    //         $length = 32;
    //     }
    //     return bin2hex(random_bytes($length));
    // }
    public function generateRandom($length = 8)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }
}
