<?php
namespace com\github\tncrazvan\CatPaw\WebSocket;

use com\github\tncrazvan\CatPaw\Tools\Server;
use com\github\tncrazvan\CatPaw\Http\HttpHeader;
use com\github\tncrazvan\CatPaw\WebSocket\WebSocketManager;

class WebSocketEvent extends WebSocketManager{
    const args=[];
    public function __construct(&$client, HttpHeader &$clientHeader, string &$content) {
        parent::__construct($client, $clientHeader, $content);
        $this->serveController(preg_split("/\\//m", $this->location));
    }
    
    private function serveController(array $location):void{
        $this->args = [];
        $locationLength = count($location);
        if($locationLength === 0 || $locationLength === 1 && $location[0] === ""){
            $location = [Server::$wsNotFoundName];
        }
        $classId = self::getClassNameIndex(Server::$wsControllerPackageName, $location);
        if($classId >= 0){
            $this->classname = self::resolveClassName($classId,Server::$wsControllerPackageName,$location);
            if(substr($this->classname,0,1) !=="\\")
                $this->classname = "\\".$this->classname;
            $classname = $this->classname;
            $controller = new $classname;
            $this->controller = $controller;
            $this->args = self::resolveMethodArgs($classId+2, $location);
        }else{
            $this->classname = Server::$wsControllerPackageName."\\".Server::$wsNotFoundName;
            if(!class_exists($this->classname)){
                $this->classname = Server::$wsControllerPackageNameOriginal."\\".Server::$wsNotFoundNameOriginal;
            }
            $this->controller = new $this->classname();
        }
    }
    
    protected function onOpen(): void {
        if(!isset(Server::$wsEvents[$this->classname])){
            Server::$wsEvents[$this->classname] = [$this->requestId => $this];
        }else{
            Server::$wsEvents[$this->classname][$this->requestId] = $this;
        }
        try{
            $this->controller->onOpen($this,$this->args);
        } catch (\Exception $ex) {
            echo "\n$ex\n";
            $this->close();
        }
    }

    protected function onMessage($data): void {
        try{
            $this->controller->onMessage($this,$data,$this->args);
        } catch (\Exception $ex) {
            echo "\n$ex\n";
            $this->close();
        }
    }

    protected function onClose(): void {
        try{
            unset(Server::$wsEvents[$this->classname][$this->requestId]);
            $this->controller->onClose($this,$this->args);
        } catch (\Exception $ex) {
            socket_close($this->client);
            exit;
        }
    }
}