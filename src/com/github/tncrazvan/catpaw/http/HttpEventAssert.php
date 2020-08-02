<?php
namespace com\github\tncrazvan\catpaw\http;

use com\github\tncrazvan\catpaw\tools\Status;

class HttpEventAssert{
    public static function true(bool $assertion,HttpEventException $ex = null){
        if(!$assertion){
            if($ex === null)
                $ex = new HttpEventException("Unknown error.",Status::INTERNAL_SERVER_ERROR);
            throw $ex;
        }
    }
}