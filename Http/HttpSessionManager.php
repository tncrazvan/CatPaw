<?php
namespace com\github\tncrazvan\CatPaw\Http;

use com\github\tncrazvan\CatPaw\Http\HttpEvent;
use com\github\tncrazvan\CatPaw\Http\HttpSession;

class HttpSessionManager extends HttpSession{
    protected function __construct($e) {
        parent::__construct($e);
    }
    
    public static function &startSession(HttpEvent &$e,&$sessionId):array{
        if (self::issetSession($e,$sessionId)) {//if session exists
            //load the session
            self::loadSession($sessionId);
            //if session is expired
            if(HttpSession::$LIST[$sessionId]->getTime() + self::$sessionTtl < time()){
                //delete the expired session
                self::stopSession(HttpSession::$LIST[$sessionId]);
            }else{ //if session is alive
                //update the session time, so the server knows its active, and not delete it
                //after self::$session_ttl seconds
                HttpSession::$LIST[$sessionId]->setTime(time());
                self::saveSession(HttpSession::$LIST[$sessionId]);
                //return the storage pointer
                return HttpSession::$LIST[$sessionId]->storage();
            }
        } 
        //make a new session
        $session = new HttpSession($e);
        $sessionId = $session->id();
        //save session file
        self::saveSession($session);
        self::setSession($session);
        //return the storage pointer
        return $session->storage();
    }
    
    public static function issetSession(&$e,&$sessionId):bool{
        if($e->issetCookie("sessionId")){
            $sessionId = $e->getCookie("sessionId");
            if(file_exists(HttpSession::$SESSION_DIR."/$sessionId")){
                return true;
            }
        }
        return false;
    }
    
    public static function loadSession(string &$sessionId):void{
        $data = json_decode(file_get_contents(HttpSession::$SESSION_DIR."/$sessionId"),true);
        $session = new HttpSession();
        $session->setStorage($data["STORAGE"]);
        $session->setTime($data["TIME"]);
        $session->setId($sessionId);
        self::setSession($session);
    }
    
    public static function saveSession(HttpSession &$session):void{
        file_put_contents(HttpSession::$SESSION_DIR."/".$session->id(), json_encode([
            "STORAGE"=>$session->storage(),
            "TIME"=>$session->getTime()
        ]));
    }

    public static function setSession(HttpSession &$session):void{
        HttpSession::$LIST[$session->id()] = $session;
    }
    
    public static function stopSession(HttpSession &$session):void{
        unset(HttpSession::$LIST[$session->id()]);
        unlink(HttpSession::$SESSION_DIR."/".$session->id());
    }
    
    public static function &getSession(string $sessionId):HttpSession{
        return HttpSession::$LIST[$sessionId];
    }
    
    public static function existsSession(string $sessionId):bool{
        return isset(HttpSession::$LIST[$sessionId]);
    }
}