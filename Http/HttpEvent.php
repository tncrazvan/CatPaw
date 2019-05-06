<?php
namespace com\github\tncrazvan\CatServer\Http;

use com\github\tncrazvan\CatServer\Http\HttpEventManager;
use com\github\tncrazvan\CatServer\Http\HttpHeader;
use com\github\tncrazvan\CatServer\Cat;
class HttpEvent extends HttpEventManager{
    public function __construct($client, HttpHeader &$client_header, string &$content) {
        parent::__construct($client, $client_header, $content);
    }
    
    private function &serveController(array $location){
        $result = null;
        $args = [];
        $location_length = count($location);
        if($location_length === 0 || $location_length === 1 && $location[0] === ""){
            $location = [Cat::$http_default_name];
        }
        $class_id = self::getClassNameIndex(Cat::$http_controller_package_name,$location);
        
        if($class_id>=0){
            $classname = self::resolveClassName($class_id,Cat::$http_controller_package_name,$location);
            $controller = new $classname();
            $methodname = $location_length-1>$class_id?$location[$class_id+1]:"main";
            $args = self::resolveMethodArgs($class_id+2, $location);
            if(method_exists($controller, $methodname)){
                $result = @$controller->{$methodname}($this,$args, $this->content);
            }else{
                $args = self::resolveMethodArgs($class_id+1, $location);
                $result = @$controller->main($this,$args, $this->content);
            }
            $controller->onClose();
        }else{
            if($location[0] === Cat::$http_default_name){
                $classname = Cat::$http_controller_package_name_original."\\".Cat::$http_default_name_original;
                $controller = new $classname();
            }else{
                $classname = Cat::$http_controller_package_name."\\".Cat::$http_not_found_name;
                if(!class_exists($classname)){
                    $classname = Cat::$http_controller_package_name_original."\\".Cat::$http_not_found_name_original;
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