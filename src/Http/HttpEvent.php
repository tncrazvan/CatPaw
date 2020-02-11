<?php
namespace com\github\tncrazvan\catpaw\http;

use com\github\tncrazvan\catpaw\http\HttpEventListener;
use com\github\tncrazvan\catpaw\http\HttpEventManager;

abstract class HttpEvent extends HttpEventManager{
    public $args = [];
    public static function controller(HttpEventListener &$listener):HttpController{        
        //Default 404
        if($listener->locationLen === 0 || $listener->locationLen === 1 && $listener->location[0] === ""){
            $listener->location = [$listener->so->httpDefaultName];
        }
        $classId = self::getClassNameIndex($listener->so->httpControllerPackageName, $listener->location ,$listener->locationLen);

        if($classId>=0){
            $classname = self::resolveClassName($listener->so->httpControllerPackageName, $classId, $listener->location);
            $controller = new $classname();
            $controller->install($listener);
            $methodname = $listener->locationLen-1>$classId?$listener->location[$classId+1]:"main";
            $controller->args = self::resolveMethodArgs($classId+2, $listener->location, $listener->locationLen);
            if(method_exists($controller, $methodname)){
                $controller->serve = $methodname;
            }else if(method_exists($controller, "main")){
                $controller->args = self::resolveMethodArgs($classId+1, $listener->location, $listener->locationLen);
                $controller->serve = "main";
            }//else leave the Default 404 as it is
        }else{
            if($listener->location[0] === $listener->so->httpDefaultName){
                $classname = $listener->so->httpControllerPackageNameOriginal."\\".$listener->so->httpDefaultNameOriginal;
                $controller = new $classname();
                $controller->install($listener);
            }else{
                $classname = $listener->so->httpControllerPackageName."\\".$listener->so->httpNotFoundName;
                if(!class_exists($classname)){
                    $classname = $listener->so->httpControllerPackageNameOriginal."\\".$listener->so->httpNotFoundNameOriginal;
                }
                $controller = new $classname();
                $controller->install($listener);
            }
            if(method_exists($controller, "main")){
                $controller->serve = "main";
            }//else leave the Default 404 as it is
        }
        return $controller;
    }
}