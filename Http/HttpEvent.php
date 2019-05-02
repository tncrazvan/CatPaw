<?php
namespace com\github\tncrazvan\CatServer\Http;
use com\github\tncrazvan\CatServer\Http\HttpEventManager;
use com\github\tncrazvan\CatServer\Cat;
class HttpEvent extends HttpEventManager{
    protected $session = null;
    public function __construct($client, HttpHeader &$client_header, string &$content) {
        parent::__construct($client, $client_header, $content);
    }
    public function issetSessionId():bool{
        return $this->isset_cookie("session_id") && HttpSession::exists($this->get_cookie("session_id"));
    }
    public function &startSession():array{
        $this->session = &HttpSessionManager::start($this);
        return $this->session;
    }
    public function stopSeassion():void{
        HttpSessionManager::stop($this->session);
    }
    public function issetSession():bool{
        if($this->session === null) return false;
        return HttpSessionManager::session_isset($e);
    }
    private function &serveController(array $location){
        $result = null;
        $args = [];
        $location_length = count($location);
        if($location_length === 0 || $location_length === 1 && $location[0] === ""){
            $location = [Cat::$http_default_name];
        }
        try{
            $class_id = Cat::getClassNameIndex(Cat::$http_controller_package_name,$location);
            $classname = Cat::resolveClassName($class_id,Cat::$http_controller_package_name,$location);
            $controller = new $classname();
            $methodname = $location_length-1>$class_id?$location[$class_id+1]:"main";
            $args = Cat::resolveMethodArgs($class_id+2, $location);
            if(method_exists($controller, $methodname)){
                $result = @$controller->{$methodname}($this,$args, $this->content);
            }else{
                $args = Cat::resolveMethodArgs($class_id+1, $location);
                $result = @$controller->main($this,$args, $this->content);
            }
            $controller->onClose();
        } catch (\Exception $ex) {
            $classname = Cat::$http_controller_package_name."\\".Cat::$http_not_found_name;
            $controller = new $classname();
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