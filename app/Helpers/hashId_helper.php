<?php
use Hashids\Hashids;

function hashId($data){

$toHash = ['id', 'locker_id', 'company_id', 'client_id', 'cell_id'];

    $hashids = new Hashids(HASHID_SALT, 6, HASHID_ALPHABET);

    if(is_array($data)){
        foreach($data as $d){
            foreach($toHash as $variableToHash){
                if(isset($d->$variableToHash)){
                    $d->$variableToHash = $hashids->encode($d->$variableToHash);
                }
            }
        }
    }elseif(is_object($data)){
        foreach($toHash as $variableToHash){
            if(isset($data->$variableToHash)){
                $data->$variableToHash = $hashids->encode($data->$variableToHash);
            }
        }
    }else{
        $data = $hashids->encode($data);
    }

    return $data;
}

function encodeHashId($id){
    $hashids = new Hashids(HASHID_SALT, 6, HASHID_ALPHABET);
    return $hashids->encode($id);
}

function decodeHashId($hash){
    $hashids = new Hashids(HASHID_SALT, 6, HASHID_ALPHABET);
    $decoded = $hashids->decode($hash);

    if(is_array($decoded) && isset($decoded[0])){
        return $decoded[0];
    }

    return 0;
}