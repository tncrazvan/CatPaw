<?php
namespace net\razshare\catpaw\attributes;

use net\razshare\catpaw\attributes\interfaces\AttributeInterface;
use net\razshare\catpaw\attributes\traits\CoreAttributeDefinition;

#[\Attribute]
class Consumes implements AttributeInterface{
    use CoreAttributeDefinition;

    private string $content_type;

    public function __construct(string $content_type){
        $this->content_type = $content_type;
    }

    public function getContentType():string{
        return $this->content_type;
    }
}