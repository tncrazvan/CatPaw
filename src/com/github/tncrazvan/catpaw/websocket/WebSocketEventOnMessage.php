<?php
namespace com\github\tncrazvan\catpaw\websocket;
abstract class WebSocketEventOnMessage{
    public abstract function run(string &$data):void;
}