<?php

namespace com\github\tncrazvan\CatServer\WebSocket;
use com\github\tncrazvan\CatServer\Http\HttpHeader;
class WebSocketEvent extends WebSocketManager{
    private $args=[],$controller,$classname;
    public function __construct(&$client, HttpHeader &$client_header, string &$content) {
        parent::__construct($client, $client_header, $content);
        $this->serveController(preg_split("/\\//m", $this->location));
    }
    
    private function serveController(array $location):void{
        $this->args = [];
        $location_length = count($location);
        if($location_length === 0 || $location_length === 1 && $location[0] === ""){
            $location = [self::$ws_not_found_name];
        }
        $class_id = self::getClassNameIndex(self::$ws_controller_package_name, $location);
        if($class_id >= 0){
            $this->classname = self::resolveClassName($class_id,self::$ws_controller_package_name,$location);
            $this->controller = new $this->classname();
            $this->args = self::resolveMethodArgs($class_id+2, $location);
        }else{
            $this->classname = self::$ws_controller_package_name."\\".self::$ws_not_found_name;
            if(!class_exists($this->classname)){
                $this->classname = self::$ws_controller_package_name_original."\\".self::$ws_not_found_name_original;
            }
            $this->controller = new $this->classname();
        }
    }
    
    protected function onOpen(): void {
        if(!isset(self::$ws_events[$this->classname])){
            self::$ws_events[$this->classname] = [$this->request_id => $this];
        }else{
            self::$ws_events[$this->classname][$this->request_id] = $this;
        }
        try{
            $this->controller->onOpen($this,$this->args);
        } catch (Exception $ex) {
            echo "\n$ex\n";
            $this->close();
        }
    }

    protected function onMessage($data): void {
        try{
            $this->controller->onMessage($this,$data,$this->args);
        } catch (Exception $ex) {
            echo "\n$ex\n";
            $this->close();
        }
    }

    protected function onClose(): void {
        try{
            unset(self::$ws_events[$this->classname][$this->request_id]);
            $this->controller->onClose($this,$this->args);
        } catch (Exception $ex) {
            socket_close($client);
            exit;
        }
    }
}