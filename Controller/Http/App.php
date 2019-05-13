<?php
namespace com\github\tncrazvan\CatPaw\Controller\Http;

use com\github\tncrazvan\CatPaw\Tools\G;
use com\github\tncrazvan\CatPaw\Http\HttpEvent;
use com\github\tncrazvan\CatPaw\Http\HttpController;

class App extends HttpController{
    
    public function &main(HttpEvent &$e, array &$path, string &$content) {
        $e->sendFileContents(G::$webRoot."/".G::$entryPoint);
    }

    public function onClose() {}

}