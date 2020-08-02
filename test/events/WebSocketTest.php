<?php
namespace app\websockettest;
use com\github\tncrazvan\catpaw\websocket\WebSocketClassEvent;
use com\github\tncrazvan\catpaw\websocket\WebSocketEventOnClose;
use com\github\tncrazvan\catpaw\websocket\WebSocketEventOnMessage;
use com\github\tncrazvan\catpaw\websocket\WebSocketEventOnOpen;

class WebSocketTest extends WebSocketClassEvent{
    public function __construct(?WebSocketEventOnOpen &$onOpen = null, ?WebSocketEventOnMessage &$onMessage = null, ?WebSocketEventOnClose &$onClose = null){
        $onOpen = new Open();
        $onMessage = new Message();
        $onClose = new Close();
    }
}

class Open extends WebSocketEventOnOpen{
    public function run():void{
        echo "open\n";
    }
}

class Message extends WebSocketEventOnMessage{
    public function run(string &$data):void{
        echo "message: $data\n";
    }
}

class Close extends WebSocketEventOnClose{
    public function run():void{
        echo "close\n";
    }
}