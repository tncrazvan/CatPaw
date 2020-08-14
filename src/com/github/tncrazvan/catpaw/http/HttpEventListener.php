<?php
namespace com\github\tncrazvan\catpaw\http;

use com\github\tncrazvan\catpaw\tools\Strings;
use com\github\tncrazvan\catpaw\http\HttpHeaders;
use com\github\tncrazvan\catpaw\tools\SharedObject;

class HttpEventListener{
    //content sent (if POST method)
    //public ?string $requestContent;
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
    public $input = '';
    public string $hash;
    public bool $completeBody = true;
    public int $continuation = 0;
    public int $headerBodyLength = 0;
    public int $actualBodyLength = 0;
    public int $failedContinuations = 0;
    public array $properties = [
        "http-consumer" => false
    ];
    public const EVENT_TYPE_HTTP = 0;
    public const EVENT_TYPE_WEBSOCKET = 1;
    public $eventType = -1;
    public $event = null;
    public bool $httpConsumerStarted = false;

    private const PATTERN_PATH_PARAM = '/(?<=^{)([A-z_][A-z0-9_]+)(?=}$)/';

    private int $_find_headers_offset = 0;

    public function __construct(&$client,SharedObject $so) {
        $this->client = $client;
        $this->so = $so;
        $this->hash = \spl_object_hash($this).rand();
    }

    public function findHeaders():bool{
        $headersDetected = strpos($this->input,"\r\n\r\n",$this->_find_headers_offset);
        if($headersDetected !== false)
            return true;
        $this->_find_headers_offset += strlen($this->input);
        return false;
    }

    public function run():array{
        /*if($this->continuation > 0){
            return $this->serve();
        }else */
        if($this->resolve())
            return $this->serve();
        else
            \fclose($this->client);
        return [false,false];
    }

    public function runWebSocketDefault(){
        $this->_im_a_fork = true;
        //$this->event = &WebSocketEvent::make($listener);
        $this->event->run();
    }
    public function runHttpDefault(){
        global $_EVENT;
        //$_EVENT = &HttpEvent::make($this);
        $_EVENT = $this->event;
        $_EVENT->run();
        $_EVENT = null;
        $this->input[1] = null;
    }

    public function runHttpLiveBodyInject(&$read){
        
        if($this->event->generator){
            if($this->event->generator->valid()){
                global $_EVENT;
                $_EVENT = $this->event;
                $returnObject = $this->event->generator->current();
                if($returnObject instanceof HttpConsumer){
                    if($read === false){
                        $returnObject->done();
                    }else{
                        $returnObject->produce($read);
                    }
                }
                $this->event->generator->next();
                $_EVENT = null;
            }else{
                unset($this->so->httpQueue[$this->hash]);
                $this->so->httpConnections[$this->event->requestId] = &$this->event;
            }
        }
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

    private static function _file(string &$type, array &$paths, HttpEventListener &$listener, \Closure &$callback):bool{
        if($type !== 'websocket'){
            $location = $listener->so->webRoot.$listener->path;

            //checking if it's a file
            if(\file_exists($location) && !\is_dir($location)){
                if(is_array($paths["@file"])){
                    if(isset($paths["@file"]['run'])){
                        $callback = $paths["@file"]['run'];
                        foreach($paths["@file"] as $key =>&$property){
                            if($key === 'run') continue;
                            $listener->properties[$key] = $property;
                        }
                    }
                }else{
                    $callback = $paths["@file"];
                    return true;
                }                
            }
        }
        return false;
    }


    private static function _event(array &$paths, HttpEventListener &$listener, \Closure &$callback):bool{
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
                if(is_array($cb)){
                    if(isset($cb['run'])){
                        $callback = $cb['run'];
                        foreach($cb as $key =>&$property){
                            if($key === 'run') continue;
                            $listener->properties[$key] = &$property;
                        }
                    }else
                        continue;
                }else
                    $callback = $cb;
                return true;
            }
        }
        return false;
    }

    public static function callback(string $type,HttpEventListener $listener):\Closure{
        $paths = &$listener->so->events[$type];

        self::_forward($paths,$listener);


        if(is_array($paths["@file"])){
            if(isset($paths["@file"]['run'])){
                $callback = $paths["@file"]['run'];
                foreach($paths["@file"] as $key =>&$property){
                    if($key === 'run') continue;
                    $listener->properties[$key] = $property;
                }
            }
        }else
            $callback = $paths["@file"];


        if(self::_file($type,$paths,$listener,$callback))
            return $callback;

        if(self::_event($paths,$listener,$callback))
            return $callback;

        return $callback;
    }

    private function resolve():bool{
        if($this->input === '') //0 is okay, but these are not okay: false || null || ''
            return false;
        $this->input = \preg_split('/\r\n\r\n/', $this->input,2);
        $partsCounter = \count($this->input);
        if($partsCounter === 0)
            return false;
        
        if($partsCounter === 1){
            $this->input[1] = '';
        }


        $this->requestHeaders = HttpHeaders::fromString(null, $this->input[0]);
        if(!$this->requestHeaders)
            return false;

        $this->actualBodyLength += \strlen($this->input[1]);
        if($this->requestHeaders->has("Content-Length")){
            try{
                $this->headerBodyLength = intval($this->requestHeaders->get(("Content-Length")));
            }catch(\Exception $ex){
                $this->headerBodyLength = $this->actualBodyLength;
            }
            catch(\ErrorException $ex){
                $this->headerBodyLength = $this->actualBodyLength;
            }
            
            if($this->actualBodyLength < $this->headerBodyLength){
                $this->completeBody = false;
            }
        }
        


        $this->resource = \urldecode($this->requestHeaders->getResource());
        if($this->resource === '')
            $this->resource = '/';
        if($this->resource[0] !== '/')
            $this->resource = '/'.$this->resource;

        $_path_and_query = \preg_split('/\?|\&/',$this->resource,2);

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