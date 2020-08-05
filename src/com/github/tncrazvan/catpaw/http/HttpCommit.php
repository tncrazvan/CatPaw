<?php
namespace com\github\tncrazvan\catpaw\http;

class HttpCommit{
    private $data;
    public function __construct(&$data){
        $this->data = $data;
    }
    public function &getData(){
        return $this->data;
    }
}