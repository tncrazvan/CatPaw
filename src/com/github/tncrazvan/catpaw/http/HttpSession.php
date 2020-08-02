<?php
namespace com\github\tncrazvan\catpaw\http;

class HttpSession {
    private $id,$STORAGE = [],$time;
    
    public function __construct($e=null) {
        if($e !== null){
            $this->id = hash('sha3-224',$e->getAddress().",".$e->getPort().",".rand());
            $e->setResponseCookie("sessionId", $this->id, "/");
            $this->time=time();
        }
    }
    
    public function setId(string $id):void{
        $this->id = $id;
    }
    
    public function getTime():int{
        return $this->time;
    }

    public function setTime(int $time):void{
        $this->time=$time;
    }

    public function &id():string{
        return $this->id;
    }
    
    public function &storage():array{
        return $this->STORAGE;
    }
    
    public function setStorage(&$value):void{
        $this->STORAGE = $value;
    }
    
    public function &get(string $key){
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