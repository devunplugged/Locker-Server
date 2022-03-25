<?php
namespace App\Libraries\Logger;

use App\Models\LogModel;

class Logger{

    public static function log($importance, $content, $description='', $clientType='', $clientId=0, $type='common', $out = 'db'){
        
        if(is_array($content) || is_object($content)){
            ob_start();
                print_r($content);
            $content = ob_get_contents();
            ob_end_clean();
        }
        
        switch($out){
            case 'file': self::to_file($importance, $content, $description, $clientType, $clientId, $type); break;
            case 'screen': self::to_screen($importance, $content, $description, $clientType, $clientId, $type); break;
            case 'db': self::to_database($importance, $content, $description, $clientType, $clientId, $type); break;
        }

    }

    private static function to_file($importance, $content, $description, $clientType, $clientId, $type){
        $fp = fopen(ROOTPATH . 'logs' . DIRECTORY_SEPARATOR .$type.'-log.txt', 'a');//opens file in append mode.
        $text = "LOG (".date("Y-m-d H:i:s")."): Type: $type | Importance: $importance\n ";
        $text = "Client type: $ClientType | Client id: $clientId\n ";
        $text .= "$content\n ";
        $text .= "Description: $description\n ";
        $text .= "------------------------------------------------------------\n ";

        fwrite($fp, $text);
        fclose($fp);
    }

    private static function to_screen($importance, $content, $description, $clientType, $clientId, $type){
        echo "<h3>LOG (".time("Y-m-d H:i:s").") Type: $type Importance: $importance</h3>";
        echo "<h4>Client type: $clientType Client id: $clientId</h4>";
        echo '<pre>';
        echo $content;
        echo '</pre>';
        echo 'Description: ' . $description;
    }

    private static function to_database($importance, $content, $description, $clientType, $clientId, $type){
        $logModel = new LogModel();
        $log = new \App\Entities\Log();
        $log->importance = $importance;
        $log->content = $content;
        $log->type = $type;
        $log->description = $description;
        $log->client_type = $clientType;
        $log->client_id = $clientId;
        $logModel->save($log);
    }
}