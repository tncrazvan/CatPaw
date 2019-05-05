<?php

namespace com\github\tncrazvan\CatServer\Controller\WebSocket;
use com\github\tncrazvan\CatServer\WebSocket\WebSocketController;
use com\github\tncrazvan\CatServer\WebSocket\WebSocketEvent;
class ControllerNotFound extends WebSocketController{
    
    public function onOpen(WebSocketEvent &$e, array &$args): void {
        $e->close();
    }
    public function onMessage(WebSocketEvent &$e, string &$data, array &$args): void {}
    
    public function onClose(WebSocketEvent &$e, array &$args): void {}
}