<?php

namespace App\Models;

use CodeIgniter\Model;
use Hashids\Hashids;

class ApiClientModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'apiclients';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $insertID         = 0;
    protected $returnType       = \App\Entities\ApiClient::class; //'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['id_hash', 'company_id', 'name', 'type', 'active'];

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


    public function getAll()
    {
        return $this->findAll();
    }

    public function get($id)
    {
        return $this->where('id', $id)->first();
    }

    public function getClients($data)
    {
        $offset = $data['limit'] * ($data['page'] - 1);
        $results = [];

        $query = $this;

        if(isset($data['company'])){
            $query = $query->where('company_id', $data['company']);
        }

        if($data['type'] != 'all'){
            $results['results'] = $query->where('type', $data['type']);
        }

        $results['results'] = $query->limit($data['limit'], $offset)->find();
        $results['count'] = $this->countAllResults();
        return $results;
    }

    public function getCompany($id)
    {
        return $this->where('id', $id)->where('type', 'company')->first();
    }

    public function getCompanies()
    {
        return $this->where('type', 'company')->findAll();
    }

    public function getLocker($id)
    {
        return $this->where('id', $id)->where('type', 'locker')->first();
    }

    public function getLockers()
    {
        return $this->where('type', 'locker')->findAll();
    }

    public function getWorkers($companyId, $withPersonalData = false)
    {

        if(!$withPersonalData){
            return $this->where('company_id', $companyId)->where('type', 'staff')->findAll();
        }

        $sql = "SELECT apiclients.*, d1.value AS first_name, d2.value AS sur_name FROM apiclients LEFT JOIN details d1 ON apiclients.id=d1.client_id AND d1.name='first_name' LEFT JOIN details d2 ON apiclients.id=d2.client_id AND d2.name='sur_name' WHERE apiclients.type = 'staff' AND apiclients.company_id = ?";
        $query = $this->db->query($sql, [$companyId]);
        $workers = $query->getResult();

        //all results are copied to apiClient object for consistency when returning api objects
        $workersWithPersonalData = [];
        foreach($workers as $worker){
            $client = new \App\Entities\ApiClient();
            $client->id = $worker->id;
            $client->company_id = $worker->company_id;
            $client->name = $worker->name;
            $client->type = $worker->type;
            $client->active = $worker->active;
            $client->created_at = $worker->created_at;
            $client->updated_at = $worker->updated_at;
            $client->first_name = $worker->first_name;
            $client->sur_name = $worker->sur_name;
            $workersWithPersonalData[] = $client;
        }
        return $workersWithPersonalData;
    }

    public function getByType($type)
    {
        return $this->where('type', $type)->findAll();
    }

    public function getByHash($IdHash)
    {
        return $this->where('id_hash', $IdHash)->first();
    }
    /*
    public function getLocker($lockerId){
        return $this->where('id', $lockerId)->find();
    }*/

    public function getLockerByHash($lockerIdHash)
    {
        return $this->where('id_hash', $lockerIdHash)->where('type', 'locker')->first();
    }

    public function getCompanyLockers($companyId)
    {
        return $this->where('company_id', $companyId)->where('type', 'locker')->find();
    }

    public function getCompanyAccessLockers($companyId)
    {
        //return $this->where('company_id', $companyId)->where('type', 'locker')->find();
        $lockers = $this->join('lockerdaccesses', 'lockeraccesses.locker_id = apiclients.id')->where('apiclients.type', 'locker')->where('apiclients.id', $companyId)->find();
        $lockersList = [];
        foreach($lockers as $locker){
            $lockersList[] = new \App\Entities\ApiClient($locker);
        }
        return $lockersList;
    }

    public function getAllLockers()
    {
        return $this->where('type', 'locker')->find();
    }

    public function getCompaniesLockerAccess($lockerId)
    {
        $sql = "SELECT `a`.*, `l`.`created_at` as 'access_created_at', `l`.`created_at` as 'access_updated_at' FROM `apiclients` as `a` LEFT JOIN `lockeraccesses` as `l` ON `a`.`id` = `l`.`company_id` AND `l`.`locker_id` = ? WHERE `a`.`type` = 'company' ORDER BY `a`.`name`";
        $query = $this->db->query($sql, [$lockerId]);
        return $query->getResult();
    }

    public function create($data)
    {

        if ($this->save($data)) {
            $id = $this->getInsertID();

            //save ID as company_id for company client type
            if($data->type == 'company'){
                $data->id = $id;
                $data->company_id = $id;
                $this->save($data);
            }

            return $this->getInsertID();
        }

        return false;
    }

    public function deleteByHash($IdHash)
    {
        return $this->where('id_hash', $IdHash)->delete();
    }
}
