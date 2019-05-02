<?php
namespace com\github\tncrazvan\CatServer\Http;
class HttpSessionManager{
    public static function start(HttpEvent $e):HttpSession{
        if($e->isset_cookie("session_id")){
            $session_id = $e->get_cookie("session_id");
            if(isset(HttpSession::$LIST[$session_id])){
                return HttpSession::$LIST[$session_id];
            }
        }
        $session = new HttpSession($e);
        self::set($session);
        return $session;
    }
    
    public static function set(HttpSession &$session){
        HttpSession::$LIST[$session->id()] = $session;
    }
    
    public static function stop(HttpSession &$session):void{
        unset(HttpSession::$LIST[$session->get_session_id()]);
    }
    
    public static function get(string $session_id):HttpSession{
        return HttpSession::$LIST[$session_id];
    }
    
    public static function exists(string $session_id):bool{
        return isset(HttpSession::$LIST[$session_id]);
    }
}