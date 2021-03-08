<?php
namespace net\razshare\catpaw\attributes;

use net\razshare\catpaw\attributes\interfaces\AttributeInterface;
use net\razshare\catpaw\attributes\traits\CoreAttributeDefinition;

#[\Attribute]
class Request implements AttributeInterface{
    use CoreAttributeDefinition;
}