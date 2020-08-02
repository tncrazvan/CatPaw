<?php
use HomePage;
return [
    "port" => 80,
    "webRoot" => "./www/public",
    "bindAddress" => "127.0.0.1",
    "events" => [
        "http"=>[
            "/home/{username}" => fn(string $method, ?User &$user = null) => new HomePage($method,$user)
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