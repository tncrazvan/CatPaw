<?php
namespace com\github\tncrazvan\catpaw\tools\scripts;

use com\github\tncrazvan\catpaw\http\HttpEvent;

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
        return self::event()->startSession();
    }
    public static function &stopSession():void{
        self::event()->stopSession();
    }
    public static function &queries():array{
        return self::event()->getRequestUrlQueries();
    }
    public static function &query(string $key):?string{
        return self::event()->getRequestUrlQuery($key);
    }
    public static function &body(string $classname, bool $toarray = false){
        return self::event()->getRequestParsedBody($classname, $toarray);
    }
    public static function runOnce(\Closure $callback):void{
        $e = self::event();
        if(!\in_array($e->listener->path,$e->listener->so->runOnce)){
            $e->listener->so->runOnce[] = $e->listener->path;
            $callback();
        }
    }
}