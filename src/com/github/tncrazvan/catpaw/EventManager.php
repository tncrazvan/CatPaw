<?php
namespace com\github\tncrazvan\catpaw;

use com\github\tncrazvan\catpaw\http\HttpConsumer;
use com\github\tncrazvan\catpaw\http\HttpEvent;
use com\github\tncrazvan\catpaw\http\HttpHeaders;
use com\github\tncrazvan\catpaw\http\HttpEventOnClose;
use com\github\tncrazvan\catpaw\http\HttpEventListener;
use com\github\tncrazvan\catpaw\http\HttpRequestBody;
use com\github\tncrazvan\catpaw\http\HttpRequestCookies;
use com\github\tncrazvan\catpaw\http\HttpResponseCookies;
use com\github\tncrazvan\catpaw\tools\Caster;
use com\github\tncrazvan\catpaw\tools\formdata\FormData;
use com\github\tncrazvan\catpaw\tools\LinkedList;
use com\github\tncrazvan\catpaw\tools\Strings;
use com\github\tncrazvan\catpaw\websocket\WebSocketEventOnOpen;
use com\github\tncrazvan\catpaw\websocket\WebSocketEventOnClose;
use com\github\tncrazvan\catpaw\websocket\WebSocketEventOnMessage;
use com\github\tncrazvan\catpaw\websocket\WebSocketEvent;


abstract class EventManager{
    protected ?array $requestUrlQueries=[];
    protected ?array $session = null;
    protected HttpHeaders $serverHeaders;
    protected HttpEventListener $listener;
    protected string $requestId;
    protected ?string $sessionId;
    protected bool $alive=true;
    private array $dummy = [];
    protected bool $_consumer_provided = false;
    
    protected ?\Closure $_commit_fn = null;
    protected bool $autocommit = true;

    public function getHttpEventListener():HttpEventListener{return $this->listener;}
    public function getRequestId():string{return $this->requestId;}
    //public function getSessionId():string{return $this->sessionId;}
    //public function consumerIsProvided():bool{return $this->_consumer_provided;}
    //public function getCommitFunction():\Closure{return $this->_commit_fn;}
    //public function isAutocommit():bool{return $this->autocommit;}

    protected $client;

    /**
     * Write to a socket stream.
     * @param contents contents to send.
     */
    function fwrite_stream(&$contents) {
        $fwrite = 0;
        for ($written = 0; $written < \strlen($contents); $written += $fwrite) {
            $fwrite = \fwrite($this->client, \substr($contents, $written));
            if ($fwrite === false) {
                return false;
            }
        }
        return $written;
    }


    /**
     * Check if http consumer is available for this event.
     * @return void
     */
    public function checkHttpConsumer():void{
        $valid = true;
        $meta = new \ReflectionFunction($this->callback);
        $parameters = $meta->getParameters();
        $params = [];
        foreach($parameters as &$parameter){
            $type = $parameter->getType();
            $cls = $type->getName();
            if($cls === HttpConsumer::class){
                if($this instanceof HttpEvent)
                    $this->listener->setProperty("http-consumer", true);
                else
                    $this->listener->setProperty("http-consumer", false);
            }
        }
    }

    protected function &calculateParameters(string &$message, bool &$valid):array{
        $valid = true;
        $meta = new \ReflectionFunction($this->callback);
        $parameters = $meta->getParameters();
        $params = [];
        foreach($parameters as &$parameter){
            $type = $parameter->getType();
            $cls = $type->getName();
            switch($cls){
                case WebSocketEventOnOpen::class:
                    $this->onOpen = new class() extends WebSocketEventOnOpen{
                        public function run():void{
                
                        }
                    };
                    $params[] = &$this->onOpen;
                break;
                case WebSocketEventOnMessage::class:
                    $this->onMessage = new class() extends WebSocketEventOnMessage{
                        public function run(LinkedList &$fragments):void{
                
                        }
                    };
                    $params[] = &$this->onMessage;
                break;
                case WebSocketEventOnClose::class:
                    $this->onClose = new class() extends WebSocketEventOnClose{
                        public function run():void{
                
                        }
                    };
                    $params[] = &$this->onClose;
                break;
                case HttpEventOnClose::class:
                    $this->onClose = new class() extends HttpEventOnClose{
                        public function run():void{
                
                        }
                    };
                    $params[] = &$this->onClose;
                break;
                case WebSocketEvent::class://inject the WebSocketEvent instance
                case HttpEvent::class://inject the HttpEvent instance
                    $params[] = &$this;
                break;
                case HttpRequestCookies::class://inject the HttpEvent instance
                    $params[] = &HttpRequestCookies::factory($this);
                break;
                case HttpResponseCookies::class://inject the HttpEvent instance
                    $params[] = &HttpResponseCookies::factory($this);
                break;
                case HttpConsumer::class:
                    if($this instanceof HttpEvent)
                        $this->listener->setProperty("http-consumer", true);
                    else
                        $this->listener->setProperty("http-consumer", false);
                        
                    $this->_consumer_provided = true;
                    static $param = null;
                    $param = new HttpConsumer();
                    $params[] = &$param;
                break;
                case \Closure::class:
                    $name = $parameter->getName();
                    if('commit' === $name)
                        $params[] = $this->_commit_fn;
                    else
                        $params[] = function(){};
                break;
                case 'string':
                    $name = $parameter->getName();
                    static $param = null;
                    switch($name){
                        case 'method':
                            $param = &$this->getRequestMethod();
                        break;
                        case 'body':
                            $param = &$this->listener->input[1];
                        break;
                        default:
                            if(isset($this->listener->params[$name]))
                                $param = &$this->listener->params[$name];
                            else
                                $param = null;
                        break;
                    }
                    $params[] = &$param;
                break;
                case 'int':
                    $name = $parameter->getName();
                    static $param = null;
                    switch($name){
                        case 'body':
                            if(\is_numeric($this->listener->input[1]))
                                $param = \intval($this->listener->input[1]);
                            else{
                                $message = 'Body was expected to be numeric, but non numeric value has been provided instead:'.$this->listener->input[1];
                                $valid = false;
                                return $this->dummy;
                            }
                        break;
                        default:
                            if(isset($this->listener->params[$name]))
                                if(\is_numeric($this->listener->params[$name]))
                                    $param = \intval($this->listener->params[$name]);
                                else{
                                    $message = 'Parameter {'.$name.'} was expected to be numeric, but non numeric value has been provided instead:'.$this->listener->params[$name];
                                    $valid = false;
                                    return $this->dummy;
                                }
                            else{
                                $param = null;
                            }
                        break;
                    }
                    $params[] = &$param;
                break;
                case 'bool':
                    $name = $parameter->getName();
                    static $param = null;
                    $param = \filter_var($this->listener->params[$name], FILTER_VALIDATE_BOOLEAN);
                    $params[] = &$param;
                break;
                case 'float':
                    $name = $parameter->getName();
                    static $param = null;
                    $param = (float) $this->listener->params[$name];
                    $params[] = &$param;
                break;
                case 'array':
                    $name = $parameter->getName();
                    static $param = null;
                    switch ($name) {
                        case 'session':
                            $param = &$this->startSession();
                        break;
                        case 'body':
                            try{
                                if($this->listener->input[1] === '')
                                    $param = [];
                                else
                                    $param = &$this->getRequestParsedBody(null,true);
                            }catch(\Exception $e){
                                echo "Could not convert body to '$cls', injecting null value.\n";
                                echo 
                                    $e->getMessage()
                                    ."\n"
                                    .$e->getTraceAsString()."\n";
                            }
                        break;
                    }
                    $params[] = &$param;
                break;
                default://unknown type, check if it's path parameter, otherwise inject null
                    static $param = null;
                    $reflectionClass = new \ReflectionClass($cls);
                    if($reflectionClass->isSubclassOf(HttpRequestBody::class)){
                        try{
                            $param = &$this->getRequestParsedBody($cls);
                        }catch(\Exception $e){
                            echo "Could not convert body to '$cls', injecting null value.\n";
                            echo 
                                $e->getMessage()
                                ."\n"
                                .$e->getTraceAsString()."\n";
                        }
                    }
                    $params[] = &$param;
                break;
            }
        }
            
        return $params;
    }

    /**
     * Get the parsed body of the request.
     * @param classname classname the function will attempt to convert the body to.
     * @param toarray if true and classname is null, the function will attempt to convert the body to an array.
     * @return mixed request body parsed as an object or array.
     */
    public function &getRequestParsedBody(?string $classname=null,bool $toarray = false){
        $ctype = $this->getRequestHeader("Content-Type");
        if($ctype === null){
            $result = null;
            return $result;
        }else if($classname !== null){
            if(Strings::startsWith($ctype,"application/x-www-form-urlencoded")){
                \mb_parse_str($this->listener->input[1],$result);
            }else if(Strings::startsWith($ctype,"application/json")){
                $result = \json_decode($this->listener->input[1]);
            }else if(Strings::startsWith($ctype,"multipart/")){
                $result = null;
                FormData::parse($this,$this->listener->input[1],$result);
            }else{
                echo "No matching Content-Type ($ctype), falling back to null.\n";
                $result = null;
                return $result;
            }
            $result = &Caster::cast($result,$classname);
            return $result;
        }else if($toarray) try {
            if(Strings::startsWith($ctype,"application/x-www-form-urlencoded")){
                \mb_parse_str($this->listener->input[1],$result);
                return $result;
            }else if(Strings::startsWith($ctype,"application/json")){
                $result = \json_decode($this->listener->input[1]);
                return $result;
            }else if(Strings::startsWith($ctype,"multipart/")){
                FormData::parse($this,$this->listener->input[1],$result);
                return $result;
            }else{
                echo "No matching Content-Type ($ctype), falling back to empty array.\n";
                $result = [];
                return $result;
            }
        }catch(\Exception $e){
            echo "Could not convert body to array, falling back to empty array.\n";
            $result = [];
            return $result;
        }
    }

    /**
     * Uninstall this EventManager, removing its request url queries.
     * @return void
     */
    public function uninstall():void{
        $this->requestUrlQueries = [];
    }

    /**
     * Install this EventManager, assigning it a request id, an HttpEventListener, 
     * a client resource, response headers and initializing its url queries.
     * @return void
     */
    public function install(HttpEventListener &$listener):void{
        $this->requestId = \spl_object_hash($this).rand();
        $this->listener = $listener;
        $this->client = $listener->getClient();
        $this->serverHeaders = new HttpHeaders($this);
        $queries=[];
        $object=[];
        
        if($listener->issetQueryString()){
            $queries = \preg_split("/\\&/",$listener->getQueryString());
            foreach ($queries as &$query){
                $object = \preg_split("/=/m",$query);
                $objectLength = \count($object);
                if($objectLength > 1){
                    $this->requestUrlQueries[\trim($object[0])] = $object[1];
                }else{
                    $this->requestUrlQueries[\trim($object[0])] = true;
                }
            }
        }
    }
    
    /**
     * Closes the client connection.
     * @return void This method WILL invoke the "onClose" callback.
     */
     public function close():void{
        if(!$this->alive) return;
        $this->alive = false;
        $outcome = \fclose($this->client);
        if($this->onClose !== null)
            $this->onClose->run();
        //@socket_set_option($this->clistener->lient, SOL_SOCKET, SO_LINGER, array('l_onoff' => 1, 'l_linger' => 1));
        //@socket_close($this->listener->client);
        if($this->session !== null) 
            $this->listener
                    ->getSharedObject()
                        ->getSessions()
                            ->saveSession($this,$this->listener
                                                        ->getSharedObject()
                                                            ->getSessions()
                                                                ->getSession($this->sessionId));
    }

    /**
     * Get client ip address.
     * @return string the ip address of the client.
     */
    public function &getAddress():string{
        $host = \stream_socket_get_name($this->client,true);
        $hostname = \preg_replace("/:[0-9]*/","",$host);
        return $hostname;
    }
    
    /**
     * Get client port number.
     * @return int the port number of the client
     */
    public function getPort():int{
        $host = \stream_socket_get_name($this->client,true);
        $port = \preg_replace("/.*:/","",$host);
        return \intval($port);
    }
    

    /**
     * Check if your response headers contain a specific field.
     * @param key the name of the header field you want to check for.
     * @param bool true if the header field is set,  otherwise false.
     */
    public function issetResponseHeader(string $key):bool{
        return $this->serverHeaders->has($key);
    }

    /**
     * Set a field to your response headers.
     * @param key name of the field
     * @param content content of the field
     * @return void
     */
    public function setResponseHeader(string $key, string $content):void{
        $this->serverHeaders->set($key,$content);
    }


    /**
     * Set the headers of the event.
     * @param header replacement headers object.
     * @return void
     */
    public function setResponseHttpHeaders(HttpHeaders &$headers):void{
        $this->serverHeaders = $headers;
    }
        
    /**
     * Set the status of your response.
     * @param status a status string (a repository of status strings can be found in the Status class)..
     * @return void
     */
    public function setResponseStatus(string $status):void{
        $this->serverHeaders->setStatus($status);
    }
    
    /**
     * Set the Content-Type field to the response headers object.
     * @param type content type string, such as "text/plain", "text/html" ecc...
     * @return void
     */
    public function setResponseContentType(string $type):void{
        $this->setResponseHeader("Content-Type", $type);
    }

    /**
     * Get your response headers object.
     * @return HttpHeaders header of the your response message.
     */
    public function &getResponseHttpHeaders():HttpHeaders{
        return $this->serverHeaders;
    }

    /**
     * Get a header field from your response headers.
     * @param key name of the header field.
     * @return string headers of your response.
     */
    public function &getResponseHeader(string $key):string{
        return $this->serverHeaders->get($key);
    }
    
    /**
     * Get the client socket resource.
     * @return resource the socket of the client.
     */
    public function getClient(){
        return $this->client;
    }
    
    /**
     * Get the request headers object.
     * @return HttpHeaders headers of the client request.
     */
    public function &getRequestHttpHeaders():HttpHeaders{
        return $this->listener->getRequestHeaders();
    }

    /**
     * Get a specific request header field by key.
     * @param key name of the header field.
     * @return null|string the value of the header field if it exists, otherwise null.
     */
    public function &getRequestHeader(string $key):?string{
        return $this->listener->getRequestHeaders()->get($key);
    }
    
    /**
     * Get the request method name.
     * @return string name of the method (always uppercase).
     */
    public function &getRequestMethod():string{
        return $this->listener->getRequestHeaders()->getMethod();
    }
    
    /**
     * Get the user agent of the client.
     * @return string user agent string.
     */
    public function &getRequestUserAgent():string{
        return $this->getRequestHeader("User-Agent");
    }
    
    /**
     * Starts a client http session.
     * @return array This method returns an array pointer of the session.
     * Any changes made to the array will be saved across all http requests relative to this session, 
     * untill the server kills the session due to inactivity. 
     * The default session ttl is 24 minutes.
     * NOTE: changes to the session array won't be saved unless you request the pointer of the array.
     */
    public function &startSession():array{
        $this->session = &$this->listener->getSharedObject()->getSessions()->startSession($this, $this->sessionId);
        return $this->session;
    }
    
    /**
     * Removes the session of the current client from the server sessions list.
     * @return void No need to call HttpEventManager::startSession, this method will 
     * call it automatically if needed.
     */
    public function stopSession():void{
        if($this->sessionId === null){
            $this->startSession();
        }
        $this->session = null;
        $this->listener->getSharedObject()->getSessions()->stopSession($this,$this->listener->getSharedObject()->getSessions()->getSession($this->sessionId));
    }
    
    /**
     * Checks if the current client can find a session.
     * @return bool true if the client has a "sessionId" cookie and its value exists in the server sessions list, otherwise false.
     */
    public function issetSession():bool{
        if($this->session === null) return false;
        $sessionID = null;
        return $this->listener->getSharedObject()->getSessions()->issetSession($this,$sessionID);
    }
    
    /**
     * Checks if the request contains the given url query key.
     * @param key name of the query.
     * @return bool true if
     */
    public function issetRequestQueryString(string $key):bool{
        return isset($this->requestUrlQueries[$key]);
    }
    
    /**
     * Get the request url query by key.
     * @param key name of the query.
     * @return string the value of the query if it exists.
     */
    public function &getRequestQueryString(string $key):?string{
        return $this->requestUrlQueries[$key];
    }

    /**
     * Get all the url queries of the request.
     * @return array the request url queries array.
     */
    public function &getRequestQueryStrings():array{
        return $this->requestUrlQueries;
    }
    
    /**
     * Notices the client to unset the given cookie.
     * @param key name of the cookie
     * @param path path of the cookie
     * @param domain domain of the cookie
     */
    public function unsetResponseCookie(string $key, ?string $path="/", ?string $domain=null):void{
        $this->serverHeaders->setCookie($key, "",$path,$domain,"0");
    }
    
    /**
     * Notices the client to set the given cookie.
     * @param name name of the cookie.
     * @param value value of the cookie.
     * @param path path of the cookie.
     * @param domain domain of the cooke.
     * @param expire time to live of the cookie.
     */
    public function setResponseCookie(string $key, string $content, ?string $path='/', ?string $domain=null, ?string $expire=null):void{
        $this->serverHeaders->setCookie($key, $content, $path, $domain, $expire);
    }
    
    /**
     * Gets the value of the cookie.
     * @param name name of the cookie.
     * @return value of the cookie.
     */
    public function getRequestCookie(string $key):string{
        return $this->listener->getRequestHeaders()->getCookie($key);
    }

    /**
     * Get the cookies of the current request.
     * @return array of cookies
     */
    public function &getRequestCookies():array{
        return $this->listener->getRequestHeaders()->getCookies();
    }

    /**
     * Get the cookies of the current response.
     * @return array of cookies
     */
    public function &getResponseCookies():array{
        return $this->serverHeaders->getCookies();
    }
    
    /**
     * Checks if a specific request cookie is set.
     * @param key name of the cookie.
     */
    public function issetRequestCookie(string $key):bool{
        return $this->listener->getRequestHeaders()->issetCookie($key);
    }
}