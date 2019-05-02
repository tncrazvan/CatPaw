<?php
namespace com\github\tncrazvan\CatServer\Controller\Http;

use com\github\tncrazvan\CatServer\Http\HttpController;
use com\github\tncrazvan\CatServer\Http\HttpEvent;

class App extends HttpController{
    
    public function &main(HttpEvent &$e, array &$path, string &$content) {
        $e->sendFileContents(self::$entry_point);
    }

    public function onClose() {}

}