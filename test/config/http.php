<?php
return [
    "port" => 80,
    "webRoot" => "../www",
    "bindAddress" => "0.0.0.0",
    "controllers" => [
        "http" => "com\\github\\tncrazvan\\catpaw\app\\http",
        "ws" => "com\\github\\tncrazvan\\catpaw\\app\\websocket"
    ],
    "sessionName" => "_SESSION",
    "compress" => ["deflate"],
    "header" => [
        "Cache-Control" => "no-store"
    ]
];