<?php
namespace net\razshare\catpaw\attributes\http;

use net\razshare\catpaw\attributes\Singleton;

#[\Attribute]
class Path extends Singleton {
    private string $value;
    public function __construct(string $value = "/"){
        $this->value = $value;
    }

    public function getValue():string{
        return $this->value;
    }
}