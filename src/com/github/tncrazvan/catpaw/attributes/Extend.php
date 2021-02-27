<?php
namespace com\github\tncrazvan\catpaw\attributes;

use com\github\tncrazvan\catpaw\attributes\interfaces\AttributeInterface;
use com\github\tncrazvan\catpaw\attributes\traits\CoreAttributeDefinition;

#[\Attribute]
class Extend implements AttributeInterface{
    use CoreAttributeDefinition;

    public function __construct(
        private string $className
    ){}

    public function getClassName():string{
        return $this->className;
    }
}