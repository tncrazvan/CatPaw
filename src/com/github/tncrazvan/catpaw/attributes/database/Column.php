<?php
namespace com\github\tncrazvan\catpaw\attributes\database;

use com\github\tncrazvan\catpaw\attributes\interfaces\AttributeInterface;
use com\github\tncrazvan\catpaw\attributes\traits\CoreAttributeDefinition;

#[\Attribute]
class Column implements AttributeInterface{
    use CoreAttributeDefinition;

    public function __construct(
        private int $type = -1,
        private string $name = ''
    ){}

    public function getName():string{
        return $this->name;
    }

    public function getType():int{
        return $this->type;
    }
}