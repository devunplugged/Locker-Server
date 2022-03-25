<?php
namespace App\Validation;

use App\Models\ApiClientModel;

class PackageRules
{
    public function task_exists(string $str): bool
    {
        $taskModel = new TaskModel();
        $locker = $taskModel->getTaskByHash($str);
        if($locker){
            return true;
        }
        return false;
    }
}