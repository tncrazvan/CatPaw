<?php

namespace com\github\tncrazvan\CatServer\Http;

class HttpSession{
    static $LIST = [];
    private $id,$STORAGE=[];
    
    private function __construct($e) {
        $this->id = hash('sha3-224',$e->get_inet_address().",".$e->get_port().",".rand());
        $e->set_cookie("session_id", $this->id, "/");
    }
    
    public static function start(HttpEvent $e):HttpSession{
        if($e->isset_cookie("session_id")){
            $session_id = $e->get_cookie("session_id");
            if(isset(self::$LIST[$session_id])){
                return self::$LIST[$session_id];
            }
        }
        $session = new HttpSession($e);
        self::set($session);
        return $session;
    }
    
    public static function get(string $session_id):HttpSession{
        return self::$LIST[$session_id];
    }
    
    public static function make(HttpSession $session):void{
        self::$LIST[$session_id] = $session;
    }
    
    public static function exists(string $session_id):bool{
        return isset(self::$LIST[$session_id]);
    }
    
    public static function remove(HttpSession $session):void{
        unset(self::$LIST[$session->get_session_id()]);
    }
    
    public function get_session_id():string{
        return $this->id;
    }
    
    public function set_property(string $key,$object):void{
        $this->STORAGE[$key]=$object;
    }
    
    public function unset_property(string $key):void{
        unset($this->STORAGE[$key]);
    }
    
    public function isset_property(string $key):bool{
        return isset($this->STORAGE[$key]);
    }
    
    public function get_property(string $key){
        return $this->STORAGE[$key];
    }
}