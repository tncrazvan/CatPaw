<?php
namespace com\github\tncrazvan\catpaw\websocket;

use com\github\tncrazvan\catpaw\tools\Server;
use com\github\tncrazvan\catpaw\http\HttpHeader;
use com\github\tncrazvan\catpaw\websocket\WebSocketManager;

abstract class WebSocketEvent extends WebSocketManager{
    public static function serveController(array &$location,&$controller,$client,HttpHeader $clientHeader,string &$content):void{
        $locationLength = count($location);
        if($locationLength === 0 || $locationLength === 1 && $location[0] === ""){
            $location = [Server::$wsNotFoundName];
        }
        $classId = self::getClassNameIndex(Server::$wsControllerPackageName, $location);
        if($classId >= 0){
            $classname = self::resolveClassName($classId,Server::$wsControllerPackageName,$location);
            if(substr($classname,0,1) !=="\\")
            $classname = "\\".$classname;
            $controller = new $classname;
            $controller->args = self::resolveMethodArgs($classId+2, $location);
        }else{
            $classname = Server::$wsControllerPackageName."\\".Server::$wsNotFoundName;
            if(!class_exists($classname)){
                $classname = Server::$wsControllerPackageNameOriginal."\\".Server::$wsNotFoundNameOriginal;
            }
            $controller = new $classname();
        }
        $controller->classname = $classname;
    }

    protected function onOpenCaller(): void {
        if(!isset(Server::$wsEvents[$this->classname])){
            Server::$wsEvents[$this->classname] = [$this->requestId => $this];
        }else{
            Server::$wsEvents[$this->classname][$this->requestId] = $this;
        }
        try{
            $this->onOpen();
        } catch (\Exception $ex) {
            echo "\n$ex\n";
            $this->close();
        }
    }

    protected function onMessageCaller($data): void {
        try{
            $this->onMessage($data);
        } catch (\Exception $ex) {
            echo "\n$ex\n";
            $this->close();
        }
    }

    protected function onCloseCaller(): void {
        try{
            unset(Server::$wsEvents[$this->classname][$this->requestId]);
            $this->onClose();
        } catch (\Exception $ex) {
            socket_close($this->client);
            exit;
        }
    }
}