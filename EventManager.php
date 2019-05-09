<?php
namespace com\github\tncrazvan\CatServer\Http;
use com\github\tncrazvan\CatServer\Cat;
class EventManager extends Cat{
    
    protected 
            $client,
            $client_header,
            $location,
            $alive=true,
            $user_languages=[],
            $query_string=[],
            $server_header,
            $session = null,
            $session_id = null;
    
    public function __construct(&$client,HttpHeader $client_header) {
        $this->client=$client;
        $this->server_header = new HttpHeader();
        $this->client_header = $client_header;
        $parts = preg_split("/\\?|\\&/m",preg_replace("/^\\//m","",urldecode($client_header->get("Resource"))));
        $tmp=[];
        $object=[];
        $this->location = $parts[0];
        $parts_length = count($parts);
        
        if($parts_length > 1){
            $tmp = preg_split("/\\&/",$parts[1]);
            foreach ($tmp as &$part){
                $object = preg_split("/=/m",$part);
                $object_length = count($object);
                if($object_length > 1){
                    $this->query_string[trim($object[0])] = $object[1];
                }else{
                    $this->query_string[trim($object[0])] = "";
                }
            }
        }
    }
    
    public function run(){}


    protected static function getClassNameIndex(string $root, array &$location):int{
        $classname = $root;
        $location_length = count($location);
        for($i=0;$i<$location_length;$i++){
            $classname .="\\".$location[$i];
            if(class_exists($classname)){
                return $i;
            }
        }
        return -1;
    }
    
    protected static function resolveClassName(int $class_id, string $root, array &$location):string{
        $classname = $root;
        for($i=0;$i<=$class_id;$i++){
            $classname .="\\".$location[$i];
        }
        return $classname;
    }
    
    protected static function resolveMethodArgs(int $offset, array &$location):array{
        $args = [];
        $location_length = count($location);
        if($location_length-1>$offset-1){
            $args = array_slice($args, $offset);
        }
        return $args;
    }
    
    /**
     * Get client ip address
     * @return string the ip address of the client
     */
    public function &getAddress():string{
        socket_getpeername($this->client, $address);
        return $address;
    }
    
    /**
     * Get client port number.
     * @return string the port number of the client
     */
    public function &getPort():string{
        socket_getpeername($this->client, $address,$port);
        return $port;
    }
    
    
    /**
     * Closes the client connection.
     * @return void This method WILL NOT invoke the "onClose" method.
     */
    public function close():void{
        socket_set_option($this->client, SOL_SOCKET, SO_LINGER, array('l_onoff' => 1, 'l_linger' => 1));
        socket_close($this->client);
        if($this->session !== null) HttpSessionManager::saveSession (HttpSessionManager::getSession($this->session_id));
    }
    /**
     * Get client socket
     * @return \resource This is the socket of the client.
     */
    public function &getClient(){
        return $this->client;
    }
    
    /**
     * Set a field to your response header.
     * @param string $key name of the field
     * @param string $content content of the field
     */
    public function setHeaderField(string $key, string $content):void{
        $this->server_header->set($key,$content);
    }
        
    /**
     * Set the status of your response.
     * @param string $status a status code. Multiple status codes can be found in the Cat class, suche as Cat::STATUS_SUCCESS.
     */
    public function setStatus(string $status):void{
        $this->setHeaderField("Status", "HTTP/1.1 $status");
    }
    
    /**
     * Get header field.
     * @param string $key name of the header field.
     * @return string value of the header field.
     */
    public function &getHeaderField(string $key):string{
        return $this->server_header->get($key);
    }
    
    /**
     * Get response header.
     * @return \com\github\tncrazvan\CatServer\Http\HttpHeader header of the your response message.
     */
    public function &getHeader():HttpHeader{
        return $this->server_header;
    }
    
    /**
     * Get request header.
     * @return \com\github\tncrazvan\CatServer\Http\HttpHeader header of the client request.
     */
    public function &getClientHeader():HttpHeader{
        return $this->client_header;
    }
    
    /**
     * Get request method.
     * @return string method of the client request.
     */
    public function &getMethod():string{
        return $this->getHeaderField("Method");
    }
    
    public function &getUserLanguages():array{
        return $this->user_languages;
    }
    /**
     * Get the default user language from the request header.
     * @return &string
     */
    public function &getUserDefaultLanguage():string{
        return $this->user_languages["DEFAULT-LANGUAGE"];
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
        $this->session = &HttpSessionManager::startSession($this, $this->session_id);
        return $this->session;
    }
    
    /**
     * Removes the session of the current client from the server sessions list.
     * @return void No need to call HttpEventManager::startSession, this method will 
     * call it automatically if needed.
     */
    public function stopSession():void{
        if($this->session_id === null){
            $this->startSession();
        }
        $this->session = null;
        HttpSessionManager::stopSession(HttpSessionManager::getSession($this->session_id));
    }
    
    /**
     * Checks if the current client can find a session.
     * @return bool true if the client has "session_id" cookie and its value exists in the server sessions list, otherwise false.
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
        return isset($this->query_string[$key]);
    }
    
    /**
     * 
     * @param key name of the query.
     * @return the value of the query.
     */
    public function getUrlQuery(string $key):string{
        return $this->query_string[$key];
    }

    /**
     * @return the array queries pointer
     */
    public function &getUrlQueries():array{
        return $this->query_string;
    }
    
    /**
     * Finds the languages of the client application.
     * The value is stored in EventManager#userLanguages.
     */
    protected function findUserLanguages():void{
        if($this->client_header->get("Accept-Language") === null){
            $this->user_languages["unknown"]="unknown";
        }else{
            //prepare array
            $tmp = array_fill(0, 2, null);
            $languages = preg_split("/,/",$this->client_header->get("Accept-Languages"));
            $this->user_languages["DEFAULT-LANGUAGE"]=$languages[0];
            foreach($languages as &$language){
                $tmp = preg_split("/;/",$language);
                if(count($tmp) > 1)
                    $this->user_languages[$tmp[0]] = $tmp[1];
                else
                    $this->user_languages["unknown"]="unknown";
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
        $this->server_header->setCookie($key, "",$path,$domain,"0");
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
        $this->server_header->setCookie($key, $content, $path, $domain, $expire);
    }
    
    /**
     * Gets the value of the cookie.
     * @param name name of the cookie.
     * @return value of the cookie.
     */
    public function &getCookie(string $key):string{
        return $this->client_header->getCookie($key);
    }
    
    /**
     * Checks if the cookie is set.
     * @param key name of the cookie.
     */
    public function issetCookie(string $key){
        return $this->client_header->issetCookie($key);
    }
}