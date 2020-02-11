<?php
namespace com\github\tncrazvan\catpaw\http;

use com\github\tncrazvan\catpaw\tools\Server;
use com\github\tncrazvan\catpaw\http\HttpEvent;
use com\github\tncrazvan\catpaw\http\HttpHeaders;
use com\github\tncrazvan\catpaw\websocket\WebSocketEvent;

class HttpEventListener{
    public 
        $client,
        //request headers
        $requestHeaders,
        //content sent (if POST method)
        $requestContent,
        //array of url split on "/"
        $location,
        //length of the location array
        $locationLen,
        //the while requested resource (URL + Query String)
        $resource,
        $resourceLen;
    public function __construct(&$client) {
        $this->client = $client;
    }
    public function run():void{
        $this->resolve();
        $this->serve();
    }

    private function resolve():void{
        $input = fread($this->client, Server::$httpMtu);
        if(!$input){
            return;
        }
        if(trim($input) === ""){
            return;
        }
        $input = preg_split('/\r\n\r\n/', $input,2);
        $partsCounter = count($input);
        if($partsCounter === 0){
            fclose($this->client);
            return;
        }
        $strHeaders = $input[0];
        $this->requestContent = $partsCounter>1?$input[1]:"";
        $this->requestHeaders = HttpHeaders::fromString($strHeaders);
        $this->resource = preg_split("/\\?|\\&/m",preg_replace("/^\\//m","",urldecode($this->requestHeaders->get("Resource"))));
        $this->resourceLen = count($this->resource);
        $this->location = preg_split("/\\//m",$this->resource[0]);
        $this->locationLen = count($this->location);
    }

    private function serve():void{
       if($this->requestHeaders !== null && $this->requestHeaders->get("Connection") !== null){
           if(preg_match("/Upgrade/", $this->requestHeaders->get("Connection"))){
                //websocket event goes here
                $this->websocket();
           }else{
                //http event goes here
                $this->http11();
           }
       }
    }

    private function websocket(){
        $controller = WebSocketEvent::controller($this);
        $controller->run();
    }
    private function http11(){
        $controller = HttpEvent::controller($this);
        $controller->run();
    }
}