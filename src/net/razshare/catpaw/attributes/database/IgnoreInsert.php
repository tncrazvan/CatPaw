<?php
namespace net\razshare\catpaw\attributes\database;

use net\razshare\catpaw\attributes\interfaces\AttributeInterface;
use net\razshare\catpaw\attributes\traits\CoreAttributeDefinition;

/**
 * Ignore this column when building an `insert` query.
 */
#[\Attribute]
class IgnoreInsert implements AttributeInterface{
    use CoreAttributeDefinition;
}