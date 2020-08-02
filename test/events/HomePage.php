<?php
use com\github\tncrazvan\catpaw\http\HttpClassEvent;
class HomePage extends HttpClassEvent{
    private string $method;
    private ?User $user;
    public function __construct(string $method, ?User &$user){
        $this->method = $method;
        $this->user = $user;
    }
    public function run(){
        return "hello world";
    }
}
