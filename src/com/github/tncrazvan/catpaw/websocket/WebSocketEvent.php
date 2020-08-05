<?php
namespace com\github\tncrazvan\catpaw\websocket;

use com\github\tncrazvan\catpaw\http\HttpEventListener;
use com\github\tncrazvan\catpaw\websocket\WebSocketManager;

class WebSocketEvent extends WebSocketManager{
    public static function &make(HttpEventListener &$listener):WebSocketEvent{
        $callback = HttpEventListener::callback("websocket", $listener);
        $event = new WebSocketEvent();
        $event->install($listener);
        $event->callback = $callback;
        return $event;
    }
}