<?php
namespace com\github\tncrazvan\catpaw\http;

use com\github\tncrazvan\catpaw\tools\Server;
use com\github\tncrazvan\catpaw\http\HttpEventManager;
use com\github\tncrazvan\catpaw\http\HttpEventListener;

abstract class HttpEvent extends HttpEventManager{
    public $args = [];
    public static function controller(HttpEventListener &$listener):HttpController{        
        //Default 404
        if($listener->locationLen === 0 || $listener->locationLen === 1 && $listener->location[0] === ""){
            $listener->location = [Server::$httpDefaultName];
        }
        $classId = self::getClassNameIndex(Server::$httpControllerPackageName, $listener->location ,$listener->locationLen);

        if($classId>=0){
            $classname = self::resolveClassName(Server::$httpControllerPackageName, $classId, $listener->location);
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
            if($listener->location[0] === Server::$httpDefaultName){
                $classname = Server::$httpControllerPackageNameOriginal."\\".Server::$httpDefaultNameOriginal;
                $controller = new $classname();
                $controller->install($listener);
            }else{
                $classname = Server::$httpControllerPackageName."\\".Server::$httpNotFoundName;
                if(!class_exists($classname)){
                    $classname = Server::$httpControllerPackageNameOriginal."\\".Server::$httpNotFoundNameOriginal;
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