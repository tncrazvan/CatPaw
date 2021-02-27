<?php
namespace com\github\tncrazvan\catpaw\attributes\http\methods;

use com\github\tncrazvan\catpaw\attributes\interfaces\AttributeInterface;
use com\github\tncrazvan\catpaw\attributes\traits\CoreAttributeDefinition;

#[\Attribute]
class OPTIONS implements AttributeInterface{
    use CoreAttributeDefinition;
}