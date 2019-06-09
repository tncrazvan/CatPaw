<?php
namespace com\github\tncrazvan\CatPaw\Controller\Http;

use com\github\tncrazvan\CatPaw\Tools\Http;
use com\github\tncrazvan\CatPaw\Http\HttpEvent;
use com\github\tncrazvan\CatPaw\Http\HttpResponse;
use com\github\tncrazvan\CatPaw\Http\HttpController;

class ControllerNotFound extends HttpController{
    
    public function &main(HttpEvent &$e, array &$path, string &$content) {
        return new HttpResponse([
            "Status"=>Http::STATUS_NOT_FOUND
        ]);
    }

    public function onClose() {}
}