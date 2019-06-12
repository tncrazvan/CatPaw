<?php
namespace com\github\tncrazvan\CatPaw\WebSocket;

use com\github\tncrazvan\CatPaw\Tools\G;
use com\github\tncrazvan\CatPaw\Http\HttpHeader;
use com\github\tncrazvan\CatPaw\WebSocket\WebSocketManager;

class WebSocketEvent extends WebSocketManager{
    private $controller,$classname;
    const args=[];
    public function __construct(&$client, HttpHeader &$clientHeader, string &$content) {
        parent::__construct($client, $clientHeader, $content);
        $this->serveController(preg_split("/\\//m", $this->location));
    }
    
    private function serveController(array $location):void{
        $this->args = [];
        $locationLength = count($location);
        if($locationLength === 0 || $locationLength === 1 && $location[0] === ""){
            $location = [G::$wsNotFoundName];
        }
        $classId = self::getClassNameIndex(G::$wsControllerPackageName, $location);
        if($classId >= 0){
            $this->classname = self::resolveClassName($classId,G::$wsControllerPackageName,$location);
            $this->controller = new $this->classname();
            $this->args = self::resolveMethodArgs($classId+2, $location);
        }else{
            $this->classname = G::$wsControllerPackageName."\\".G::$wsNotFoundName;
            if(!class_exists($this->classname)){
                $this->classname = G::$wsControllerPackageNameOriginal."\\".G::$wsNotFoundNameOriginal;
            }
            $this->controller = new $this->classname();
        }
    }
    
    protected function onOpen(): void {
        if(!isset(G::$wsEvents[$this->classname])){
            G::$wsEvents[$this->classname] = [$this->requestId => $this];
        }else{
            G::$wsEvents[$this->classname][$this->requestId] = $this;
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
            unset(G::$wsEvents[$this->classname][$this->requestId]);
            $this->controller->onClose($this,$this->args);
        } catch (Exception $ex) {
            socket_close($client);
            exit;
        }
    }
}