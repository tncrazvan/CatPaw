<?php
namespace net\razshare\catpaw\attributes;

use net\razshare\catpaw\attributes\interfaces\AttributeInterface;
use net\razshare\catpaw\attributes\traits\CoreAttributeDefinition;

#[\Attribute]
class Produces implements AttributeInterface{
    use CoreAttributeDefinition;

    private string $content_types;

    public function __construct(string $content_types){
        $this->content_types = $content_types;
    }

    public function getContentType():string{
        return $this->content_types;
    }
}