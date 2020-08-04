<?php
namespace com\github\tncrazvan\catpaw\http;

class HttpRequestCookies{
    private HttpEvent $event;
    public function __construct(HttpEvent $event){
        $this->event=$event;
    }

    public function isset(string $key):bool{
        return $this->event->issetRequestCookie($key);
    }

    public function &getAll():array{
        return $this->event->getRequestCookies();
    }

    public static function &factory(HttpEvent $e):HttpRequestCookies{
        $object = new HttpRequestCookies($e);
        return $object;
    }
}