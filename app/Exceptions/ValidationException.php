<?php
namespace App\Exceptions;

class ValidationException extends \Exception{

    private $errorCode;

    public function __construct($msg, $errorCode=500, $code = 0, Exception $old = null) {
        $this->errorCode = $errorCode;
        parent::__construct($msg, $code, $old);
    }

    public function getErrorCode(){
        return $this->errorCode;
    }

}