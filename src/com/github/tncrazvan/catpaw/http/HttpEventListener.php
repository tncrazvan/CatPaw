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
    //content sent (if POST method)
    public ?string $requestContent;
    //the while requested resource (URL + Query String)
    public ?array $resource;
    //request headers
    public HttpHeaders $requestHeaders;
    //array of url split on "/"
    public array $location;
    //length of the location array
    public int $locationLen;
    public int $resourceLen;
    public SharedObject $so;
    public array $params = [];
    public $client;

    private const PATTERN_PATH_PARAM = '/(?<=^{)([A-z_][A-z0-9_]+)(?=}$)/';

    public function __construct(&$client,SharedObject $so) {
        $this->client = $client;
        $this->so = $so;
    }
    public function run():void{
        if($this->resolve())
            $this->serve();
        else
            \fclose($this->client);
    }

    public static function callback(string $type,HttpEventListener $listener):\Closure{
        $paths = &$listener->so->events[$type];
        $request = \implode('/',$listener->location);

        if($type !== 'websocket'){
            $location = $listener->so->webRoot.$request;
            
            if(is_dir($location) && $type)
                if(Strings::endsWith($location,"/"))
                    $location .= $listener->so->entryPoint;
                else
                    $location .= '/'.$listener->so->entryPoint;
            
            
            //checking if it's a file
            if(\file_exists($location) && !\is_dir($location))
                return $paths["@file"];
        }
        
        
        foreach($paths as $route => &$callback){
            if($route === '@file' || 
                    $route === '@404' || 
                        ($route === '/' && $route !== $request)) continue;

            if($route[0] === '/')
                $route = \substr($route,1);
            
            $pieces = \explode('/',$route);
            
            $len=\count($pieces);
            $c = 0;
            for($i=0,$lenR = \count($listener->location);$i<$len && $i<$lenR;$i++){
                $matches = null;
                if(\preg_match(self::PATTERN_PATH_PARAM,$pieces[$i],$matches)) {
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

    private function resolve():bool{
        $input = \fread($this->client, $this->so->httpMtu);
        if(!$input)
            return false;
        
        if(trim($input) === "")
            return false;
        
        $input = \preg_split('/\r\n\r\n/', $input,2);
        $partsCounter = \count($input);
        if($partsCounter === 0)
            return false;
        
        $strHeaders = $input[0];
        $this->requestContent = $partsCounter>1?$input[1]:"";
        $this->requestHeaders = HttpHeaders::fromString(null, $strHeaders);
        if(!$this->requestHeaders)
            return false;
        $this->resource = \preg_split("/\\?|\\&/m",\preg_replace("/^\\//m","",\urldecode($this->requestHeaders->getResource())));
        $this->resourceLen = \count($this->resource);
        $this->location = \preg_split("/\\//m",$this->resource[0]);
        $this->locationLen = \count($this->location);

        return true;
    }

    private function serve():void{
       if($this->requestHeaders->get("Connection") !== null){
           if(\preg_match("/Upgrade/", $this->requestHeaders->get("Connection"))){
                //websocket event goes here
                $this->websocket();
           }else{
                //http event goes here
                $this->http11();
           }
       }
    }

    private function websocket():void{
        $event = WebSocketEvent::make($this);
        $event->run();
    }
    private function http11():void{
        $event = HttpEvent::make($this);
        $event->run();
    }
}