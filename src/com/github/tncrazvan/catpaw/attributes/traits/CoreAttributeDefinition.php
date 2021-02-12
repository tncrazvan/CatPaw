<?php
namespace com\github\tncrazvan\catpaw\attributes\traits;

use com\github\tncrazvan\catpaw\tools\AttributeResolver;

trait CoreAttributeDefinition{
    public static function findByFunction(\ReflectionFunction $reflection_function):?static{
        if(!AttributeResolver::issetFunctionAttribute($reflection_function,static::class))
            return null;
        return new static(...AttributeResolver::getFunctionAttributeArguments($reflection_function,static::class));
    }

    public static function findByMethod(\ReflectionMethod $reflection_method):?static{
        if(!AttributeResolver::issetMethodAttribute($reflection_method,static::class))
            return null;
        return new static(...AttributeResolver::getMethodAttributeArguments($reflection_method,static::class));
    }

    public static function findByClass(\ReflectionClass $reflection_class):?static{
        if(!AttributeResolver::issetClassAttribute($reflection_class,static::class))
            return null;
        return new static(...AttributeResolver::getClassAttributeArguments($reflection_class,static::class));
    }

    public static function findByProperty(\ReflectionProperty $reflection_property):?static{
        if(!AttributeResolver::issetPropertyAttribute($reflection_property,static::class))
            return null;
        return new static(...AttributeResolver::getPropertyAttributeArguments($reflection_property,static::class));
    }

    public static function findByParameter(\ReflectionParameter $reflection_parameter):?static{
        if(!AttributeResolver::issetParameterAttribute($reflection_parameter,static::class))
            return null;
        return new static(...AttributeResolver::getParameterAttributeArguments($reflection_parameter,static::class));
    }
}