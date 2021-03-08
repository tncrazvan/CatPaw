<?php

namespace net\razshare\catpaw\attributes;

use net\razshare\catpaw\attributes\Inject;
use net\razshare\catpaw\attributes\Singleton;
use net\razshare\catpaw\attributes\http\methods\COPY;
use net\razshare\catpaw\attributes\http\methods\DELETE;
use net\razshare\catpaw\attributes\http\methods\GET;
use net\razshare\catpaw\attributes\http\methods\HEAD;
use net\razshare\catpaw\attributes\http\methods\LINK;
use net\razshare\catpaw\attributes\http\methods\LOCK;
use net\razshare\catpaw\attributes\http\methods\OPTIONS;
use net\razshare\catpaw\attributes\http\methods\PATCH;
use net\razshare\catpaw\attributes\http\methods\POST;
use net\razshare\catpaw\attributes\http\methods\PROPFIND;
use net\razshare\catpaw\attributes\http\methods\PURGE;
use net\razshare\catpaw\attributes\http\methods\PUT;
use net\razshare\catpaw\attributes\http\methods\UNKNOWN;
use net\razshare\catpaw\attributes\http\methods\UNLINK;
use net\razshare\catpaw\attributes\http\methods\UNLOCK;
use net\razshare\catpaw\attributes\http\methods\VIEW;
use net\razshare\catpaw\tools\helpers\Factory;

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

    public static function issetClassAttribute(\ReflectionClass $reflection_class, string $attribute_name):bool{
        $attributes = $reflection_class->getAttributes();
        foreach($attributes as &$attribute){
            $local_attribute_name = $attribute->getName();
            if($local_attribute_name === $attribute_name)
                return true;
            
        }
        return false;
    }

    public static function getFunctionAttributeArguments(\ReflectionFunction $reflection_function, string $attribute_name):?array{
        $attributes = $reflection_function->getAttributes();
        foreach($attributes as &$attribute){
            $classname = $attribute->getName();
            if($classname === $attribute_name)
                return $attribute->getArguments();
        }
        return null;
    }

    public static function issetFunctionAttribute(\ReflectionFunction $reflection_function, string $attribute_name):bool{
        $attributes = $reflection_function->getAttributes();
        foreach($attributes as &$attribute){
            $classname = $attribute->getName();
            if($classname === $attribute_name)
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

    public static function getParameterAttributeArguments(\ReflectionParameter $reflection_parameter, string $attribute_name):?array{
        $attributes = $reflection_parameter->getAttributes();
        foreach($attributes as &$attribute){
            $classname = $attribute->getName();
            if($classname === $attribute_name)
                return $attribute->getArguments();
        }
        return null;
    }

    public static function issetParameterAttribute(\ReflectionParameter $reflection_parameter, string $attribute_name):bool{
        $attributes = $reflection_parameter->getAttributes();
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

    
    private static $_props_resolved = [];
    /**
     * Properties injections are resolved at runtime not launch time.<br />
     * However property injections themselves are singletons and they will be resolved only ONCE and then reused
     * for all subsequent executions.
     */
    public static function injectProperties(string &$classname,$instance):void{
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
        if(!Factory::isset($proptype)){
        //if(!isset(Singleton::$map[$proptype])){
            $obj = Factory::make($proptype);
            static::injectProperties($proptype,$obj);
            //Singleton::$map[$proptype] = $obj;
            Factory::setObject($proptype,$obj);
            $prop->setValue($instance,$obj);
        }else
            $prop->setValue($instance,Factory::make($proptype));
    }
}