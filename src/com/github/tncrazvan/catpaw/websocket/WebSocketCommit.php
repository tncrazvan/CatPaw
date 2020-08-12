<?php
namespace com\github\tncrazvan\catpaw\websocket;

class WebSocketCommit{
    private $data;
    private bool $binary;
    public function __construct(&$data,bool $binary){
        $this->data = $data;
        $this->binary = $binary;
    }
    public function &getData(){
        return $this->data;
    }
    public function isBinary():bool{
        return $this->binary;
    }
}