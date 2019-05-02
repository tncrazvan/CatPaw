<?php
namespace com\github\tncrazvan\CatServer\Http;
use com\github\tncrazvan\CatServer\Http\HttpEventManager;
use com\github\tncrazvan\CatServer\Cat;
class HttpEvent extends HttpEventManager{
    public $session;
    public function __construct($client, HttpHeader &$client_headers, string &$content) {
        parent::__construct($client, $client_headers, $content);
    }
    public function sessionIdIsset():bool{
        return $this->isset_cookie("session_id") && HttpSession::exists($this->get_cookie("session_id"));
    }
    public function session_start():HttpSession{
        $this->session = HttpSession::start($this);
        return $this->session;
    }
    private function serve_controller(array $location){
        $result = null;
        $args = [];
        $location_length = count($location);
        if($location_length === 0 || $location_length === 1 && $location[0] === ""){
            $location = [Cat::$http_default_name];
        }
        try{
            $class_id = Cat::get_classname_index(Cat::$http_controller_package_name,$location);
            $classname = Cat::resolve_classname($class_id,Cat::$http_controller_package_name,$location);
            $controller = new $classname();
            $methodname = $location_length-1>$class_id?$location[$class_id+1]:"main";
            $args = Cat::resolve_method_args($class_id+2, $location);
            if(method_exists($controller, $methodname)){
                $result = $controller->{$methodname}($this,$args, $this->content);
            }else{
                $args = Cat::resolve_method_args($class_id+1, $location);
                $result = $controller->main($this,$args, $this->content);
            }
            $controller->on_close();
        } catch (\Exception $ex) {
            $classname = Cat::$http_controller_package_name."\\".Cat::$http_not_found_name;
            $controller = new $classname();
            $result = $controller->main($this,$args,$this->content);
            $controller->on_close();
        }
        return $result;
    }
    protected function on_controller_request(string $url){
        return $this->serve_controller(preg_split("/\\//m",$url));
    }

}