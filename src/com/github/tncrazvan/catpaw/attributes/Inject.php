<?php
namespace com\github\tncrazvan\catpaw\attributes;

use com\github\tncrazvan\catpaw\attributes\interfaces\AttributeInterface;
use com\github\tncrazvan\catpaw\attributes\traits\CoreAttributeDefinition;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;

#[\Attribute]
class Inject implements AttributeInterface{
    use CoreAttributeDefinition;
}