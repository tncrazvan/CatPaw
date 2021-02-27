<?php
namespace com\github\tncrazvan\catpaw\attributes\interfaces;

interface AttributeInterface{
    public static function findByMethod(\ReflectionMethod $reflection_method):?object;
    public static function findByClass(\ReflectionClass $reflection_class):?object;
    public static function findByProperty(\ReflectionProperty $reflection_property):?object;
}