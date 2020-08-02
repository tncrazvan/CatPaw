<?php
use com\github\tncrazvan\catpaw\http\HttpEvent;
use com\github\tncrazvan\catpaw\http\HttpEventAssert;
use com\github\tncrazvan\catpaw\http\HttpEventException;
use com\github\tncrazvan\catpaw\http\HttpEventOnClose;
use com\github\tncrazvan\catpaw\http\HttpException;
use com\github\tncrazvan\catpaw\http\HttpRequestCookies;
use com\github\tncrazvan\catpaw\http\HttpResponseCookies;
use com\github\tncrazvan\catpaw\tools\Status;
use com\github\tncrazvan\catpaw\websocket\WebSocketEventOnMessage;
use com\github\tncrazvan\catpaw\websocket\WebSocketEventOnOpen;

return [
    "port" => 80,
    "webRoot" => "../www/public",
    "bindAddress" => "127.0.0.1",
    "scripts" => [
        "editor" => "code @filename"
    ],
    "events" => [
        "http"=>[
            "/home/{test}" => function(int $test, string &$_METHOD, HttpRequestCookies &$_REQUEST_COOKIES, HttpResponseCookies &$_RESPONSE_COOKIES){
                HttpEventAssert::true($_METHOD === "GET",new HttpEventException("Method not allowed.",Status::METHOD_NOT_ALLOWED));
                print_r($_REQUEST_COOKIES->getAll());
                $_RESPONSE_COOKIES->set("testing2","random-value".time());
                return "this is a test!";
            }
        ],
        "websocket"=>[
            "/test" => function(WebSocketEventOnOpen &$onOpen,WebSocketEventOnMessage &$onMessage){
                $onOpen = new class() extends WebSocketEventOnOpen{
                    public function run():void{
                        echo "hello world!\n";
                    }
                };
                
                $ok = true;
                $onMessage = new class($ok) extends WebSocketEventOnMessage{
                    private $ok;
                    public function __construct(&$ok){
                        $this->ok = $ok;
                    }
                    public function run(string &$data):void{
                        echo "len: ".strlen($data)." - ";
                        $array = str_split($data);
                        foreach ($array as &$char) {
                            $this->ok = $this->ok && ($char === 'a');
                        }
                        echo "Contains only 'a'?".($this->ok?"yes\n":"no\n");
                    }
                };
            }
        ]
    ],
    "sessionName" => "_SESSION",
    "ramSession" => [
        "allow" => false,
        "size" => "1024M"
    ],
    "compress" => ["deflate"],
    "headers" => [
        "Cache-Control" => "no-store"
    ]
];