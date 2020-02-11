<?php
namespace com\github\tncrazvan\catpaw\websocket;

use com\github\tncrazvan\catpaw\tools\SharedObject;
use com\github\tncrazvan\catpaw\http\HttpEventListener;
use com\github\tncrazvan\catpaw\websocket\WebSocketManager;

abstract class WebSocketEvent extends WebSocketManager{
    public static function controller(HttpEventListener &$listener):WebSocketController{
        if($listener->locationLen === 0 || $listener->locationLen === 1 && $listener->location[0] === "")
            $listener->location = [$listener->so->wsNotFoundName];
        
        $classId = self::getClassNameIndex($listener->so->wsControllerPackageName, $listener->location, $listener->locationLen);
        if($classId >= 0){
            $classname = self::resolveClassName($listener->so->wsControllerPackageName, $classId, $listener->location);
            if(substr($classname,0,1) !=="\\")
                $classname = "\\".$classname;
            $controller = new $classname;
            $controller->args = self::resolveMethodArgs($classId+2, $listener->location, $listener->locationLen);
        }else{
            $classname = $listener->so->wsControllerPackageName."\\".$listener->so->wsNotFoundName;
            if(!class_exists($classname))
                $classname = $listener->so->wsControllerPackageNameOriginal."\\".$listener->so->wsNotFoundNameOriginal;
            $controller = new $classname();
        }
        $controller->classname = $classname;
        $controller->install($listener);
        return $controller;
    }

    protected function onOpenCaller(): void {
        if(!isset($this->listener->so->wsEvents[$this->classname])){
            $this->listener->so->wsEvents[$this->classname] = [$this->requestId => $this];
        }else{
            $this->listener->so->wsEvents[$this->classname][$this->requestId] = $this;
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
            unset($this->listener->so->wsEvents[$this->classname][$this->requestId]);
            $this->onClose();
        } catch (\Exception $ex) {
            socket_close($this->listener->client);
            exit;
        }
    }
}