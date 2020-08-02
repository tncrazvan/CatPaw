<?php
namespace com\github\tncrazvan\catpaw\test\events\http\homepage;
use com\github\tncrazvan\catpaw\http\HttpClassEvent;
use com\github\tncrazvan\catpaw\test\models\homepage\User;

class HomePage extends HttpClassEvent{
    private string $method;
    private ?User $user;
    public function __construct(string $method, ?User &$user){
        $this->method = $method;
        $this->user = $user;
    }
    public function &run(){
        $result = "hello world";
        return $result;
    }
}
