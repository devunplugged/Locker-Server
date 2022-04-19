<?php

namespace App\Models;

use CodeIgniter\Model;
use App\Libraries\Logger\Logger;

class PackageLogModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'packagelogs';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $insertID         = 0;
    protected $returnType       = \App\Entities\PackageLog::class;
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['package_id', 'content', 'created_by'];

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

    public function create($packageId, $content, $createdBy = NULL)
    {
        $packageLog = new \App\Entities\PackageLog();
        $packageLog->package_id = $packageId;
        $packageLog->content = $content;
        $packageLog->created_by = $createdBy;

        $this->save($packageLog);
        return $this->getInsertID();
    }

    public function getPackageLog($packageId)
    {
        return $this->where('package_id', $packageId)->findAll();
    }
}
