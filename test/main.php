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

$webRoot = "../public";

return [
    "port" => 80,
    "webRoot" => &$webRoot,
    "bindAddress" => "127.0.0.1",
    "events" => [
        "http"=>[
            "/hello/{test}" 
                => fn(string $test,HttpEvent $e,HttpEventOnClose &$onCLose) 
                    => new HelloPage($test,$e,$onCLose),
            "/templating" 
                => fn() => ServerFile::include('./templates/index.php')
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