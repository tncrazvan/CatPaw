<?php
namespace com\github\tncrazvan\catpaw\http;

class HttpCommit{
    private $data;
    private int $length;
    public function __construct(&$data,int $length){
        $this->data = $data;
        $this->length = $length;
    }
    public function &getData(){
        return $this->data;
    }
    public function getLength():int{
        return $this->length;
    }
}