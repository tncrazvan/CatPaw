<?php
use app\events\http\homepage\HelloPage;
use com\github\tncrazvan\catpaw\Event;
use app\events\websocket\websockettest\WebSocketTest;
use com\github\tncrazvan\catpaw\http\HttpEvent;
use com\github\tncrazvan\catpaw\http\HttpEventOnClose;
use com\github\tncrazvan\catpaw\tools\ServerFile;
use com\github\tncrazvan\catpaw\websocket\WebSocketEvent;
use com\github\tncrazvan\catpaw\websocket\WebSocketEventOnOpen;
use com\github\tncrazvan\catpaw\websocket\WebSocketEventOnClose;
use com\github\tncrazvan\catpaw\websocket\WebSocketEventOnMessage;

Event::http("/hello/{test}",function(string $test,HttpEvent $e,HttpEventOnClose &$onClose){
    return new HelloPage($test,$e,$onClose);
});

Event::http("/templating/{username}",[
    "GET" => function(string $username){
        return ServerFile::include('./public/index.php',$username);
    }
]);

Event::websocket("/test",[
    "GET" => function(WebSocketEvent &$e,WebSocketEventOnOpen &$onOpen,WebSocketEventOnMessage &$onMessage,WebSocketEventOnClose &$onClose){
        return new WebSocketTest($e,$onOpen,$onMessage,$onClose);
    }
]);