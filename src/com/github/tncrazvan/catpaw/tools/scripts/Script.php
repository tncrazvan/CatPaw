<?php
namespace com\github\tncrazvan\catpaw\tools\scripts;

use com\github\tncrazvan\catpaw\http\HttpEvent;
use com\github\tncrazvan\catpaw\http\HttpRequestHeaders;

class Script{
    public static function args():array{
        global $_ARGS;
        return $_ARGS;
    }
    public static function event():HttpEvent{
        global $_EVENT;
        return $_EVENT;
    }
    public static function &startSession():array{
        return static::event()->startSession();
    }
    public static function &stopSession():void{
        static::event()->stopSession();
    }
    public static function getMethod():string{
        return static::event()->getRequestMethod();
    }
    
    public static function setHeader(string $key, string $content):void{
        static::event()->setResponseHeader($key,$content);
    }
    public static function issetCookie(string $key):?string{
        return static::event()->issetRequestCookie($key);
    }
    public static function getCookie(string $key):?string{
        return static::event()->getRequestCookie($key);
    }
    public static function setCookie(string $key, string $content, ?string $path='/', ?string $domain=null, ?string $expire=null):void{
        static::event()->setResponseCookie($key,$content,$path,$domain,$expire);
    }
    public static function &getQueryStrings():array{
        return static::event()->getRequestQueryStrings();
    }
    public static function &getQueryString(string $key):?string{
        return static::event()->getRequestQueryString($key);
    }
    public static function &getBody(string $classname, bool $toarray = false){
        return static::event()->getRequestParsedBody($classname, $toarray);
    }
    private static ?array $loadedScripts = null;
    public static function runOnce(\Closure $callback):void{
        $e = static::event();
        if(static::$loadedScripts === null)
            static::$loadedScripts = &$e->getHttpEventListener()->getSharedObject()->getLoadedScripts();

        if(!\in_array($e->getHttpEventListener()->getPath(),static::$loadedScripts)){
            static::$loadedScripts[] = $e->getHttpEventListener()->getPath();
            $callback();
        }
    }
}