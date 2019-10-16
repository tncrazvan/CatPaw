<?php
namespace com\github\tncrazvan\CatPaw\Http;

use com\github\tncrazvan\CatPaw\Tools\Server;
use com\github\tncrazvan\CatPaw\Tools\Status;
use com\github\tncrazvan\CatPaw\Http\HttpHeader;
use com\github\tncrazvan\CatPaw\Http\HttpResponse;
use com\github\tncrazvan\CatPaw\Http\HttpEventManager;



class HttpEvent extends HttpEventManager{
    public function __construct($client, HttpHeader &$clientHeader, string &$content) {
        parent::__construct($client, $clientHeader, $content);
    }
    
    private function &serveController(array $location){
        $result = null;
        $args = [];
        $locationLength = count($location);
        if($locationLength === 0 || $locationLength === 1 && $location[0] === ""){
            $location = [Server::$httpDefaultName];
        }
        $classId = self::getClassNameIndex(Server::$httpControllerPackageName,$location);

        if($classId>=0){
            $classname = self::resolveClassName($classId,Server::$httpControllerPackageName,$location);
            $controller = new $classname();
            $methodname = $locationLength-1>$classId?$location[$classId+1]:"main";
            $args = self::resolveMethodArgs($classId+2, $location);
            if(method_exists($controller, $methodname)){
                $result = @$controller->{$methodname}($this,$args, $this->content);
            }else if(method_exists($controller, "main")){
                $args = self::resolveMethodArgs($classId+1, $location);
                $result = @$controller->main($this,$args, $this->content);
            }else{
                $result = new HttpResponse([
                    "Status"=>Status::NOT_FOUND
                ],null);
            }
            if(method_exists($controller, "onClose"))
                $controller->onClose();
        }else{
            if($location[0] === Server::$httpDefaultName){
                $classname = Server::$httpControllerPackageNameOriginal."\\".Server::$httpDefaultNameOriginal;
                $controller = new $classname();
            }else{
                $classname = Server::$httpControllerPackageName."\\".Server::$httpNotFoundName;
                if(!class_exists($classname)){
                    $classname = Server::$httpControllerPackageNameOriginal."\\".CaGt::$httpNotFoundNameOriginal;
                }
                $controller = new $classname();
            }
            if(method_exists($controller, "main")){
                $result = @$controller->main($this,$args,$this->content);
            }else{
                $result = new HttpResponse([
                    "Status"=>Status::NOT_FOUND
                ],null);
            }
            if(method_exists($controller, "onClose"))
                $controller->onClose();
        }
        return $result;
    }
    
    protected function &onControllerRequest(string &$url){
        $result = &$this->serveController(preg_split("/\\//m",$url));
        return $result;
    }

}