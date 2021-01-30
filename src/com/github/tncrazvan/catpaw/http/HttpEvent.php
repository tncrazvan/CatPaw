<?php
namespace com\github\tncrazvan\catpaw\http;

use com\github\tncrazvan\catpaw\http\HttpEventManager;
use com\github\tncrazvan\catpaw\http\HttpEventListener;

class HttpEvent extends HttpEventManager{
    public static function &make(HttpEventListener &$listener):HttpEvent{    
        $reflection_method = null;
        $reflection_class = null;
        $callback = HttpEventListener::callback('http', $listener, $reflection_method, $reflection_class);

        $event = new HttpEvent();
        $event->install($listener);
        $event->callback = &$callback;
        $event->reflection_method = $reflection_method;
        $event->reflection_class = $reflection_class;
        return $event;
        
    }
}