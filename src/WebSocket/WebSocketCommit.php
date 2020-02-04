<?php
namespace com\github\tncrazvan\catpaw\websocket;

use com\github\tncrazvan\catpaw\websocket\WebSocketEvent;

class WebSocketCommit{
    private $data,$length;
    public function __construct($data,int $length){
        $this->data = $data;
        $this->length = $length;
    }
    public function getDate(){
        return $this->data;
    }
    public function getLength():int{
        return $this->length;
    }
}