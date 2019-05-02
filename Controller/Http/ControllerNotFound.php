<?php
namespace com\github\tncrazvan\CatServer\Controller\Http;

use com\github\tncrazvan\CatServer\Http\HttpController;
use com\github\tncrazvan\CatServer\Http\HttpEvent;

class ControllerNotFound extends HttpController{
    
    public function &main(HttpEvent &$e, array &$path, string &$content) {
        $e->setStatus(self::STATUS_NOT_FOUND);
    }

    public function onClose() {}
}