<?php

namespace com\github\tncrazvan\CatServer\WebSocket;
use com\github\tncrazvan\CatServer\Http\HttpHeader;
class WebSocketEvent extends WebSocketManager{
    private $args=[];
    public function __construct(&$client, HttpHeader &$client_header, string &$content) {
        parent::__construct($client, $client_header, $content);
    }
}