<?php
namespace com\github\tncrazvan\catpaw\http;

use com\github\tncrazvan\catpaw\http\HttpEvent;
use com\github\tncrazvan\catpaw\EventManager;

class HttpSessionManager{
    public $list= [];
    public function &startSession(HttpEvent $e,&$sessionId):array{
        if ($this->issetSession($e,$sessionId)) {//if session exists
            //load the session
            $this->loadSession($e,$sessionId);
            //if session is expired
            if($this->list[$sessionId]->getTime() + $e->listener->so->sessionTtl < time()){
                //delete the expired session
                $this->stopSession($e,$this->list[$sessionId]);
            }else{ //if session is alive
                //update the session time, so the server knows its active, and not delete it
                //after self::$session_ttl seconds
                $this->list[$sessionId]->setTime(time());
                $this->saveSession($e,$this->list[$sessionId]);
                //return the storage pointer
                return $this->list[$sessionId]->storage();
            }
        } 
        //make a new session
        $session = new HttpSession($e);
        $sessionId = $session->id();
        //save session file
        $this->saveSession($e,$session);
        $this->setSession($session);
        //return the storage pointer
        return $session->storage();
    }
    
    public function issetSession(EventManager $e,&$sessionId):bool{
        if($e->issetRequestCookie("sessionId")){
            $sessionId = $e->getRequestCookie("sessionId");
            if(file_exists($e->listener->so->sessionDir."/$sessionId")){
                return true;
            }
        }
        return false;
    }
    
    public function loadSession(EventManager $e,string $sessionId):void{
        $data = json_decode(file_get_contents($e->listener->so->sessionDir."/$sessionId"),true);
        $session = new HttpSession();
        $session->setStorage($data["STORAGE"]);
        $session->setTime($data["TIME"]);
        $session->setId($sessionId);
        self::setSession($session);
    }
    
    public function saveSession(EventManager $e,HttpSession $session):void{
        file_put_contents($e->listener->so->sessionDir."/".$session->id(), json_encode([
            "STORAGE"=>$session->storage(),
            "TIME"=>$session->getTime()
        ]));
    }

    public function setSession(HttpSession $session):void{
        $this->list[$session->id()] = $session;
    }
    
    public function stopSession(EventManager $e,HttpSession $session):void{
        unset($this->list[$session->id()]);
        unlink($e->listener->so->sessionDir."/".$session->id());
    }
    
    public function &getSession(string $sessionId):HttpSession{
        return $this->list[$sessionId];
    }
    
    public function existsSession(string $sessionId):bool{
        return isset($this->list[$sessionId]);
    }
}