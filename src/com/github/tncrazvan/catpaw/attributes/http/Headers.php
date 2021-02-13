<?php
namespace com\github\tncrazvan\catpaw\attributes\http;

use com\github\tncrazvan\catpaw\attributes\interfaces\AttributeInterface;
use com\github\tncrazvan\catpaw\attributes\traits\CoreAttributeDefinition;

#[\Attribute]
class Headers implements AttributeInterface{
    use CoreAttributeDefinition;
}