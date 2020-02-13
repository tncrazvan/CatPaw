<?php
namespace com\github\tncrazvan\catpaw\http;

use com\github\tncrazvan\catpaw\http\HttpEventListener;
use com\github\tncrazvan\catpaw\http\HttpEventManager;

abstract class HttpEvent extends HttpEventManager{
    public $args = [];
    public static function controller(HttpEventListener &$listener):HttpController{        
        //Default 404
        if($listener->locationLen === 0 || $listener->locationLen === 1 && \preg_match('/\s*\/+\s*/',$listener->location[0]) === 1){
            $listener->location = [""];
        }
        
        $classId = self::getClassNameIndex('http', $listener, $classname);

        $methodname = $listener->locationLen-1>$classId?$listener->location[$classId+1]:"main";
        if(method_exists($classname, $methodname)){
            $controller = new $classname();
            $controller->serve = $methodname;
        }else if(method_exists($classname, "main")){
            $controller = new $classname();
            $controller->serve = "main";
        }else{
            $controller = new $listener->so->controllers["http"]["@404"]();
            $controller->serve = "main";
        }
        
        $controller->install($listener);
        $controller->args = self::resolveMethodArgs($classId, $listener);
        return $controller;
    }
}