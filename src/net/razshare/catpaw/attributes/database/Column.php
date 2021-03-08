<?php
namespace net\razshare\catpaw\attributes\database;

use net\razshare\catpaw\attributes\interfaces\AttributeInterface;
use net\razshare\catpaw\attributes\traits\CoreAttributeDefinition;

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