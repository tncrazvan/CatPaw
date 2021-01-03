<?php

namespace com\github\tncrazvan\catpaw\tools;

use com\github\tncrazvan\catpaw\attributes\Inject;
use com\github\tncrazvan\catpaw\attributes\Singleton;
use com\github\tncrazvan\catpaw\attributes\http\Path;
use com\github\tncrazvan\catpaw\attributes\http\methods\COPY;
use com\github\tncrazvan\catpaw\attributes\http\methods\DELETE;
use com\github\tncrazvan\catpaw\attributes\http\methods\GET;
use com\github\tncrazvan\catpaw\attributes\http\methods\HEAD;
use com\github\tncrazvan\catpaw\attributes\http\methods\LINK;
use com\github\tncrazvan\catpaw\attributes\http\methods\LOCK;
use com\github\tncrazvan\catpaw\attributes\http\methods\OPTIONS;
use com\github\tncrazvan\catpaw\attributes\http\methods\PATCH;
use com\github\tncrazvan\catpaw\attributes\http\methods\POST;
use com\github\tncrazvan\catpaw\attributes\http\methods\PROPFIND;
use com\github\tncrazvan\catpaw\attributes\http\methods\PURGE;
use com\github\tncrazvan\catpaw\attributes\http\methods\PUT;
use com\github\tncrazvan\catpaw\attributes\http\methods\UNKNOWN;
use com\github\tncrazvan\catpaw\attributes\http\methods\UNLINK;
use com\github\tncrazvan\catpaw\attributes\http\methods\UNLOCK;
use com\github\tncrazvan\catpaw\attributes\http\methods\VIEW;

class AttributeResolver{
    public static function getClassAttributeArguments(\ReflectionClass $reflection_class, string $attribute_name):?array{
        $attributes = $reflection_class->getAttributes();
        foreach($attributes as &$attribute){
            $local_attribute_name = $attribute->getName();
            if($local_attribute_name === $attribute_name)
                return $attribute->getArguments();
        }
        return null;
    }

    public static function issetClassAttribute(\ReflectionClass $reflection_class, string ...$attribute_names):bool{
        $attributes = $reflection_class->getAttributes();
        foreach($attributes as &$attribute){
            $classname = $attribute->getName();
            if(in_array($classname,$attribute_names,true))
                return true;
            
        }
        return false;
    }

    public static function getMethodAttributeArguments(\ReflectionMethod $reflection_method, string $attribute_name):?array{
        $attributes = $reflection_method->getAttributes();
        foreach($attributes as &$attribute){
            $classname = $attribute->getName();
            if($classname === $attribute_name)
                return $attribute->getArguments();
        }
        return null;
    }

    public static function issetMethodAttribute(\ReflectionMethod $reflection_method, string $attribute_name):bool{
        $attributes = $reflection_method->getAttributes();
        foreach($attributes as &$attribute){
            $classname = $attribute->getName();
            if($classname === $attribute_name)
                return true;
        }
        return false;
    }

    public static function getPropertyAttributeArguments(\ReflectionProperty $reflection_property, string $attribute_name):?array{
        $attributes = $reflection_property->getAttributes();
        foreach($attributes as &$attribute){
            $classname = $attribute->getName();
            if($classname === $attribute_name)
                return $attribute->getArguments();
        }
        return null;
    }

    public static function issetPropertyAttribute(\ReflectionProperty $reflection_property, string $attribute_name):bool{
        $attributes = $reflection_property->getAttributes();
        foreach($attributes as &$attribute){
            $classname = $attribute->getName();
            if($classname === $attribute_name)
                return true;
        }
        return false;
    }

    public static function getMethodAttribute(\ReflectionMethod $method):array{
        return [
            "COPY" => 
            static::issetMethodAttribute($method,COPY::class),
            "DELETE" =>
            static::issetMethodAttribute($method,DELETE::class),
            "GET" => 
            static::issetMethodAttribute($method,GET::class),
            "HEAD" => 
            static::issetMethodAttribute($method,HEAD::class),
            "LINK" => 
            static::issetMethodAttribute($method,LINK::class),
            "LOCK" => 
            static::issetMethodAttribute($method,LOCK::class),
            "OPTIONS" => 
            static::issetMethodAttribute($method,OPTIONS::class),
            "PATCH" => 
            static::issetMethodAttribute($method,PATCH::class),
            "POST" => 
            static::issetMethodAttribute($method,POST::class),
            "PROPFIND" => 
            static::issetMethodAttribute($method,PROPFIND::class),
            "PURGE" => 
            static::issetMethodAttribute($method,PURGE::class),
            "PUT" => 
            static::issetMethodAttribute($method,PUT::class),
            "UNKNOWN" => 
            static::issetMethodAttribute($method,UNKNOWN::class),
            "UNLINK" => 
            static::issetMethodAttribute($method,UNLINK::class),
            "UNLOCK" => 
            static::issetMethodAttribute($method,UNLOCK::class),
            "VIEW" => 
            static::issetMethodAttribute($method,VIEW::class)
        ];
    }

    public static function resolveMethodAttributes(\ReflectionMethod $method, array &$map, int $i):void{
        $p = static::getMethodAttributeArguments($method,Path::class);
        $path = $p && count($p) > 0?$p[0]:'';

        $http_methods = static::getMethodAttribute($method);

        $http_method = '';
        foreach($http_methods as $key => &$httpm){
            if($httpm){
                $http_method = $key;
                break;
            }
        }

        if('' !== $http_method || '' !== $path){
            $map[$i] = [
                "method" => '' !== $http_method?$http_method:"GET",
                "path" => '' !== $path?\preg_replace('/^\/+/','', $path):'',
                "fname" => $method->getName(),
            ];
        }
    }


    
    private static $_props_resolved = [];
    /**
     * Properties injections are resolved at runtime not launch time.<br />
     * However property injections themselves are singletons and they will be resolved only ONCE and then reused
     * for all subsequent executions.
     */
    public static function resolveClassPropertiesAttributes(string &$classname,$instance):void{
        if(isset(static::$_props_resolved[$classname])) return;
        static::$_props_resolved[] = $classname;
        $reflectionClass = new \ReflectionClass($classname);
        $props = $reflectionClass->getProperties();
        foreach($props as &$prop){
            if(static::issetPropertyAttribute($prop,Inject::class)){
                $prop->setAccessible(true);
                static::injectProperty($prop,$classname,$instance);
                $prop->setAccessible(false);
            }
        }
    }

    public static function injectProperty(\ReflectionProperty $prop,string &$classname,&$instance):void{
        if(
            $prop->isInitialized($instance) 
            || '' === $classname 
            || 'string' === $classname 
            || 'array' === $classname 
            || 'int' === $classname 
            || 'bool' === $classname
            ) return;
        $proptype = $prop->getType()->getName();
        if(!isset(Singleton::$map[$proptype])){
            $obj = new $proptype();
            static::resolveClassPropertiesAttributes($proptype,$obj);
            Singleton::$map[$proptype] = $obj;
        }
        $prop->setValue($instance,Singleton::$map[$proptype]);
    }
}