<?php
namespace com\github\tncrazvan\CatPaw\Http;

use com\github\tncrazvan\CatPaw\Http\HttpEvent;
use com\github\tncrazvan\CatPaw\Http\HttpHeader;
use com\github\tncrazvan\CatPaw\Http\HttpRequestReader;
use com\github\tncrazvan\CatPaw\WebSocket\WebSocketEvent;

class HttpEventListener extends HttpRequestReader{
    public function __construct(&$read) {
        parent::__construct($read);
    }
    public function onRequest(HttpHeader &$clientHeader, string &$content):void{
       if($clientHeader !== null && $clientHeader->get("Connection") !== null){
           if(preg_match("/Upgrade/", $clientHeader->get("Connection"))){
               //websocket event goes here
               $event = new WebSocketEvent($this->client,$clientHeader,$content);
               $event->run();
           }else{
               //http event goes here
               $event = new HttpEvent($this->client,$clientHeader,$content);
               $event->run();
           }
       }
       return;
    }

}