<?php
namespace net\razshare\catpaw\attributes\http;

use net\razshare\catpaw\attributes\interfaces\AttributeInterface;
use net\razshare\catpaw\attributes\traits\CoreAttributeDefinition;

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