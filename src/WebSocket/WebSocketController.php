<?php

namespace com\github\tncrazvan\catpaw\websocket;

use com\github\tncrazvan\catpaw\websocket\WebSocketEvent;

abstract class WebSocketController extends WebSocketEvent{
    public abstract function onOpen();
    public abstract function onMessage(string &$data);
    public abstract function onClose();
}

