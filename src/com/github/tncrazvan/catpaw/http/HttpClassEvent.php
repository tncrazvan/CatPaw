<?php
namespace com\github\tncrazvan\catpaw\http;
abstract class HttpClassEvent{
    private $method;
    public function __construct(string &$method){
        $this->method = $method;
    }
    public function &getMethod():string{
        return $this->method;
    }
}