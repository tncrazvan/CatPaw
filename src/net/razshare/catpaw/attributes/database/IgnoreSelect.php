<?php
namespace net\razshare\catpaw\attributes\database;

use net\razshare\catpaw\attributes\interfaces\AttributeInterface;
use net\razshare\catpaw\attributes\traits\CoreAttributeDefinition;

/**
 * Ignore this column when building a `select` query.
 */
#[\Attribute]
class IgnoreSelect implements AttributeInterface{
    use CoreAttributeDefinition;
}