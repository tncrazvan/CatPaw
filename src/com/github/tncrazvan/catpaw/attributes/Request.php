<?php
namespace com\github\tncrazvan\catpaw\attributes;

use com\github\tncrazvan\catpaw\attributes\interfaces\AttributeInterface;
use com\github\tncrazvan\catpaw\attributes\traits\CoreAttributeDefinition;

#[\Attribute]
class Request implements AttributeInterface{
    use CoreAttributeDefinition;
}