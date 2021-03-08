<?php
namespace net\razshare\catpaw\sessions;

use net\razshare\catpaw\attributes\sessions\Session;
use net\razshare\catpaw\config\MainConfiguration;
use net\razshare\catpaw\tools\Dir;

class SessionManager{
    public function __construct(
        private MainConfiguration $config
    ) {
        $cud = getcwd();
        Dir::umount("$cud/{$config->session_directory}");
        Dir::mount("$cud/{$config->session_directory}",$config->session_size);
    }

    public array $list = [];
    public function &startSession(array &$headers, ?string &$sessionId):array{
        if ($sessionId && $this->issetSession($sessionId)) {//if session exists
            //load the session
            $this->loadSession($sessionId);
            //if session is expired
            if($this->list[$sessionId]->getTime() + $this->config->session_ttl < time()){
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
        $data = json_decode(file_get_contents($this->config->session_directory."/$sessionId"),true);
        $headers = null;
        $session = (new Session())->init($this,$headers);
        $session->setStorage($data["STORAGE"]);
        $session->setTime($data["TIME"]);
        $session->setId($sessionId);
        $this->setSession($session);
    }
    
    public function saveSession(Session $session):void{
        $filename = $this->config->session_directory."/".$session->id();
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
        unlink($this->config->session_directory.'/'.$session->id());
    }
    
    public function &getSession(string &$sessionId):?Session{
        return $this->list[$sessionId];
    }
    
    public function issetSession(string &$sessionId):bool{
        return isset($this->list[$sessionId]);
    }
}