<?php
namespace com\github\tncrazvan\catpaw\attributes;

use com\github\tncrazvan\catpaw\attributes\interfaces\AttributeInterface;
use com\github\tncrazvan\catpaw\attributes\traits\CoreAttributeDefinition;

#[\Attribute]
class Entity implements AttributeInterface{
    use CoreAttributeDefinition;

    public function __construct(
        private string $tableName = ''
    ){}

    public function getTableName():string{
        return $this->tableName;
    }
}