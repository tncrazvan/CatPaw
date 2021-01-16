<?php
namespace com\github\tncrazvan\catpaw\attributes;

use com\github\tncrazvan\catpaw\attributes\interfaces\AttributeInterface;
use com\github\tncrazvan\catpaw\attributes\traits\CoreAttributeDefinition;

#[\Attribute]
class Consumes implements AttributeInterface{
    use CoreAttributeDefinition;

    private string $content_type;
    private string $classname;

    public function __construct(string $content_type, string $classname){
        $this->content_type = $content_type;
        $this->classname = $classname;
    }

    public function getContentType():string{
        return $this->content_type;
    }

    public function getClassName():string{
        return $this->classname;
    }
}