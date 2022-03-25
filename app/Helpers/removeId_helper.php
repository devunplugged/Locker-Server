<?php

function removeId($data){

    if(is_array($data)){
        foreach($data as $d){
            if(isset($d->id)){
                unset($d->id);
            }
        }
    }else{
        if(isset($data->id)){
            unset($data->id);
        }
    }

    return $data;
}