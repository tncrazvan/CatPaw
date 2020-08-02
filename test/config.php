<?php
use com\github\tncrazvan\catpaw\http\HttpEvent;
use com\github\tncrazvan\catpaw\http\HttpEventOnClose;
use com\github\tncrazvan\catpaw\websocket\WebSocketEventOnMessage;
use com\github\tncrazvan\catpaw\websocket\WebSocketEventOnOpen;

return [
    "port" => 80,
    "webRoot" => "../www/public",
    "bindAddress" => "127.0.0.1",
    "namespace" => "app\\com\\github\\tncrazvan\\catpaw",
    "scripts" => [
        "editor" => "code @filename"
    ],
    "events" => [
        "http"=>[
            "/home/{test}" => function(int $test,HttpEvent $e,HttpEventOnClose &$onClose){
                $onClose = new class() extends HttpEventOnClose{
                    public function run():void{
                        echo "done!\n";
                    }
                };
                return "test: $test";
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