<?php
namespace com\github\tncrazvan\catpaw\http;

use Closure;
use com\github\tncrazvan\catpaw\tools\Server;
use com\github\tncrazvan\catpaw\tools\Status;
use com\github\tncrazvan\catpaw\http\HttpHeader;
use com\github\tncrazvan\catpaw\http\HttpResponse;
use com\github\tncrazvan\catpaw\http\HttpEventManager;



class HttpEvent extends HttpEventManager{
    public $args = [];

    public static function serveController(array &$location,&$controller,&$serve,$client,HttpHeader $clientHeader, string &$content){
        $locationLength = count($location);
        if($locationLength === 0 || $locationLength === 1 && $location[0] === ""){
            $location = [Server::$httpDefaultName];
        }
        $classId = self::getClassNameIndex(Server::$httpControllerPackageName,$location);

        if($classId>=0){
            $classname = self::resolveClassName($classId,Server::$httpControllerPackageName,$location);
            $controller = new $classname();
            $controller->install($client,$clientHeader,$content);
            $methodname = $locationLength-1>$classId?$location[$classId+1]:"main";
            $controller->args = self::resolveMethodArgs($classId+2, $location);
            if(method_exists($controller, $methodname)){
                $serve = function() use(&$controller,&$methodname){
                    return @$controller->{$methodname}();
                };
            }else if(method_exists($controller, "main")){
                $controller->args = self::resolveMethodArgs($classId+1, $location);
                $serve = function() use(&$controller){
                    return @$controller->main();
                };
            }else{
                $serve = function(){
                    return new HttpResponse([
                        "Status"=>Status::NOT_FOUND
                    ],null);
                };
            }
        }else{
            if($location[0] === Server::$httpDefaultName){
                $classname = Server::$httpControllerPackageNameOriginal."\\".Server::$httpDefaultNameOriginal;
                $controller = new $classname();
                $controller->install($client,$clientHeader,$content);
            }else{
                $classname = Server::$httpControllerPackageName."\\".Server::$httpNotFoundName;
                if(!class_exists($classname)){
                    $classname = Server::$httpControllerPackageNameOriginal."\\".Server::$httpNotFoundNameOriginal;
                }
                $controller = new $classname();
                $controller->install($client,$clientHeader,$content);
            }
            if(method_exists($controller, "main")){
                $serve = function() use(&$controller){
                    return @$controller->main();
                };
            }else{
                $serve = function(){
                    return new HttpResponse([
                        "Status"=>Status::NOT_FOUND
                    ],null);
                };
            }
        }
    }
}