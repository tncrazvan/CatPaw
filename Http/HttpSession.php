<?php

namespace com\github\tncrazvan\CatServer\Http;

class HttpSession{
    static $LIST = [];
    private $id,$STORAGE=[];
    
    private function __construct($e) {
        $this->id = hash('sha3-224',$e->get_inet_address().",".$e->get_port().",".rand());
        $e->set_cookie("session_id", $this->id, "/");
    }
    
    public function id():string{
        return $this->id;
    }
    
    public function get(string $key){
        return $this->STORAGE[$key];
    }
    
    public function set(string $key,$object):void{
        $this->STORAGE[$key]=$object;
    }
    
    public function remove(string $key):void{
        unset($this->STORAGE[$key]);
    }
    
    public function exists(string $key):bool{
        return isset($this->STORAGE[$key]);
    }
    
}