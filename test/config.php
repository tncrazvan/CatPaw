<?php
return [
    "port" => 80,
    "webRoot" => "../www/public",
    "bindAddress" => "127.0.0.1",
    "scripts" => [
        "editor" => "code @filename"
    ],
    "events" => [
        "http"=>[
            "/home/{username}" => require_once "events/home.php"
        ],
        "websocket"=>[]
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