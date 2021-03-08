<?php
namespace com\github\tncrazvan\catpaw\attributes\http;

use com\github\tncrazvan\catpaw\attributes\interfaces\AttributeInterface;
use com\github\tncrazvan\catpaw\attributes\traits\CoreAttributeDefinition;

#[\Attribute]
class Query implements AttributeInterface{
    use CoreAttributeDefinition;
    public function __construct(
        private string $name
    ){}

    public function getName():string{
        return $this->name;
    }
}