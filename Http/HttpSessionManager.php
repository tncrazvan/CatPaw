<?php
namespace com\github\tncrazvan\CatServer\Http;
class HttpSessionManager{
    public static function &start(HttpEvent &$e):array{
        if (self::session_isset($e,$session_id)) {
            $result = &HttpSession::$LIST[$session_id]->storage();
        }else{
            $session = new HttpSession($e);
            self::set($session);
            $result = &$session->storage();
        }
        return $result;
    }
    
    public static function session_isset(&$e,&$session_id):bool{
        if($e->issetCookie("session_id")){
            $session_id = $e->getCookie("session_id");
            if(isset(HttpSession::$LIST[$session_id])){
                return true;
            }
        }
        return false;
    }
    
    public static function set(HttpSession &$session):void{
        HttpSession::$LIST[$session->id()] = $session;
    }
    
    public static function stop(HttpSession &$session):void{
        unset(HttpSession::$LIST[$session->id()]);
    }
    
    public static function &get(string $session_id):HttpSession{
        return HttpSession::$LIST[$session_id];
    }
    
    public static function exists(string $session_id):bool{
        return isset(HttpSession::$LIST[$session_id]);
    }
}