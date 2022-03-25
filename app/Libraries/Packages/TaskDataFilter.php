<?php
namespace App\Libraries\Packages;

class TaskDataFilter{
    
    //private static $allowed = ['id', 'task', 'value'];

    public static function filter($tasks){
        
        if(is_array($tasks)){
            $newTasks = [];
            foreach($tasks as $task){
                $newTasks[] = self::filterTask($task);
            }
            return $newTasks;
        }else{
            return self::filterTask($tasks);
        }
        

    }

    public static function filterTask($task){
        $newTask = new \stdClass();
        $newTask->id = $task->id;
        $newTask->type = $task->type;
        $newTask->value = $task->value;
        return hashId($newTask);
    }

}