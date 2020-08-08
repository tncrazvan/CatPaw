<?php
namespace app\events\websocket\websockettest;

use com\github\tncrazvan\catpaw\tools\LinkedList;
use com\github\tncrazvan\catpaw\websocket\WebSocketEvent;
use com\github\tncrazvan\catpaw\websocket\WebSocketEventHandler;
use com\github\tncrazvan\catpaw\websocket\WebSocketEventOnClose;
use com\github\tncrazvan\catpaw\websocket\WebSocketEventOnOpen;
use com\github\tncrazvan\catpaw\websocket\WebSocketEventOnMessage;

class WebSocketTest extends WebSocketEventHandler{
    public function __construct(WebSocketEvent $event,?WebSocketEventOnOpen &$onOpen = null,?WebSocketEventOnMessage &$onMessage = null, ?WebSocketEventOnClose &$onClose = null){
        $onOpen = new Open();
        $onMessage = new Message($event);
        $onClose = new Close();
    }
}

class Open extends WebSocketEventOnOpen{
    public function run():void{
        echo "hello world!\n";
    }
}


class Message extends WebSocketEventOnMessage{
    private WebSocketEvent $e;
    public function __construct(WebSocketEvent $e){
        $this->e = $e;
    }
    public function run(LinkedList &$fragments):void{
        $message = '';
        self::joinFragments($fragments,$message,LinkedList::IT_MODE_FIFO);
        \file_put_contents("./test.txt",$message);
        echo "DONE!\n";
    }
}


class Close extends WebSocketEventOnClose{
    public function run():void{
        echo "bye!\n";
    }
}