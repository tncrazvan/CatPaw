<?php
namespace net\razshare\catpaw\attributes\sessions;

use net\razshare\catpaw\attributes\interfaces\AttributeInterface;
use net\razshare\catpaw\attributes\traits\CoreAttributeDefinition;

#[\Attribute]
class SessionID implements AttributeInterface{
    use CoreAttributeDefinition;
}