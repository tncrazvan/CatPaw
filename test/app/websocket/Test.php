<?php
namespace com\github\tncrazvan\catpaw\app\websocket;

use com\github\tncrazvan\catpaw\websocket\WebSocketEvent;
use com\github\tncrazvan\catpaw\websocket\WebSocketController;

class Test extends WebSocketController{
    public function onOpen(WebSocketEvent &$e, array &$args):void{
        echo "connected\n";
    }
    public function onMessage(WebSocketEvent &$e,string &$data, array &$args):void{
        echo "message: $data\n";
    }
    public function onClose(WebSocketEvent &$e, array &$args):void{
        echo "closed\n";
    }
}