<?php
namespace com\github\tncrazvan\catpaw\attributes\database;

use com\github\tncrazvan\catpaw\attributes\interfaces\AttributeInterface;
use com\github\tncrazvan\catpaw\attributes\traits\CoreAttributeDefinition;

/**
 * Ignore this column when building a `select` query.
 */
#[\Attribute]
class IgnoreSelect implements AttributeInterface{
    use CoreAttributeDefinition;
}