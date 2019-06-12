<?php
namespace com\github\tncrazvan\CatPaw\Controller\Http;

use com\github\tncrazvan\CatPaw\Tools\G;
use com\github\tncrazvan\CatPaw\Tools\Http;
use com\github\tncrazvan\CatPaw\Http\HttpEvent;
use com\github\tncrazvan\CatPaw\Http\HttpResponse;
use com\github\tncrazvan\CatPaw\Http\HttpController;

class App extends HttpController{
    
    public function &main(HttpEvent &$e, array &$path, string &$content) {
        switch($e->getClientMethod()){
            case "GET":
                $response = Http::getFile($e->getClientHeader(),G::$webRoot."/".G::$entryPoint);
                return $response;
            break;
            default:
                $response = new HttpResponse([
                    "Status"=>Http::STATUS_BAD_REQUEST
                ]);
                return $response;
            break;
        }
    }

    public function onClose() {}

}