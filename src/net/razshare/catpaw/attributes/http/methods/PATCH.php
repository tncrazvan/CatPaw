<?php
namespace net\razshare\catpaw\attributes\http\methods;

use net\razshare\catpaw\attributes\interfaces\AttributeInterface;
use net\razshare\catpaw\attributes\traits\CoreAttributeDefinition;

#[\Attribute]
class PATCH implements AttributeInterface{
    use CoreAttributeDefinition;
}