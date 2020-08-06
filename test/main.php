<?php
namespace app;
use app\homepage\HelloPage;
use app\websockettest\WebSocketTest;
use com\github\tncrazvan\catpaw\http\HttpEvent;
use com\github\tncrazvan\catpaw\http\HttpEventOnClose;
use com\github\tncrazvan\catpaw\tools\ServerFile;
use com\github\tncrazvan\catpaw\websocket\WebSocketEvent;
use com\github\tncrazvan\catpaw\websocket\WebSocketEventOnOpen;
use com\github\tncrazvan\catpaw\websocket\WebSocketEventOnClose;
use com\github\tncrazvan\catpaw\websocket\WebSocketEventOnMessage;

return [
    "port" => 80,
    "webRoot" => "./public",
    "bindAddress" => "127.0.0.1",
    "events" => [
        "http"=>[
            "/hello/{test}"  => fn(string $test,HttpEvent $e,HttpEventOnClose &$onCLose)  => new HelloPage($test,$e,$onCLose),
            "/templating/{username}" => function(string $username){
                return ServerFile::include('./templates/index.php',$username);
            }
                
        ],
        "websocket"=>[
            "/test"
                => fn(WebSocketEvent &$e,WebSocketEventOnOpen &$onOpen,WebSocketEventOnMessage &$onMessage,WebSocketEventOnClose &$onClose) 
                    => new WebSocketTest($e,$onOpen,$onMessage,$onClose)
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