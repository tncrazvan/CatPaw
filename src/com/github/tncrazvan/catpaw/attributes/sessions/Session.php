<?php
namespace com\github\tncrazvan\catpaw\attributes\sessions;

use com\github\tncrazvan\catpaw\attributes\interfaces\AttributeInterface;
use com\github\tncrazvan\catpaw\attributes\traits\CoreAttributeDefinition;
use com\github\tncrazvan\catpaw\sessions\SessionManager;

#[\Attribute]
class Session implements AttributeInterface{
    use CoreAttributeDefinition;
    
    private string $id;
    private array $STORAGE = [];
    private int $time;
    
    public function init(SessionManager $sm, ?array &$headers = null):static {
        if($headers !== null){
            $this->id = hash('sha3-224',rand());
            while($sm->issetSession($this->id))
                $this->id = hash('sha3-224',rand());
            
            $headers['Set-Cookie'] = urlencode('sessionId') . '=' . urlencode($this->id);
            $this->time=time();
        }
        return $this;
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
    
    public function setStorage(array &$storage):void{
        $this->STORAGE = $storage;
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