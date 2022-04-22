<?php

namespace App\Models;

use CodeIgniter\Model;
use Hashids\Hashids;
use Jenssegers\Optimus\Optimus;
use App\Libraries\Logger\Logger;
class PackageModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'packages';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $insertID         = 0;
    protected $returnType       = \App\Entities\Package::class;
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['id_hash', 'code', 'size', 'ref_code', 'track_code', 'recipient_code', 'status', 'locker_id', 'cell_sort_id', 'company_id', 'created_by', 'enter_code_entered_at', 'recipient_code_entered_at', 'inserted_at', 'removed_at','canceled_at'];

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

    public function create($data){
        
        $this->save($data);
        //$hashids = new Hashids(getenv('HASHID_PACKAGE_SALT'), 6, getenv('HASHID_ALPHABET'));
        $id = $this->getInsertID();
        //$hashedId = $hashids->encode($id);

        $optimus = new Optimus(OPTIMUS_PRIME, OPTIMUS_INVERSE, OPTIMUS_RANDOM, OPTIMUS_BIT_LENGTH);
        $code = PACKAGE_CODE_PREFIX . $optimus->encode($id);

        $codeData = [
            'id' => $id,
            'code' => $code,
            'track_code' => md5($id . time()),
        ];
        $this->save($codeData);
        
        return $id;

    }

    public function get($id){
        return $this->where('id', $id)->first();
    }

    public function getPackageByRecipientCode($code){
        return $this->where('recipient_code', $code)->find();
    }

   /* public function getActivePackageByRecipientCode($code){
        $query = $this->db->query('SELECT * FROM `packages` WHERE recipient_code = ? AND (removed_at >= ? OR removed_at IS NULL)', [$code, date("Y-m-d H:i:s", time()-PACKAGE_ADDITIONAL_ACTIVE_TIME)]);
        return $query->getResult();
    }*/

    public function getPackageByRecipientCodeAndLockerId($code, $lockerId){
        return $this->where('recipient_code', $code)->where('locker_id', $lockerId)->find();
    }

    public function getActivePackageByRecipientCodeAndLocker($request){
        $query = $this->db->query(
            'SELECT * FROM `packages` WHERE recipient_code = ? AND locker_id = ? AND (removed_at >= ? OR removed_at IS NULL)', 
            [
                $request->getVar('code'), 
                $request->decodedJwt->clientId, 
                date("Y-m-d H:i:s", time()-PACKAGE_ADDITIONAL_ACTIVE_TIME)
            ]
        );
        return $query->getResult();
    }

    public function getPackageByCodeAndLocker($request){
        Logger::log(56, $request->getVar('code'), "code");
        Logger::log(56, $request->decodedJwt->clientId, "clientId");
        return $this->where('code', $request->getVar('code'))->where('locker_id', $request->decodedJwt->clientId)->find();
    }

    /////////////NEW STAFF FOR PACKAGE OBJECT//////////
    public function getPackageByInsertCodeAndLocker($code, $lockerId){
        return $this->where('code', $code)->where('locker_id', $lockerId)->first();
    }

    public function getActivePackageByInsertCode($code){
        return $this->where('code', $code)->where('inserted_at', NULL)->where('removed_at', NULL)->first();
    }

    public function getActivePackageByRecipientCodeAndLocker_2($code, $lockerId){
        return $this->where('recipient_code', $code)->where('locker_id', $lockerId)->groupStart()->where('removed_at>', date("Y-m-d H:i:s", time()-PACKAGE_ADDITIONAL_ACTIVE_TIME))->orWhere('removed_at', NULL)->groupEnd()->first();
    }

    public function getActivePackageByRecipientCode($code){
        return $this->where('recipient_code', $code)->groupStart()->where('removed_at>', date("Y-m-d H:i:s", time()-PACKAGE_ADDITIONAL_ACTIVE_TIME))->orWhere('removed_at', NULL)->groupEnd()->first();
    }
    /////////////END NEW STAFF FOR PACKAGE OBJECT//////////

    public function getPackageByCode($code){
        return $this->where('code', $code)->find();
    }

    public function getPackage($packageId){
        return $this->find($packageId);
    }

    public function getByHash($packageId){
        return $this->where('id_hash', $packageId)->first();
    }

    public function getPackagesAll(){
        return $this->findAll();
    }

    public function getPackages($data){
        $offset = $data['limit'] * ( $data['page'] - 1 );
        $results = [];
        

        $query = $this;

        if(isset($data['company'])){
            $query = $query->where('company_id', $data['company']);
        }

        //$count = clone $query;
        $results['count'] = 1;
        Logger::log(994, $query->limit($data['limit'], $offset)->getCompiledSelect(), 'query');
        $results['results'] = $query->limit($data['limit'], $offset)->find();
        
        return $results;
    }

    public function getPackagesAllDesc(){
        return $this->orderBy('id DESC')->find();
    }

    public function getPackageToInsert($lockerId, $cellSortId){
        return $this->where('locker_id', $lockerId)->where('cell_sort_id', $cellSortId)->where('inserted_at', NULL)->where('removed_at', NULL)->orderBy('id DESC')->first();
    }

    public function getPackageToRemove($lockerId, $cellSortId){
        return $this->where('locker_id', $lockerId)->where('cell_sort_id', $cellSortId)->where('inserted_at!=', NULL)->where('removed_at', NULL)->where('status','remove-ready')->orderBy('id DESC')->first();
        //->where('recipient_code_entered_at!=',NULL)
    }

    public function getInsertRemoveReadyInLockerPackagesForCell($lockerId, $cellSortId){
        return $this->where('locker_id', $lockerId)->where('cell_sort_id', $cellSortId)->groupStart()->where('status', 'insert-ready')->orWhere('status', 'remove-ready')->orWhere('status', 'in-locker')->groupEnd()->findAll();
    }

    public function geLockedPackageForCell($lockerId, $cellSortId){
        return $this->where('locker_id', $lockerId)->where('cell_sort_id', $cellSortId)->where('status', 'locked')->where('removed_at', NULL)->first();
    }

    public function insertOrRemoveReadyPackagesExistForCell($lockerId, $cellSortId){
        $packages = $this->where('locker_id', $lockerId)->where('cell_sort_id', $cellSortId)->where('status', 'insert-ready')->orWhere('status', 'remove-ready')->findAll();
        return !empty($packages);
    }

    public function insertOrRemoveReadyPackagesExistForLockerExcept($lockerId, $packageId){
        $packages = $this->where('id!=', $packageId)->where('locker_id', $lockerId)->groupStart()->where('status', 'insert-ready')->orWhere('status', 'remove-ready')->groupEnd()->findAll();
        return !empty($packages);
    }

    public function cancelInsertPackage($packageId){
        $package = $this->getByHash($packageId);
        $package->cell_sort_id = NULL;
        $package->inserted_at = NULL;
        $package->removed_at = NULL;
        $this->save($package);
    }

    public function getLockerPackages($lockerId){

        return $this->where('locker_id', $lockerId)->where('cell_sort_id!=', NULL)->where('removed_at', NULL)->find();

    }

    public function getLockerOverduePackages($lockerId){
        $overdueTime = date("Y-m-d H:i:s", time() - PACKAGE_MAX_LOCKER_TIME);
        return $this->where('locker_id', $lockerId)->where('inserted_at<', $overdueTime)->where('removed_at', NULL)->find();
    }

    public function getCompanyPackages($companyId){
        return $this->where('company_id', $companyId)->find();
    }

    public function generateRecipientCode($length){

        do{
            
            $code = '';
            for($i=0; $i<$length; $i++){
                $code .= random_int(0,9);
            }
            $results = $this->getActivePackageByRecipientCode($code);

        }while($results);

        return $code;
    }
}
