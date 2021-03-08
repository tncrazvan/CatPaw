<?php
namespace net\razshare\catpaw\attributes\database;

use net\razshare\catpaw\attributes\interfaces\AttributeInterface;
use net\razshare\catpaw\attributes\traits\CoreAttributeDefinition;

/**
 * Ignore this column when building an `update` query.
 */
#[\Attribute]
class IgnoreUpdate implements AttributeInterface{
    use CoreAttributeDefinition;
}