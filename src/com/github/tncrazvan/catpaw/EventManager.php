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
    public ?array $queryString=[];
    public ?array $session = null;
    public HttpHeaders $serverHeaders;
    public HttpEventListener $listener;
    public string $requestId;
    public ?string $sessionId;
    public bool $alive=true;
    private array $dummy = [];
    protected bool $_consumer_provided = false;
    
    public ?\Closure $_commit_fn = null;
    protected bool $autocommit = true;

    public function checkHttpConsumer(){
        $valid = true;
        $meta = new \ReflectionFunction($this->callback);
        $parameters = $meta->getParameters();
        $params = [];
        foreach($parameters as &$parameter){
            $type = $parameter->getType();
            $cls = $type->getName();
            if($cls === HttpConsumer::class){
                if($this instanceof HttpEvent)
                    $this->listener->properties["http-consumer"] = true;
                else
                    $this->listener->properties["http-consumer"] = false;
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
                        $this->listener->properties["http-consumer"] = true;
                    else
                        $this->listener->properties["http-consumer"] = false;
                        
                    $this->_consumer_provided = true;
                    static $param = null;
                    $param = new HttpConsumer();
                    $params[] = &$param;
                break;
                case \Closure::class:
                    $params[] = $this->_commit_fn;
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
                            if($this->listener->params[$name])
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
                            if($this->listener->params[$name])
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

    public function &getRequestParsedBody(string $classname=null,bool $toarray = false){
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


    public function delegate(string $key,... $params){
        $fun = $this->listener->so->events["http"][$key];
        return \call_user_func_array($fun,$params);
    }

    public function uninstall():void{
        $this->queryString = [];
    }

    public function install(HttpEventListener &$listener):void{
        $this->requestId = \spl_object_hash($this).rand();
        $this->listener = $listener;
        $this->serverHeaders = new HttpHeaders($this);
        $queries=[];
        $object=[];
        
        if($listener->queryString !== ''){
            $queries = \preg_split("/\\&/",$listener->queryString);
            foreach ($queries as &$query){
                $object = \preg_split("/=/m",$query);
                $objectLength = \count($object);
                if($objectLength > 1){
                    $this->queryString[\trim($object[0])] = $object[1];
                }else{
                    $this->queryString[\trim($object[0])] = true;
                }
            }
        }
    }
    
    /**
     * Closes the client connection.
     * @return void This method WILL invoke the "onClose" method.
     */
     public function close():void{
        if(!$this->alive) return;
        $this->alive = false;
        $outcome = \fclose($this->listener->client);
        if($this->onClose !== null)
            $this->onClose->run();
        //@socket_set_option($this->clistener->lient, SOL_SOCKET, SO_LINGER, array('l_onoff' => 1, 'l_linger' => 1));
        //@socket_close($this->listener->client);
        if($this->session !== null) 
            $this->listener->so->sessions->saveSession($this,$this->listener->so->sessions->getSession($this->sessionId));
    }

    /**
     * Get client ip address
     * @return string the ip address of the client
     */
    public function &getAddress():string{
        $host = \stream_socket_get_name($this->listener->client,true);
        $hostname = \preg_replace("/:[0-9]*/","",$host);
        return $hostname;
    }
    
    /**
     * Get client port number.
     * @return string the port number of the client
     */
    public function getPort():int{
        $host = \stream_socket_get_name($this->listener->client,true);
        $port = \preg_replace("/.*:/","",$host);
        return \intval($port);
    }
    

    /**
     * Check if your response headers contain a specific field
     * @param string $key name of the field
     */
    public function issetResponseHeader(string $key):bool{
        return $this->serverHeaders->has($key);
    }

    /**
     * Set a field to your response header.
     * @param string $key name of the field
     * @param string $content content of the field
     */
    public function setResponseHeader(string $key, string $content):void{
        $this->serverHeaders->set($key,$content);
    }

    /**
     * Set the header of the event.
     * @return void
     */
    public function setResponseHttpHeaders(HttpHeaders &$header):void{
        $this->serverHeaders = $header;
    }
        
    /**
     * Set the status of your response.
     * @param string $status a status code. Multiple status codes can be found in the Cat class, suche as Server::STATUS_SUCCESS.
     */
    public function setResponseStatus(string $status):void{
        $this->serverHeaders->setStatus($status);
    }
    
    /**
     * Set the Content-Type field to the response header.
     * @param string $type content type string, such as "text/plain", "text/html" ecc...
     */
    public function setResponseContentType(string $type):void{
        $this->setResponseHeader("Content-Type", $type);
    }

    /**
     * Get response header.
     * @return \com\github\tncrazvan\catpaw\http\httpHeaders header of the your response message.
     */
    public function &getResponseHttpHeaders():HttpHeaders{
        return $this->serverHeaders;
    }

    /**
     * Get header field.
     * @param string $key name of the header field.
     * @return string value of the header field.
     */
    public function &getResponseHeader(string $key):string{
        return $this->serverHeaders->get($key);
    }
    
    /**
     * Get client socket
     * @return \resource This is the socket of the client.
     */
    public function &getClient(){
        return $this->listener->client;
    }
    
    /**
     * Get request header.
     * @return \com\github\tncrazvan\catpaw\http\httpHeaders header of the client request.
     */
    public function &getRequestHttpHeaders():HttpHeaders{
        return $this->listener->requestHeaders;
    }

    public function &getRequestHeader(string $key){
        return $this->listener->requestHeaders->get($key);
    }
    
    /**
     * Get request method.
     * @return string method of the client request.
     */
    public function &getRequestMethod(){
        return $this->getRequestHeader("Method");
    }
    
    /**
     * Get the user agent of the client.
     * @return &string
     */
    public function &getRequestUserAgent():string{
        return $this->getRequestHeader("User-Agent");
    }
    
    /**
     * Starts a client http session.
     * @return &array This method returns an array pointer, so any changes made to the array will be saved across all http requests relative to this session, untill the server kills the session due to inactivity. The default session ttl is 24 minutes.
     */
    public function &startSession():array{
        $this->session = &$this->listener->so->sessions->startSession($this, $this->sessionId);
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
        $this->listener->so->sessions->stopSession($this,$this->listener->so->sessions->getSession($this->sessionId));
    }
    
    /**
     * Checks if the current client can find a session.
     * @return bool true if the client has "sessionId" cookie and its value exists in the server sessions list, otherwise false.
     */
    public function issetSession():bool{
        if($this->session === null) return false;
        $sessionID = null;
        return $this->listener->so->sessions->issetSession($this,$sessionID);
    }
    
    /**
     * Checks if the requested URL contains the given key as a query.
     * @param key name of the query.
     * @return 
     */
    public function issetRequestUrlQuery(string $key):bool{
        return isset($this->queryString[$key]);
    }
    
    /**
     * 
     * @param key name of the query.
     * @return the value of the query.
     */
    public function &getRequestUrlQuery(string $key):?string{
        return $this->queryString[$key];
    }

    /**
     * @return the array queries pointer
     */
    public function &getRequestUrlQueries():array{
        return $this->queryString;
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
        return $this->listener->requestHeaders->getCookie($key);
    }

    /**
     * Get the cookies of the current request.
     * @return array of cookies
     */
    public function &getRequestCookies():array{
        return $this->listener->requestHeaders->getCookies();
    }

    /**
     * Get the cookies of the current response.
     * @return array of cookies
     */
    public function &getResponseCookies():array{
        return $this->listener->serverHeaders->getCookies();
    }
    
    /**
     * Checks if the cookie is set.
     * @param key name of the cookie.
     */
    public function issetRequestCookie(string $key):bool{
        return $this->listener->requestHeaders->issetCookie($key);
    }
}