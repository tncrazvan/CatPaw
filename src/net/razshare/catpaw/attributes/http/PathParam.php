<?php
namespace net\razshare\catpaw\attributes\http;

use net\razshare\catpaw\attributes\interfaces\AttributeInterface;
use net\razshare\catpaw\attributes\traits\CoreAttributeDefinition;

#[\Attribute]
class PathParam implements AttributeInterface{
    use CoreAttributeDefinition;

    public function __construct(
        private string $regex = '[\w\d\-_\.\~%]+'
    ){}

    public function getRegex():string{
        return $this->regex;
    }
    public function setRegex(string $regex):void{
        $this->regex = $regex;
    }
}