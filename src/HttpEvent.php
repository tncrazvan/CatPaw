<?php
namespace com\github\tncrazvan\catpaw;

use Closure;

class HttpEvent{
    public function __construct(
        private string $method,
        private string $uri,
        private Closure $callback
    ){
        if(!str_starts_with($uri,'/'))
            $uri = "/$uri";
    }

    public function &getMethod():string{
        return $this->method;
    }

    public function &getUri():string{
        return $this->uri;
    }

    public function &getClosure():Closure{
        return $this->callback;
    }
}