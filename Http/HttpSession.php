<?php
namespace com\github\tncrazvan\CatPaw\Http;

use com\github\tncrazvan\CatPaw\Tools\G;

class HttpSession extends G{
    public static $LIST = [];
    public static $SESSION_DIR;
    private $id,$STORAGE = [],$time;
    
    protected function __construct($e=null) {
        if($e !== null){
            $this->id = hash('sha3-224',$e->getAddress().",".$e->getPort().",".rand());
            $e->setCookie("sessionId", $this->id, "/");
            $this->time=time();
        }
    }
    
    protected function setId(string &$id):void{
        $this->id = $id;
    }
    
    protected function getTime():int{
        return $this->time;
    }

    protected function setTime(&$time):void{
        $this->time=$time;
    }

    public function &id():string{
        return $this->id;
    }
    
    public function &storage():array{
        return $this->STORAGE;
    }
    
    protected function setStorage(&$value):void{
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