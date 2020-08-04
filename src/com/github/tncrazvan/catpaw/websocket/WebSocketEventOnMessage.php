<?php
namespace com\github\tncrazvan\catpaw\websocket;

use com\github\tncrazvan\catpaw\tools\LinkedList;

abstract class WebSocketEventOnMessage{
    public abstract function run(LinkedList &$fragments):void;
}