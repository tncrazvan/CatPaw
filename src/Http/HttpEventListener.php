<?php
namespace com\github\tncrazvan\catpaw\http;

use com\github\tncrazvan\catpaw\http\HttpEvent;
use com\github\tncrazvan\catpaw\http\HttpHeaders;
use com\github\tncrazvan\catpaw\tools\SharedObject;
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
        $resourceLen,
        $so;
    public function __construct(&$client,SharedObject $so) {
        $this->client = $client;
        $this->so = $so;
    }
    public function run():void{
        $this->resolve();
        $this->serve();
    }

    
    public static function getClassNameIndex(string $type, HttpEventListener $listener, &$classnameOut){
        $paths = &$listener->so->controllers[$type];

        //checking if it's a file
        if(\file_exists(($filename = $listener->so->webRoot.\implode('/',$listener->location)))){
            if(!\is_dir($filename)){
                $classnameOut = $paths["@file"];
                return 0;
            }
        }

        //looking for controller
        $choice = "";
        for($i=$listener->locationLen;$i>0;$i--){
            $choice = strtolower(trim('/'.implode('/',array_slice($listener->location,0,$i))));
            foreach($paths as $path => &$classname){
                if($path === $choice && class_exists($classname,true)){
                    $classnameOut = $classname;
                    return $i-1;
                }
            }
        }

        //if no controller has been found serve 404
        $classnameOut = $paths["@404"];
        return 0;
    }
    
    public static function &resolveMethodArgs(int $offset, HttpEventListener $listener):array{
        $args = [];
        if($listener->locationLen-1>$offset-1){
            $args = array_slice($listener->location, $offset);
        }
        return $args;
    }

    private function resolve():void{
        $input = fread($this->client, $this->so->httpMtu);
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
        $this->requestHeaders = HttpHeaders::fromString(null, $strHeaders);
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