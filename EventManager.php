<?php
namespace com\github\tncrazvan\CatServer\Http;

use com\github\tncrazvan\CatServer\Cat;

class EventManager extends Cat{
    
    protected 
            $client_headers,
            $location,
            $user_languages=[],
            $query_string=[],
            $server_headers;
    
    public function __construct(HttpHeader $client_headers) {
        $this->server_headers = new HttpHeader();
        $this->client_headers = $client_headers;
        $parts = preg_split("/\\?|\\&/m",preg_replace("/^\\//m","",urldecode($client_headers->get("Resource"))));
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
    
    /**
     * Checks if the requested URL contains the given key as a query.
     * @param key name of the query.
     * @return 
     */
    public function isset_url_query(string $key):bool{
        return isset($this->query_string[$key]);
    }
    
    /**
     * 
     * @param key name of the query.
     * @return the value of the query.
     */
    public function get_url_query(string $key):string{
        return $this->query_string[$key];
    }
    
    /**
     * Finds the languages of the client application.
     * The value is stored in EventManager#userLanguages.
     */
    protected function find_user_languages():void{
        if($this->client_headers->get("Accept-Language") === null){
            $this->user_languages["unknown"]="unknown";
        }else{
            //prepare array
            $tmp = array_fill(0, 2, null);
            $languages = preg_split("/,/",$this->client_headers->get("Accept-Languages"));
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
    public function unset_cookie(string $key, string $path="/", string $domain=null):void{
        $this->server_headers->set_cookie($key, "",$path,$domain,"0");
    }
    
    /**
     * Notices the client to set the given cookie.
     * @param name name of the cookie.
     * @param value value of the cookie.
     * @param path path of the cookie.
     * @param domain domain of the cooke.
     * @param expire time to live of the cookie.
     */
    public function set_cookie(string $name, string $value, string $path, string $domain, string $expire):void{
        $this->server_headers->set_cookie($key, $value, $path, $domain, $expire);
    }
    
    /**
     * Gets the value of the cookie.
     * @param name name of the cookie.
     * @return value of the cookie.
     */
    public function get_cookie(string $key):string{
        $this->client_headers->get_cookie($key);
    }
    
    /**
     * Checks if the cookie is set.
     * @param key name of the cookie.
     */
    public function isset_cookie(string $key){
        return $this->client_headers->isset_cookie($key);
    }
    
    public function cookie_isset(string $key){
        return $this->isset_cookie($key);
    }
}