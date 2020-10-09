<?php
namespace com\github\tncrazvan\catpaw\http;

use com\github\tncrazvan\catpaw\EventManager;
use com\github\tncrazvan\catpaw\tools\Strings;
use com\github\tncrazvan\catpaw\http\HttpHeaders;
use com\github\tncrazvan\catpaw\tools\SharedObject;
use com\github\tncrazvan\catpaw\tools\Status;

class HttpEventListener{

    public const EVENT_TYPE_HTTP = 0;
    public const EVENT_TYPE_WEBSOCKET = 1;
    public const PATTERN_PATH_PARAM = '/(?<=^{)([A-z_][A-z0-9_]+)(?=}$)/';

    //requested query string
    private string $queryString;
    //requested path
    private string $path;
    //requested resource (path + query)
    private string $resource;
    //request headers
    private HttpHeaders $requestHeaders;
    private SharedObject $so;
    private $client;
    private string $hash;
    private bool $completeBody = true;
    private int $continuation = 0;
    private int $requestHeaderBodyLength = 0;
    private int $actualBodyLength = 0;
    private int $failedContinuations = 0;
    private array $properties = [
        "http-consumer" => false
    ];
    private $eventType = -1;
    private bool $httpConsumerStarted = false;
    private int $emptyFails = 0;


    private int $_find_headers_offset = 0;

    public array $params = [];
    public $input = '';
    public $event = null;

    public function __construct(&$client,SharedObject $so) {
        $this->client = $client;
        $this->so = $so;
        $this->hash = \spl_object_hash($this).rand();
    }

    
    public function &getQueryString():string{return $this->queryString;}
    public function issetQueryString():bool{return $this->queryString === '';}
    public function &getPath():string{return $this->path;}
    public function &getResource():string{return $this->resource;}
    public function &getRequestHeaders():HttpHeaders{return $this->requestHeaders;}
    public function &getSharedObject():SharedObject{return $this->so;}
    public function &getHash():string{return $this->hash;}
    public function &getClient(){return $this->client;}
    public function isCompleteBody():bool{return $this->completeBody;}
    public function setCompleteBody(bool $value):void{$this->completeBody = $value;}
    public function setContinuation(int $value):void{$this->continuation = $value;}
    public function getContinuation():int{return $this->continuation;}
    public function increaseContinuation():void{$this->continuation++;}
    public function getRequestHeaderBodyLength():int{return $this->requestHeaderBodyLength;}
    public function getActualBodyLength():int{return $this->actualBodyLength;}
    public function bodyLengthsMatch():bool{return $this->requestHeaderBodyLength === $this->actualBodyLength;}
    public function actualBodyLengthIsMaxed():bool{return $this->actualBodyLength === $this->so->getHttpMaxBodyLength();}
    public function increaseActualBodyLengthByValue(int $value):void{$this->actualBodyLength += $value;}
    public function increaseFailedContinuations():void{$this->failedContinuations++;}
    public function &getProperties():array{return $this->properties;}
    public function issetProperty(string $key):bool{return isset($this->properties[$key]);}
    public function setProperty(string $key,$value):void{$this->properties[$key] = $value;}
    public function &getProperty(string $key){return $this->properties[$key];}
    public function unsetProperty(string $key):void{unset($this->properties[$key]);}
    public function getEventType():int{return $this->eventType;}
    public function setEventType(int $type):void{$this->eventType = $type;}
    public function httpConsumerStarted():bool{return $this->httpConsumerStarted;}
    public function httpConsumerStart():void{$this->httpConsumerStarted = true;}
    public function getEmptyFails():int{return $this->emptyFails;}
    public function setEmptyFails(int $value):void{$this->emptyFails = $value;}
    public function increaseEmptyFails():void{$this->emptyFails++;}

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
        $_EVENT = $this->event;

        //no need to check for exceptions, they will be automatically handled since this is a default http event
        $this->event->initCallback(); 
        $_EVENT->run();
        $_EVENT = null;
        $this->input[1] = null;
        return true;
    }

    public function runHttpLiveBodyInject(&$read):bool{

        if($this->event->generator){

            $error = true;
            try{
                if($this->event->generator->valid()){
                    global $_EVENT;
                    $_EVENT = $this->event;
                    $returnObject = $this->event->generator->current();
                    if($returnObject instanceof HttpConsumer){
                        if($read === false){
                            $returnObject->done();
                        }else{
                            $returnObject->produce($read);
                            $this->event->generator->next();
                        }
                    }
                    $_EVENT = null;
                }else{
                    $this->getSharedObject()->unsetHttpQueueEntry($this->hash);
                    $this->getSharedObject()->setHttpConnectionsEntry($this->event->getRequestId(),$this->event);
                }
                $error = false;
            }catch(\TypeError $ex){
                $this->responseObject = new HttpResponse([
                    "Status"=>Status::INTERNAL_SERVER_ERROR
                ],$ex->getMessage()."\n".$ex->getTraceAsString());
            }catch(HttpEventException $ex){
                $this->responseObject = new HttpResponse([
                    "Status"=>$ex->getStatus()
                ],$ex->getMessage()."\n".$ex->getTraceAsString());
            }catch(\Exception $ex){
                $this->responseObject = new HttpResponse([
                    "Status"=>Status::INTERNAL_SERVER_ERROR
                ],$ex->getMessage()."\n".$ex->getTraceAsString());
            }
            if($error){
                $this->event->funcheck($this->event->responseObject);
                $this->event->dispatch($this->event->responseObject);
                return false;
            }else 
                return true;
        }else 
            return false;
    }

    private static function _forward(array &$paths, HttpEventListener &$listener):void{
        if(!isset($paths["@forward"]))
            return;
            
        $_event_path = \preg_replace('/(?<=^)\/+/','',$listener->path);
        foreach($paths["@forward"] as $route => $to){
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
                    $listener->params[$matches[1]] = $parts[$i];
                    $c++;
                }
                else if($pieces[$i] === $parts[$i])  $c++;
            }

            if($c === $len){
                $pieces = \preg_split('/\//',$to);

                for($i=0,$len = \count($pieces);$i<$len;$i++){
                    $matches = null;
                    if(\preg_match(self::PATTERN_PATH_PARAM,$pieces[$i],$matches)) {
                        $pieces[$i] = $listener->params[$matches[1]];
                    }
                }
                $listener->path = \join('/',$pieces);
            }
        }
        return;





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

    private static function _file(string $method,string &$type, array &$paths, HttpEventListener &$listener, ?\Closure &$callback):bool{
        if($type === 'http'){
            $location = $listener->getSharedObject()->getWebRoot().$listener->path;

            //checking if it's a file
            //if(\file_exists($location) && !\is_dir($location)){
            if(!\is_dir($location)){
                if(is_array($paths["@file"])){
                    if(isset($paths["@file"][$method])){
                        $callback = $paths["@file"][$method];
                        foreach($paths["@file"] as $key =>&$property){
                            if($key === $method) continue;
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


    private static function _event(string $method,array &$paths, HttpEventListener &$listener, ?\Closure &$callback):bool{
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
                    if(isset($cb[$method])){
                        $callback = $cb[$method];
                        foreach($cb as $key =>&$property){
                            if($key === $method) continue;
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
        $method = \strtoupper($listener->getRequestHeaders()->getMethod());
        $paths = &$listener->getSharedObject()->getEvents()[$type];

        

        self::_forward($paths,$listener);

        if($type === 'http' && is_array($paths["@404"]) && isset($paths["@404"][$method])){
            $callback = $paths["@404"][$method];
            foreach($paths["@404"] as $key =>&$property){
                if($key === $method) continue;
                $listener->properties[$key] = $property;
            }
        }else{
            $callback = $paths["@404"];
        }
            

        if(self::_file($method,$type,$paths,$listener,$callback))
            return $callback;

        if(self::_event($method,$paths,$listener,$callback))
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
                $this->requestHeaderBodyLength = intval($this->requestHeaders->get(("Content-Length")));
            }catch(\Exception $ex){
                $this->requestHeaderBodyLength = $this->actualBodyLength;
            }
            catch(\ErrorException $ex){
                $this->requestHeaderBodyLength = $this->actualBodyLength;
            }
            
            if($this->actualBodyLength < $this->requestHeaderBodyLength){
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