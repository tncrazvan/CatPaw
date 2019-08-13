<?php
namespace com\github\tncrazvan\CatPaw\Controller\Http;

use com\github\tncrazvan\CatPaw\Tools\G;
use com\github\tncrazvan\CatPaw\Tools\Http;
use com\github\tncrazvan\CatPaw\Tools\Status;
use com\github\tncrazvan\CatPaw\Http\HttpEvent;
use com\github\tncrazvan\CatPaw\Http\HttpResponse;
use com\github\tncrazvan\CatPaw\Http\HttpController;
use com\github\tncrazvan\CatPaw\Exception\HeaderFieldNotFoundException;

class App extends HttpController{
    
    public function &main(HttpEvent &$e, array &$path, string &$content) {
        switch($e->getClientMethod()){
            case "GET":
                return Http::getFile($e->getClientHeader(),G::$webRoot."/".G::$entryPoint);
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