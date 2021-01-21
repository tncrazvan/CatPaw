<?php
namespace com\github\tncrazvan\catpaw\attributes\http;

use com\github\tncrazvan\catpaw\attributes\interfaces\AttributeInterface;
use com\github\tncrazvan\catpaw\attributes\traits\CoreAttributeDefinition;
use com\github\tncrazvan\catpaw\tools\Strings;

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