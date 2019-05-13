<?php
namespace com\github\tncrazvan\CatServer\Controller\Http;

use com\github\tncrazvan\CatServer\Tools\Http;
use com\github\tncrazvan\CatServer\Http\HttpEvent;
use com\github\tncrazvan\CatServer\Http\HttpController;

class ControllerNotFound extends HttpController{
    
    public function &main(HttpEvent &$e, array &$path, string &$content) {
        $e->setStatus(Http::STATUS_NOT_FOUND);
    }

    public function onClose() {}
}