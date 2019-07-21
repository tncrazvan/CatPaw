<?php
namespace com\github\tncrazvan\CatPaw\Http;

use com\github\tncrazvan\CatPaw\Tools\G;

class HttpEvent extends HttpEventManager{
    public function __construct($client, HttpHeader &$clientHeader, string &$content) {
        parent::__construct($client, $clientHeader, $content);
    }
    
    private function &serveController(array $location){
        $result = null;
        $args = [];
        $locationLength = count($location);
        if($locationLength === 0 || $locationLength === 1 && $location[0] === ""){
            $location = [G::$httpDefaultName];
        }
        $classId = self::getClassNameIndex(G::$httpControllerPackageName,$location);

        if($classId < 0){
            //$classId = self::getClassNameIndex(G::$httpControllerPackageName,$location);
        }else{

        }

        if($classId>=0){
            $classname = self::resolveClassName($classId,G::$httpControllerPackageName,$location);
            $controller = new $classname();
            $methodname = $locationLength-1>$classId?$location[$classId+1]:"main";
            $args = self::resolveMethodArgs($classId+2, $location);
            if(method_exists($controller, $methodname)){
                $result = @$controller->{$methodname}($this,$args, $this->content);
            }else{
                $args = self::resolveMethodArgs($classId+1, $location);
                $result = @$controller->main($this,$args, $this->content);
            }
            $controller->onClose();
        }else{
            if($location[0] === G::$httpDefaultName){
                $classname = G::$httpControllerPackageNameOriginal."\\".G::$httpDefaultNameOriginal;
                $controller = new $classname();
            }else{
                $classname = G::$httpControllerPackageName."\\".G::$httpNotFoundName;
                if(!class_exists($classname)){
                    $classname = G::$httpControllerPackageNameOriginal."\\".CaGt::$httpNotFoundNameOriginal;
                }
                $controller = new $classname();
            }

            $result = @$controller->main($this,$args,$this->content);
            $controller->onClose();
        }
        return $result;
    }
    protected function &onControllerRequest(string &$url){
        $result = &$this->serveController(preg_split("/\\//m",$url));
        return $result;
    }

}