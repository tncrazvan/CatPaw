<?php
namespace com\github\tncrazvan\CatServer\Http;
use com\github\tncrazvan\CatServer\WebSocket\WebSocketEvent;
class HttpEventListener extends HttpRequestReader{
    public function __construct(&$read) {
        parent::__construct($read);
    }
    public function onRequest(HttpHeader &$client_header, string &$content):void{
       if($client_header !== null && $client_header->get("Connection") !== null){
           if(preg_match("/Upgrade/", $client_header->get("Connection"))){
               //websocket event goes here
               $event = new WebSocketEvent($this->client,$client_header,$content);
               $event->run();
           }else{
               //http event goes here
               $event = new HttpEvent($this->client,$client_header,$content);
               $event->run();
           }
       }
       return;
    }

}