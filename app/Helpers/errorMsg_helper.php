<?php
use \Firebase\JWT\JWT;

function createErrorMsg($htmlErrorCode, $appErrorCode, array $messages){

    $error = [];
    $error['status'] = $htmlErrorCode;
    $error['error'] = $appErrorCode;
    $error['messages'] = $messages;
    return $error;

}