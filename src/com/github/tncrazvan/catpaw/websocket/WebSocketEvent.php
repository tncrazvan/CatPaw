<?php
namespace com\github\tncrazvan\catpaw\websocket;

use com\github\tncrazvan\catpaw\http\HttpEventListener;
use com\github\tncrazvan\catpaw\websocket\WebSocketEventManager;

class WebSocketEvent extends WebSocketEventManager{
    public static function &make(HttpEventListener &$listener):WebSocketEvent{
        $reflection_method = null;
        $callback = HttpEventListener::callback("websocket", $listener, $reflection_method);
        
        $listener->setProperty("http-consumer", false); //make sure it's not a live body injection
        $event = new WebSocketEvent();
        $event->install($listener);
        $event->callback = &$callback;
        return $event;
    }
}