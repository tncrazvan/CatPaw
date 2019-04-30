<?php
use com\github\tncrazvan\CatServer\Http\HttpController;
use \com\github\tncrazvan\CatServer\Http\HttpEvent;
class ControllerNotFound extends HttpController{
    
    public function main(HttpEvent &$e, array &$path, string &$content) {
        $e->set_status(self::STATUS_NOT_FOUND);
    }

    public function on_close() {}

}

