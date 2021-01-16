<?php
namespace com\github\tncrazvan\catpaw\http;

use com\github\tncrazvan\catpaw\http\HttpEventManager;
use com\github\tncrazvan\catpaw\http\HttpEventListener;

class HttpEvent extends HttpEventManager{
    public static function &make(HttpEventListener &$listener):HttpEvent{    
        $reflection_method = null;
        $callback = HttpEventListener::callback('http', $listener, $reflection_method);

        $event = new HttpEvent();
        $event->install($listener);
        $event->callback = &$callback;
        $event->reflection_method = $reflection_method;
        return $event;
        
    }
}