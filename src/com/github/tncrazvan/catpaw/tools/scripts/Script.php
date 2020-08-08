<?php
namespace com\github\tncrazvan\catpaw\tools\scripts;

use com\github\tncrazvan\catpaw\http\HttpEvent;
use com\github\tncrazvan\catpaw\tools\Caster;

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
    public static function &body(string $classname=null, bool $json = false){
        if($classname !== null){
            if($json){
                $result = &Caster::cast(\json_decode(self::event()->listener->requestContent),$classname);
            }else{
                $tmp = \parse_str(self::event()->listener->requestContent,$result);
                $result = &Caster::cast($tmp,$classname);
            }
            return $result;
        }else
            return self::event()->listener->requestContent;
    }
}