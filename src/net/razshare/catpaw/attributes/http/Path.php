<?php
namespace net\razshare\catpaw\attributes\http;

use net\razshare\catpaw\attributes\interfaces\AttributeInterface;
use net\razshare\catpaw\attributes\traits\CoreAttributeDefinition;

#[\Attribute]
class Path implements AttributeInterface{
    use CoreAttributeDefinition;
    private string $value;
    public function __construct(string $value = "/"){
        $this->value = $value;
    }

    public function getValue():string{
        return $this->value;
    }
}