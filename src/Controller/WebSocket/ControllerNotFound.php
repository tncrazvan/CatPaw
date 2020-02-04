<?php
namespace com\github\tncrazvan\catpaw\controller\WebSocket;

use com\github\tncrazvan\catpaw\websocket\WebSocketController;
use com\github\tncrazvan\catpaw\websocket\WebSocketEvent;

class ControllerNotFound extends WebSocketController{
    
    public function onOpen(WebSocketEvent &$e, array &$args): void {
        $e->close();
    }
    public function onMessage(WebSocketEvent &$e, string &$data, array &$args): void {}
    
    public function onClose(WebSocketEvent &$e, array &$args): void {}
}