<?php
namespace com\github\tncrazvan\catpaw\http;

use com\github\tncrazvan\catpaw\http\HttpEventListener;
use com\github\tncrazvan\catpaw\http\HttpHeaders;


class EventManager{
    
    public 
        $alive=true,
        $queryString=[],
        $serverHeaders=[],
        $session = null,
        $sessionId = null,
        $listener,
        $requestId;
    
    public function install(HttpEventListener &$listener):void{
        $this->requestId = spl_object_hash($this).rand();
        $this->listener = $listener;
        $this->serverHeaders = new HttpHeaders($this);
        $queries=[];
        $object=[];
        
        if($listener->resourceLen > 1){
            $queries = preg_split("/\\&/",$listener->resource[1]);
            foreach ($queries as &$query){
                $object = preg_split("/=/m",$query);
                $objectLength = count($object);
                if($objectLength > 1){
                    $this->queryString[trim($object[0])] = $object[1];
                }else{
                    $this->queryString[trim($object[0])] = "";
                }
            }
        }
        //$this->findUserLanguages();
    }


    protected static function getClassNameIndex(string &$root, array &$location,int $len):int{
        $classname = $root;
        for($i=0;$i<$len;$i++){
            $classname .="\\".$location[$i];
            if(class_exists($classname,true)){
                return $i;
            }
        }
        return -1;
    }
    
    protected static function resolveClassName(string &$root, int $classId, array &$location):string{
        $classname = $root;
        for($i=0;$i<=$classId;$i++){
            $classname .="\\".$location[$i];
        }
        return trim($classname);
    }
    
    protected static function &resolveMethodArgs(int $offset, array &$location, int $len):array{
        $args = [];
        if($len-1>$offset-1){
            $args = array_slice($location, $offset);
        }
        return $args;
    }
    
    /**
     * Closes the client connection.
     * @return void This method WILL NOT invoke the "onClose" method.
     */
     public function close():void{
        $this->alive = false;
        fclose($this->listener->client);
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
        $host = stream_socket_get_name($this->listener->client,true);
        $hostname = preg_replace("/:[0-9]*/","",$host);
        return $hostname;
    }
    
    /**
     * Get client port number.
     * @return string the port number of the client
     */
    public function getPort():int{
        $host = stream_socket_get_name($this->listener->client,true);
        $port = preg_replace("/.*:/","",$host);
        return intval($port);
    }
    

    /**
     * Set a field to your response header.
     * @param string $key name of the field
     * @param string $content content of the field
     */
    public function hasHeadersField(string $key):bool{
        return $this->serverHeaders->has($key);
    }

    /**
     * Set a field to your response header.
     * @param string $key name of the field
     * @param string $content content of the field
     */
    public function setHeadersField(string $key, string $content):void{
        $this->serverHeaders->set($key,$content);
    }

    /**
     * Set the header of the event.
     * @return void
     */
    public function setHeaders(HttpHeaders &$header):void{
        $this->serverHeaders = $header;
    }
        
    /**
     * Set the status of your response.
     * @param string $status a status code. Multiple status codes can be found in the Cat class, suche as Server::STATUS_SUCCESS.
     */
    public function setStatus(string $status):void{
        $this->setHeadersField("Status", "HTTP/1.1 $status");
    }
    
    /**
     * Set the Content-Type field to the response header.
     * @param string $type content type string, such as "text/plain", "text/html" ecc...
     */
    public function setContentType(string $type):void{
        $this->setHeadersField("Content-Type", $type);
    }

    /**
     * Get response header.
     * @return \com\github\tncrazvan\catpaw\http\httpHeaders header of the your response message.
     */
    public function &getHeaders():HttpHeaders{
        return $this->serverHeaders;
    }

    /**
     * Get header field.
     * @param string $key name of the header field.
     * @return string value of the header field.
     */
    public function &getHeadersField(string $key):string{
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
    public function &getRequestHeaders():HttpHeaders{
        return $this->listener->requestHeaders;
    }

    public function getRequestHeadersField(string $key){
        return $this->listener->requestHeaders->get($key);
    }
    
    /**
     * Get request method.
     * @return string method of the client request.
     */
    public function getRequestMethod(){
        return $this->getRequestHeadersField("Method");
    }
    
    /**
     * Get the user agent of the client.
     * @return &string
     */
    public function getUserAgent():string{
        return $this->getRequestHeaders()->get("User-Agent");
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
    public function issetUrlQuery(string $key):bool{
        return isset($this->queryString[$key]);
    }
    
    /**
     * 
     * @param key name of the query.
     * @return the value of the query.
     */
    public function &getUrlQuery(string $key):string{
        return $this->queryString[$key];
    }

    /**
     * @return the array queries pointer
     */
    public function &getUrlQueries():array{
        return $this->queryString;
    }
    
    /**
     * Notices the client to unset the given cookie.
     * @param key name of the cookie
     * @param path path of the cookie
     * @param domain domain of the cookie
     */
    public function unsetCookie(string $key, string $path="/", string $domain=null):void{
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
    public function setCookie(string $key, string $content, string $path="/", string $domain=null, string $expire=null):void{
        $this->serverHeaders->setCookie($key, $content, $path, $domain, $expire);
    }
    
    /**
     * Gets the value of the cookie.
     * @param name name of the cookie.
     * @return value of the cookie.
     */
    public function getCookie(string $key):string{
        return $this->listener->requestHeaders->getCookie($key);
    }
    
    /**
     * Checks if the cookie is set.
     * @param key name of the cookie.
     */
    public function issetCookie(string $key):bool{
        return $this->listener->requestHeaders->issetCookie($key);
    }
}