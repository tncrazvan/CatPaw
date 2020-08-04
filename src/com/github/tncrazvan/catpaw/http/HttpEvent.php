<?php
namespace com\github\tncrazvan\catpaw\http;

use com\github\tncrazvan\catpaw\http\HttpEventManager;
use com\github\tncrazvan\catpaw\http\HttpEventListener;

class HttpEvent extends HttpEventManager{
    public static function make(HttpEventListener &$listener):HttpEvent{        
        //Default 404
        if($listener->locationLen === 0 || $listener->locationLen === 1 && \preg_match('/\s*\/+\s*/',$listener->location[0]) === 1)
            $listener->location = [""];
        
        
        $callback = HttpEventListener::callback('http', $listener);

        $event = new HttpEvent();
        $event->install($listener);
        $event->callback = $callback;
        return $event;
        
    }
}