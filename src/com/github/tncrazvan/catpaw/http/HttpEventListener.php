<?php
namespace com\github\tncrazvan\catpaw\http;

use com\github\tncrazvan\catpaw\tools\Http;
use com\github\tncrazvan\catpaw\tools\Status;
use com\github\tncrazvan\catpaw\tools\Strings;
use com\github\tncrazvan\catpaw\http\HttpEvent;
use com\github\tncrazvan\catpaw\http\HttpHeaders;
use com\github\tncrazvan\catpaw\http\HttpResponse;
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
        $so,
        $params = [];
    public function __construct(&$client,SharedObject $so) {
        $this->client = $client;
        $this->so = $so;
    }
    public function run():void{
        $this->resolve();
        $this->serve();
    }
    
    private const PATTERN_PATH_PARAM = '/(?<=^{)([A-z_][A-z0-9_]+)(?=}$)/';

    public static function callback(string $type,HttpEventListener $listener):\Closure{
        $paths = &$listener->so->events[$type];
        $request = \implode('/',$listener->location);
        $resource = $listener->so->webRoot.$request;
        
        if(is_dir($resource))
            if(Strings::endsWith($resource,"/"))
                $resource .= $listener->so->entryPoint;
            else
                $resource .= '/'.$listener->so->entryPoint;
        
        
        //checking if it's a file
        if(\file_exists($resource) && !\is_dir($resource))
            return $paths["@file"];
        
        
        foreach($paths as $route => &$callback){
            if($route === '@file' || 
                    $route === '@404' || 
                        ($route === '/' && $route !== $request)) continue;

            if($route[0] === '/')
                $route = substr($route,1);
            
            $pieces = \explode('/',$route);
            
            $len=count($pieces);
            $c = 0;
            for($i=0,$lenR = count($listener->location);$i<$len && $i<$lenR;$i++){
                $matches = null;
                if(preg_match(self::PATTERN_PATH_PARAM,$pieces[$i],$matches)) {
                    $listener->params[$matches[0]] = $listener->location[$i];
                    $c++;
                }
                else if($pieces[$i] === $listener->location[$i]) 
                    $c++;
            }

            if($c === $len)
                return $callback;
        }

        return $paths["@404"];
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
        $this->resource = preg_split("/\\?|\\&/m",preg_replace("/^\\//m","",urldecode($this->requestHeaders->getResource())));
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
        $event = WebSocketEvent::controller($this);
        $event->run();
    }
    private function http11(){
        $event = HttpEvent::controller($this);
        $event->run();
    }
}