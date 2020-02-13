<?php
namespace com\github\tncrazvan\catpaw\websocket;

use com\github\tncrazvan\catpaw\tools\SharedObject;
use com\github\tncrazvan\catpaw\http\HttpEventListener;
use com\github\tncrazvan\catpaw\websocket\WebSocketManager;

abstract class WebSocketEvent extends WebSocketManager{
    public static function controller(HttpEventListener &$listener):WebSocketController{
        if($listener->locationLen === 0 || $listener->locationLen === 1 && \preg_match('/\s*\/+\s*/',$listener->location[0]) === 1)
            $listener->location = [$listener->so->wsNotFoundName];
        
        $classId = self::getClassNameIndex('websocket', $listener, $classname);
        $controller = new $classname;
        $controller->args = self::resolveMethodArgs($classId, $listener);
        
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