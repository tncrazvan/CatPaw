<?php
namespace com\github\tncrazvan\CatServer\Http;

class HttpEventListener extends HttpRequestReader{
    public function __construct($client) {
        parent::__construct($client);
    }
    public function onRequest(HttpHeader &$client_header, string $content):void {
        if($client_header !== null && $client_header->get("Connection") !== null){
            if(preg_match("/upgrade/", $client_header->get("Connection"))){
                //websocket event goes here
            }else{
                //http event goes here
                $event = new HttpEvent($this->client,$client_header,$content);
                $event->execute();
            }
        }
    }

}