<?php
namespace net\razshare\catpaw\attributes;

use net\razshare\catpaw\attributes\interfaces\AttributeInterface;
use net\razshare\catpaw\attributes\traits\CoreAttributeDefinition;

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