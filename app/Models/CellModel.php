<?php

namespace App\Models;

use CodeIgniter\Model;
use Hashids\Hashids;
use App\Libraries\Logger\Logger;

class CellModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'cells';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $insertID         = 0;
    protected $returnType       = \App\Entities\Cell::class;//'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['cell_sort_id', 'size', 'locker_id', 'status', 'service'];

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


    public function create($data, $lockerId){
        
        $this->save($data);
        $id = $this->getInsertID();

        $updateData = [
            'id' => $id,
            'cell_sort_id' => $this->getNextCellSortId($lockerId)
        ];

        $this->save($updateData);
        return $updateData['cell_sort_id'];

    }

    public function deleteByLockerAndSortId($lockerId, $sortId){
        return $this->where('cell_sort_id', $sortId)->where('locker_id', $lockerId)->delete();
    }

    public function getAll(){
        return $this->findAll();
    }

    public function get($id){
        return $this->where('id', $id)->first();
    }

    public function getByLocker($lockerId){
        return $this->where('locker_id', $lockerId)->findAll();
    }


    public function getNextCellSortId($lockerId){
        $results = $this->select('cell_sort_id')->where('locker_id', $lockerId)->orderBy('cell_sort_id','DESC')->limit(1)->first();

        if(!$results)    
            return 1;

        return ++$results->cell_sort_id;
    }

    public function getLockerCells($lockerId)
    {
        return $this->where('locker_id', $lockerId)->find();
    }

    public function getLockerCellsAndPackages($lockerId)
    {
        $sql = "SELECT c.id as cell_id, c.cell_sort_id, c.locker_id, c.size, c.status as cell_status, p.id, p.status, p.inserted_at, p.created_at FROM cells as c LEFT JOIN packages as p ON c.cell_sort_id = p.cell_sort_id AND c.locker_id = p.locker_id AND p.removed_at IS NULL WHERE c.locker_id = ? ORDER BY c.cell_sort_id";
        $query = $this->db->query($sql, [$lockerId]);
        return $query->getResult();
    }

    public function getLockerCellsStatus($lockerId)
    {
        
        $status = ['a' => [0, 0], 'b' => [0, 0], 'c' => [0, 0] ];
        $cells = $this->where('locker_id', $lockerId)->find();

        foreach($cells as $cell){
            $status[$cell->size][1]++;
        }

        foreach($status as $cellSize => $value){
            $status[$cellSize][0] = count($this->getEmptyCells($lockerId, $cellSize));
        }

        return $status;
        
    }

    public function getCellByLockerAndSortId($lockerId, $sortId){
        return $this->where('locker_id', $lockerId)->where('cell_sort_id', $sortId)->first();
    }

    public function getEmptyCells($lockerId, $cellSize){

        //$sql = "SELECT `c`.* FROM `cells` as `c` LEFT JOIN `packages` as `p` ON `c`.`id` = `p`.`cell_id` AND `p`.`removed_at` IS NULL WHERE `c`.`locker_id` = ? AND `c`.`size` = ? AND (`p`.`id` IS NULL OR `p`.`removed_at` IS NOT NULL)";
        $sql = "SELECT `c`.* FROM `cells` as `c` LEFT JOIN `packages` as `p` ON `c`.`cell_sort_id` = `p`.`cell_sort_id` AND `c`.`locker_id` = `p`.`locker_id` AND `p`.`removed_at` IS NULL WHERE `c`.`locker_id` = ? AND `c`.`status` = 'closed' AND `c`.`size` = ? AND (`p`.`id` IS NULL OR `p`.`removed_at` IS NOT NULL) AND `c`.`service` = 0";
        $query = $this->db->query($sql, [$lockerId, $cellSize]);
        return $query->getResult();

    }

    public function setCellStatus($task, $status){
   
        $cell = $this->getCellByLockerAndSortId($task->client_id, $task->value);
        if($cell->status == $status)
            return true;

        $cell->status = $status;
        return $this->save($cell);
    }

    public function resetLocekCells($lockerId){

        //$sql = "SELECT `c`.* FROM `cells` as `c` LEFT JOIN `packages` as `p` ON `c`.`id` = `p`.`cell_id` AND `p`.`removed_at` IS NULL WHERE `c`.`locker_id` = ? AND `c`.`size` = ? AND (`p`.`id` IS NULL OR `p`.`removed_at` IS NOT NULL)";
        $sql = "UPDATE `cells` SET `status`='closed' WHERE `locker_id` = ?";
        $this->db->query($sql, [$lockerId]);
        

    }
}
