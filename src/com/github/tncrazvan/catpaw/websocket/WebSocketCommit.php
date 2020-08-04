<?php
namespace com\github\tncrazvan\catpaw\websocket;

class WebSocketCommit{
    private $data;
    public function __construct(&$data){
        $this->data = $data;
    }
    public function &getData(){
        return $this->data;
    }
}