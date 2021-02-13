<?php
namespace com\github\tncrazvan\catpaw\attributes\sessions;

use com\github\tncrazvan\catpaw\attributes\interfaces\AttributeInterface;
use com\github\tncrazvan\catpaw\attributes\traits\CoreAttributeDefinition;

#[\Attribute]
class SessionID implements AttributeInterface{
    use CoreAttributeDefinition;
}