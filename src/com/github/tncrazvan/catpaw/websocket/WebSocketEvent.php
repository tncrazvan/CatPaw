<?php
namespace com\github\tncrazvan\catpaw\websocket;

use com\github\tncrazvan\catpaw\http\HttpEventListener;
use com\github\tncrazvan\catpaw\websocket\WebSocketManager;

class WebSocketEvent extends WebSocketManager{
    public static function controller(HttpEventListener &$listener):WebSocketEvent{
        if($listener->locationLen === 0 || $listener->locationLen === 1 && \preg_match('/\s*\/+\s*/',$listener->location[0]) === 1)
            $listener->location = [""];

        $callback = HttpEventListener::callback("websocket", $listener);
        $event = new WebSocketEvent();
        $event->install($listener);
        $event->serve = $callback;
        return $event;
    }
}