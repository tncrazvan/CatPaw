<?php
namespace com\github\tncrazvan\catpaw\controller\http;

use com\github\tncrazvan\catpaw\tools\Http;
use com\github\tncrazvan\catpaw\tools\SharedObject;
use com\github\tncrazvan\catpaw\tools\Status;
use com\github\tncrazvan\catpaw\http\HttpResponse;
use com\github\tncrazvan\catpaw\http\HttpController;

class File extends HttpController{
    
    public function main() {
        switch($this->getRequestMethod()){
            case "GET":
                return Http::getFile($this,$this->listener->so->webRoot."/".$this->listener->resource[0]);
            break;
            default:
                return new HttpResponse([
                    "Status"=>Status::METHOD_NOT_ALLOWED
                ]);
            break;
        }
    }

    public function onClose():void {}

}