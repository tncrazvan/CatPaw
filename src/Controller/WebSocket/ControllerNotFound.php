<?php
namespace com\github\tncrazvan\catpaw\controller\WebSocket;

use com\github\tncrazvan\catpaw\websocket\WebSocketController;
use com\github\tncrazvan\catpaw\websocket\WebSocketEvent;

class ControllerNotFound extends WebSocketController{
    
    public function onOpen(): void {
        $this->close();
    }
    public function onMessage(string &$data): void {}
    
    public function onClose(): void {}
}