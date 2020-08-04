<?php
namespace com\github\tncrazvan\catpaw\http;

class HttpResponseCookies{
    private HttpEvent $event;
    public function __construct(HttpEvent $event){
        $this->event=$event;
    }

    public function set(string $key, string $content, ?string $path='/', ?string $domain=null, ?string $expire=null):void{
        $this->event->setResponseCookie($key,$content,$path,$domain,$expire);
    }

    public function unset(string $key, ?string $path, ?string $domain):void{
        $this->event->unsetResponseCookie($key,$path,$domain);
    }

    public function &getAll():array{
        return $this->event->getResponseCookies();
    }

    public static function &factory(HttpEvent $e):HttpResponseCookies{
        $object = new HttpResponseCookies($e);
        return $object;
    }
}