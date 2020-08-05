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
    //requested query string
    public string $queryString;
    //requested path
    public string $path;
    //requested resource (path + query)
    public string $resource;
    //request headers
    public HttpHeaders $requestHeaders;
    //length of the location array
    /*public int $locationLen;
    public int $resourceLen;*/
    public SharedObject $so;
    public array $params = [];
    public $client;

    private const PATTERN_PATH_PARAM = '/(?<=^{)([A-z_][A-z0-9_]+)(?=}$)/';

    public function __construct(&$client,SharedObject $so) {
        $this->client = $client;
        $this->so = $so;
    }
    public function run():array{
        if($this->resolve())
            return $this->serve();
        else
            \fclose($this->client);
        return [false,false];
    }


    private static function _forward(array &$paths, HttpEventListener &$listener):void{
        if(isset($paths["@forward"]))
            foreach($paths["@forward"] as $from => &$to){
                $fromStar = $from[-1] === '*';
                if($fromStar && $from !== '/'){
                    $cleaned = \substr($from,0,-1);
                    if(Strings::startsWith($listener->path,$cleaned)){
                        $listener->path = $to;  
                        break; 
                    }//else don't do anything
                }else if($listener->path === $from){
                    $listener->path = $to;
                    break;
                }//else don't do anything
            }
    }

    private static function _file(string &$type, array &$paths, HttpEventListener &$listener, ?\CLosure &$callback):bool{
        if($type !== 'websocket'){
            $location = $listener->so->webRoot.$listener->path;

            //checking if it's a file
            if(\file_exists($location) && !\is_dir($location)){
                $callback = $paths["@file"];
                return true;
            }
        }
        return false;
    }


    private static function _event(array &$paths, HttpEventListener &$listener, ?\CLosure &$callback):bool{
        $_event_path = \preg_replace('/(?<=^)\/+/','',$listener->path);
        foreach($paths as $route => &$cb){
            if($route === '@file' || 
                    $route === '@404' || 
                        ($route === '/' && $route !== $listener->path)) continue;

            if($route[0] === '/')
                $route = \substr($route,1);
            
            $pieces = \explode('/',$route);
            
            $len=\count($pieces);
            $c = 0;
            $parts = \preg_split('/\//',$_event_path);
            for($i=0,$lenR = \count($parts);$i<$len && $i<$lenR;$i++){
                $matches = null;
                if(\preg_match(self::PATTERN_PATH_PARAM,$pieces[$i],$matches)) {
                    $listener->params[$matches[0]] = $parts[$i];
                    $c++;
                }
                else if($pieces[$i] === $parts[$i])  $c++;
            }

            if($c === $len){
                $callback = $cb;
                return true;
            }
        }
        return false;
    }

    public static function callback(string $type,HttpEventListener $listener):\Closure{
        $paths = &$listener->so->events[$type];

        self::_forward($paths,$listener);

        $callback = $paths["@404"];

        if(self::_file($type,$paths,$listener,$callback))
            return $callback;

        if(self::_event($paths,$listener,$callback))
            return $callback;

        return $callback;
    }

    private function resolve():bool{
        $input = \fread($this->client, $this->so->httpMtu);
        if($input === false || trim($input) === '') //0 is okay, but these are not okay: false || null || ''
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

        $this->resource = \urldecode($this->requestHeaders->getResource());
        if($this->resource === '')
            $this->resource = '/';
        if($this->resource[0] !== '/')
            $this->resource = '/'.$this->resource;

        $_path_and_query = \preg_split('/\?\&/',$this->resource,2);

        $this->path = $_path_and_query[0];
        if(\count($_path_and_query) > 1)
            $this->queryString = $_path_and_query[1];
        else 
            $this->queryString = '';
        //$_path_and_query = null; //this is not required since it's scoped
        return true;
    }

    private function serve():array{
       if($this->requestHeaders->get("Connection") !== null){
           if(\preg_match("/Upgrade/", $this->requestHeaders->get("Connection"))){
                //websocket event goes here
                return [false,true];
                    
           }else{
                //http event goes here
                return [true,false];
           }
       }else{
            return [true,false];
       }
    }
}