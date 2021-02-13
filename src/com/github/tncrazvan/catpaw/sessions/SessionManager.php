<?php
namespace com\github\tncrazvan\catpaw\sessions;

use com\github\tncrazvan\catpaw\attributes\sessions\Session;
use com\github\tncrazvan\catpaw\tools\Dir;

class SessionManager{
    /**
     * The time each session will live for (in seconds).
     */
    public int $ttl = 60 * 24;
    /**
     * Ram session size in MBs.
     */
    public int $size = 512;
    /**
    * The directory where sessions will be saved.
    */
    public string $directory = '@sessions';

    public function __construct() {
        $cud = getcwd();
        Dir::umount("$cud/{$this->directory}");
        Dir::mount("$cud/{$this->directory}",$this->size);
    }

    public array $list = [];
    public function &startSession(array &$headers, ?string &$sessionId):array{
        if ($sessionId && $this->issetSession($sessionId)) {//if session exists
            //load the session
            $this->loadSession($sessionId);
            //if session is expired
            if($this->list[$sessionId]->getTime() + $this->ttl < time()){
                //delete the expired session
                $this->stopSession($this->list[$sessionId]);
            }else{ //if session is alive
                //update the session time, so the server knows its active, and not delete it
                //after self::$session_ttl seconds
                $this->list[$sessionId]->setTime(time());
                $this->saveSession($this->list[$sessionId]);
                //return the storage pointer
                return $this->list[$sessionId]->storage();
            }
        } 
        //make a new session
        $session = (new Session())->init($this,$headers);
        $sessionId = $session->id();
        //save session file
        $this->saveSession($session);
        $this->setSession($session);
        //return the storage pointer
        return $session->storage();
    }
    
    public function loadSession(string &$sessionId):void{
        $data = json_decode(file_get_contents($this->directory."/$sessionId"),true);
        $headers = null;
        $session = (new Session())->init($this,$headers);
        $session->setStorage($data["STORAGE"]);
        $session->setTime($data["TIME"]);
        $session->setId($sessionId);
        $this->setSession($session);
    }
    
    public function saveSession(Session $session):void{
        $filename = $this->directory."/".$session->id();
        $dirname = dirname($filename);
        if(!is_dir($dirname)){
            mkdir($dirname,0777,true);
        }
        
        file_put_contents($filename, json_encode([
            "STORAGE"=>$session->storage(),
            "TIME"=>$session->getTime()
        ]));
    }

    public function setSession(Session $session):void{
        $this->list[$session->id()] = $session;
    }
    
    public function stopSession(Session $session):void{
        unset($this->list[$session->id()]);
        unlink($this->directory.'/'.$session->id());
    }
    
    public function &getSession(string &$sessionId):Session{
        return $this->list[$sessionId];
    }
    
    public function issetSession(string &$sessionId):bool{
        return isset($this->list[$sessionId]);
    }
}