<?php
namespace com\github\tncrazvan\catpaw\controller\http;

use com\github\tncrazvan\catpaw\tools\Http;
use com\github\tncrazvan\catpaw\tools\Status;
use com\github\tncrazvan\catpaw\http\HttpEvent;
use com\github\tncrazvan\catpaw\http\HttpResponse;
use com\github\tncrazvan\catpaw\http\HttpController;

class ControllerNotFound extends HttpController{
    
    public function main() {
        return new HttpResponse([
            "Status"=>Status::NOT_FOUND
        ]);
    }

    public function onClose():void {}
}