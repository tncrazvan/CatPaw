<?php

namespace com\github\tncrazvan\CatServer\Http;

class HttpSession{
    static $LIST = [];
    private $id,$STORAGE = [];
    
    public function __construct($e) {
        $this->id = hash('sha3-224',$e->getAddress().",".$e->getPort().",".rand());
        $e->setCookie("session_id", $this->id, "/");
    }
    
    public function id():string{
        return $this->id;
    }
    
    public function &storage():array{
        return $this->STORAGE;
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
    
    public function has(string $key):bool{
        return isset($this->STORAGE[$key]);
    }
    
}