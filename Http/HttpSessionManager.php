<?php
namespace com\github\tncrazvan\CatServer\Http;
class HttpSessionManager extends HttpSession{
    protected function __construct($e) {
        parent::__construct($e);
    }
    
    public static function &startSession(HttpEvent &$e,&$session_id):array{
        if (self::issetSession($e,$session_id)) {//if session exists
            //if session is expired
            if(HttpSession::$LIST[$session_id]->getTime() + self::$session_ttl > time()){
                //delete the expired session
                self::stopSession(HttpSession::$LIST[$session_id]);
            }else{ //if session is alive, return its storage pointer
                //update the session time, so the server knows its active, and not delete it
                //after self::$session_ttl seconds
                HttpSession::$LIST[$session_id]->setTime(time());
                //return the storage pointer
                return  HttpSession::$LIST[$session_id]->storage();
            }
        } 
        //make a new session
        $session = new HttpSession($e);
        $session_id = $session->id();
        self::setSession($session);
        //return the storage pointer
        return $session->storage();
    }
    
    public static function issetSession(&$e,&$session_id):bool{
        if($e->issetCookie("session_id")){
            $session_id = $e->getCookie("session_id");
            if(isset(HttpSession::$LIST[$session_id])){
                return true;
            }
        }
        return false;
    }
    
    public static function setSession(HttpSession &$session):void{
        HttpSession::$LIST[$session->id()] = $session;
    }
    
    public static function stopSession(HttpSession &$session):void{
        unset(HttpSession::$LIST[$session->id()]);
    }
    
    public static function &getSession(string $session_id):HttpSession{
        return HttpSession::$LIST[$session_id];
    }
    
    public static function existsSession(string $session_id):bool{
        return isset(HttpSession::$LIST[$session_id]);
    }
}