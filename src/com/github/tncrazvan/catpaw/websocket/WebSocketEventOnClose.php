<?php
namespace com\github\tncrazvan\catpaw\websocket;
abstract class WebSocketEventOnClose{
    public abstract function run():void;
}