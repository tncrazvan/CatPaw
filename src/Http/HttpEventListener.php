<?php
namespace com\github\tncrazvan\catpaw\http;

use com\github\tncrazvan\catpaw\tools\Http;
use com\github\tncrazvan\catpaw\tools\Server;
use com\github\tncrazvan\catpaw\tools\Status;
use com\github\tncrazvan\catpaw\http\HttpEvent;
use com\github\tncrazvan\catpaw\http\HttpHeader;
use com\github\tncrazvan\catpaw\http\HttpResponse;
use com\github\tncrazvan\catpaw\http\HttpController;
use com\github\tncrazvan\catpaw\http\HttpRequestReader;
use com\github\tncrazvan\catpaw\websocket\WebSocketEvent;
use com\github\tncrazvan\catpaw\websocket\WebSocketController;

class HttpEventListener extends HttpRequestReader{
    public function __construct(&$read) {
        parent::__construct($read);
    }
    public function onRequest(HttpHeader &$clientHeader, string &$content):void{
       if($clientHeader !== null && $clientHeader->get("Connection") !== null){
           if(preg_match("/Upgrade/", $clientHeader->get("Connection"))){
                //websocket event goes here
                $url = preg_split("/\\?|\\&/m",preg_replace("/^\\//m","",urldecode($clientHeader->get("Resource"))))[0];
                $location = preg_split("/\\//m",$url);
                WebSocketController::serveController($location,$controller,$this->client,$clientHeader,$content);
                $controller->install($this->client,$clientHeader,$content);
                $controller->run();
           }else{
                //http event goes here
                //$event = new HttpEvent($this->client,$clientHeader,$content);
                //$event->run();
                $url = preg_split("/\\?|\\&/m",preg_replace("/^\\//m","",urldecode($clientHeader->get("Resource"))))[0];
                $location = preg_split("/\\//m",$url);
                $serve = function(){
                    return new HttpResponse([
                        "Status"=>Status::NOT_FOUND
                    ],null);
                };
                HttpController::serveController($location,$controller,$serve,$this->client,$clientHeader,$content);

                $filename = Server::$webRoot."/".$url;
                if($url === "favicon.ico"){
                    if(!\file_exists($filename)){
                        $controller->send(new HttpResponse([
                            "Status"=>Status::NOT_FOUND
                        ]));
                    }else{
                        $controller->send(Http::getFile($controller->clientHeader,$filename));
                    }
                }else{
                    if(file_exists($filename)){
                        if(!is_dir($filename)){
                            $controller->send(Http::getFile($controller->clientHeader,$filename));
                        }else{
                            $controller->send($serve());
                        }
                    }else{
                        $controller->send($serve());
                    }
                }
                
                if(method_exists($controller, "onClose"))
                $controller->onClose();
                $controller->close();
           }
       }
       return;
    }

}