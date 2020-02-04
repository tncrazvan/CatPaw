<?php
namespace com\github\tncrazvan\CatPaw\WebSocket;

use com\github\tncrazvan\CatPaw\WebSocket\WebSocketEvent;

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