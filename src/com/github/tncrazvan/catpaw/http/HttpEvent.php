<?php
namespace com\github\tncrazvan\catpaw\http;

use com\github\tncrazvan\catpaw\http\HttpEventManager;
use com\github\tncrazvan\catpaw\http\HttpEventListener;

class HttpEvent extends HttpEventManager{
    public static function make(HttpEventListener &$listener):HttpEvent{    
        $callback = HttpEventListener::callback('http', $listener);

        $event = new HttpEvent();
        $event->install($listener);
        $event->callback = $callback;
        return $event;
        
    }
}