<?php
namespace com\github\tncrazvan\catpaw\websocket;
abstract class WebSocketEventOnOpen{
    public abstract function run():void;
}