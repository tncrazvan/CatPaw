<?php
namespace com\github\tncrazvan\CatServer\Http;
class HttpSessionManager extends HttpSession{
    protected function __construct($e) {
        parent::__construct($e);
    }
    
    public static function &startSession(HttpEvent &$e,&$session_id):array{
        if (self::issetSession($e,$session_id)) {//if session exists
            //load the session
            self::loadSession($session_id);
            //if session is expired
            if(HttpSession::$LIST[$session_id]->getTime() + self::$session_ttl < time()){
                //delete the expired session
                self::stopSession(HttpSession::$LIST[$session_id]);
            }else{ //if session is alive
                //update the session time, so the server knows its active, and not delete it
                //after self::$session_ttl seconds
                HttpSession::$LIST[$session_id]->setTime(time());
                self::saveSession(HttpSession::$LIST[$session_id]);
                //return the storage pointer
                return HttpSession::$LIST[$session_id]->storage();
            }
        } 
        //make a new session
        $session = new HttpSession($e);
        $session_id = $session->id();
        //save session file
        self::saveSession($session);
        self::setSession($session);
        //return the storage pointer
        return $session->storage();
    }
    
    public static function issetSession(&$e,&$session_id):bool{
        if($e->issetCookie("session_id")){
            $session_id = $e->getCookie("session_id");
            if(file_exists(HttpSession::SESSION_DIR."/$session_id")){
                return true;
            }
        }
        return false;
    }
    
    public static function loadSession(string &$session_id):void{
        $data = json_decode(file_get_contents(HttpSession::SESSION_DIR."/$session_id"),true);
        $session = new HttpSession();
        $session->setStorage($data["STORAGE"]);
        $session->setTime($data["TIME"]);
        $session->setId($session_id);
        self::setSession($session);
    }
    
    public static function saveSession(HttpSession &$session):void{
        file_put_contents(HttpSession::SESSION_DIR."/".$session->id(), json_encode([
            "STORAGE"=>$session->storage(),
            "TIME"=>$session->getTime()
        ]));
    }

    public static function setSession(HttpSession &$session):void{
        HttpSession::$LIST[$session->id()] = $session;
    }
    
    public static function stopSession(HttpSession &$session):void{
        unset(HttpSession::$LIST[$session->id()]);
        unlink(HttpSession::SESSION_DIR."/".$session->id());
    }
    
    public static function &getSession(string $session_id):HttpSession{
        return HttpSession::$LIST[$session_id];
    }
    
    public static function existsSession(string $session_id):bool{
        return isset(HttpSession::$LIST[$session_id]);
    }
}