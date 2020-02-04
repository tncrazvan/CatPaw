<?php
namespace com\github\tncrazvan\catpaw\controller\http;

use com\github\tncrazvan\catpaw\tools\Http;
use com\github\tncrazvan\catpaw\tools\Server;
use com\github\tncrazvan\catpaw\tools\Status;
use com\github\tncrazvan\catpaw\http\HttpEvent;
use com\github\tncrazvan\catpaw\http\HttpResponse;
use com\github\tncrazvan\catpaw\http\HttpController;
use com\github\tncrazvan\catpaw\exception\HeaderFieldNotFoundException;

class App extends HttpController{
    
    public function main(HttpEvent &$e, array &$path, string &$content) {
        switch($e->getClientMethod()){
            case "GET":
                return Http::getFile($e->getClientHeader(),Server::$webRoot."/".Server::$entryPoint);
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