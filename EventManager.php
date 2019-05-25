<?php
namespace com\github\tncrazvan\CatPaw\Http;

use com\github\tncrazvan\CatPaw\Tools\G;
use com\github\tncrazvan\CatPaw\Http\HttpHeader;
use com\github\tncrazvan\CatPaw\Http\HttpSessionManager;

class EventManager extends G{
    
    protected 
            $client,
            $clientHeader,
            $location,
            $alive=true,
            $userLanguages=[],
            $queryString=[],
            $serverHeader,
            $session = null,
            $sessionId = null;
    
    public function __construct(&$client,HttpHeader $clientHeader) {
        $this->client=$client;
        $this->serverHeader = new HttpHeader();
        $this->clientHeader = $clientHeader;
        $parts = preg_split("/\\?|\\&/m",preg_replace("/^\\//m","",urldecode($clientHeader->get("Resource"))));
        $tmp=[];
        $object=[];
        $this->location = $parts[0];
        $partsLength = count($parts);
        
        if($partsLength > 1){
            $tmp = preg_split("/\\&/",$parts[1]);
            foreach ($tmp as &$part){
                $object = preg_split("/=/m",$part);
                $objectLength = count($object);
                if($objectLength > 1){
                    $this->queryString[trim($object[0])] = $object[1];
                }else{
                    $this->queryString[trim($object[0])] = "";
                }
            }
        }
    }
    
    public function run(){}


    protected static function getClassNameIndex(string $root, array &$location):int{
        $classname = $root;
        $locationLength = count($location);
        for($i=0;$i<$locationLength;$i++){
            $classname .="\\".$location[$i];
            if(class_exists($classname)){
                return $i;
            }
        }
        return -1;
    }
    
    protected static function resolveClassName(int $classId, string $root, array &$location):string{
        $classname = $root;
        for($i=0;$i<=$classId;$i++){
            $classname .="\\".$location[$i];
        }
        return $classname;
    }
    
    protected static function resolveMethodArgs(int $offset, array &$location):array{
        $args = [];
        $locationLength = count($location);
        if($locationLength-1>$offset-1){
            $args = array_slice($args, $offset);
        }
        return $args;
    }
    
    /**
     * Get client ip address
     * @return string the ip address of the client
     */
    public function &getAddress():string{
        $host = stream_socket_get_name($this->client,true);
        $hostname = preg_replace("/:[0-9]*/","",$host);
        return $hostname;
    }
    
    /**
     * Get client port number.
     * @return string the port number of the client
     */
    public function &getPort():int{
        $host = stream_socket_get_name($this->client,true);
        $port = preg_replace("/.*:/","",$host);
        return intval($port);
    }
    
    
    /**
     * Closes the client connection.
     * @return void This method WILL NOT invoke the "onClose" method.
     */
    public function close():void{
        @socket_set_option($this->client, SOL_SOCKET, SO_LINGER, array('l_onoff' => 1, 'l_linger' => 1));
        @socket_close($this->client);
        if($this->session !== null) HttpSessionManager::saveSession (HttpSessionManager::getSession($this->sessionId));
    }
    
    
    /**
     * Set a field to your response header.
     * @param string $key name of the field
     * @param string $content content of the field
     */
    public function setHeaderField(string $key, string $content):void{
        $this->serverHeader->set($key,$content);
    }
        
    /**
     * Set the status of your response.
     * @param string $status a status code. Multiple status codes can be found in the Cat class, suche as G::STATUS_SUCCESS.
     */
    public function setStatus(string $status):void{
        $this->setHeaderField("Status", "HTTP/1.1 $status");
    }
    

    /**
     * Get response header.
     * @return \com\github\tncrazvan\CatPaw\Http\HttpHeader header of the your response message.
     */
    public function &getHeader():HttpHeader{
        return $this->serverHeader;
    }

    /**
     * Get header field.
     * @param string $key name of the header field.
     * @return string value of the header field.
     */
    public function &getHeaderField(string $key):string{
        return $this->serverHeader->get($key);
    }
    
    /**
     * Get client socket
     * @return \resource This is the socket of the client.
     */
    public function &getClient(){
        return $this->client;
    }
    
    /**
     * Get request header.
     * @return \com\github\tncrazvan\CatPaw\Http\HttpHeader header of the client request.
     */
    public function &getClientHeader():HttpHeader{
        return $this->clientHeader;
    }

    public function &getClientHeaderField(string $key):string{
        return $this->clientHeader->get($key);
    }
    
    /**
     * Get request method.
     * @return string method of the client request.
     */
    public function &getClientMethod():string{
        return $this->getClientHeaderField("Method");
    }
    
    public function &getUserLanguages():array{
        return $this->userLanguages;
    }
    /**
     * Get the default user language from the request header.
     * @return &string
     */
    public function &getUserDefaultLanguage():string{
        return $this->userLanguages["DEFAULT-LANGUAGE"];
    }
    
    /**
     * Get the user agent of the client.
     * @return &string
     */
    public function &getUserAgent():string{
        return $this->getClientHeader()->get("User-Agent");
    }
    
    /**
     * Starts a client http session.
     * @return &array This method returns an array pointer, so any changes made to the array will be saved across all http requests relative to this session, untill the server kills the session due to inactivity. The default session ttl is 24 minutes.
     */
    public function &startSession():array{
        $this->session = &HttpSessionManager::startSession($this, $this->sessionId);
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
        HttpSessionManager::stopSession(HttpSessionManager::getSession($this->sessionId));
    }
    
    /**
     * Checks if the current client can find a session.
     * @return bool true if the client has "sessionId" cookie and its value exists in the server sessions list, otherwise false.
     */
    public function issetSession():bool{
        if($this->session === null) return false;
        return HttpSessionManager::issetSession($e);
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
    public function getUrlQuery(string $key):string{
        return $this->queryString[$key];
    }

    /**
     * @return the array queries pointer
     */
    public function &getUrlQueries():array{
        return $this->queryString;
    }
    
    /**
     * Finds the languages of the client application.
     * The value is stored in EventManager#userLanguages.
     */
    protected function findUserLanguages():void{
        if($this->clientHeader->get("Accept-Language") === null){
            $this->userLanguages["unknown"]="unknown";
        }else{
            //prepare array
            $tmp = array_fill(0, 2, null);
            $languages = preg_split("/,/",$this->clientHeader->get("Accept-Languages"));
            $this->userLanguages["DEFAULT-LANGUAGE"]=$languages[0];
            foreach($languages as &$language){
                $tmp = preg_split("/;/",$language);
                if(count($tmp) > 1)
                    $this->userLanguages[$tmp[0]] = $tmp[1];
                else
                    $this->userLanguages["unknown"]="unknown";
            }
        }
    }
    
    /**
     * Notices the client to unset the given cookie.
     * @param key name of the cookie
     * @param path path of the cookie
     * @param domain domain of the cookie
     */
    public function unsetCookie(string $key, string $path="/", string $domain=null):void{
        $this->serverHeader->setCookie($key, "",$path,$domain,"0");
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
        $this->serverHeader->setCookie($key, $content, $path, $domain, $expire);
    }
    
    /**
     * Gets the value of the cookie.
     * @param name name of the cookie.
     * @return value of the cookie.
     */
    public function &getCookie(string $key):string{
        return $this->clientHeader->getCookie($key);
    }
    
    /**
     * Checks if the cookie is set.
     * @param key name of the cookie.
     */
    public function issetCookie(string $key){
        return $this->clientHeader->issetCookie($key);
    }
}