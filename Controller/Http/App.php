<?php
namespace com\github\tncrazvan\CatServer\Controller\Http;

use com\github\com\tncrazvan\CatServer\Tools\G;
use com\github\tncrazvan\CatServer\Http\HttpEvent;
use com\github\tncrazvan\CatServer\Http\HttpController;

class App extends HttpController{
    
    public function &main(HttpEvent &$e, array &$path, string &$content) {
        $e->sendFileContents(G::$webRoot."/".G::$entryPoint);
    }

    public function onClose() {}

}