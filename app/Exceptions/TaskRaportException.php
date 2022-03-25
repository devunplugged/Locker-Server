<?php
namespace App\Exceptions;

class TaskRaportException extends \Exception{

    private $response;

    public function __construct($exmsg, $response, $code = 0, Exception $old = null) {
        $this->response = $response;
        parent::__construct($exmsg, $code, $old);
    }

    public function getResponse(){
        return $this->response;
    }

}