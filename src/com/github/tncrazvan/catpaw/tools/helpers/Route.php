<?php
namespace com\github\tncrazvan\catpaw\tools\helpers;

use ReflectionClass;
use ReflectionParameter;

class Route{
    private static array $httpEvents = [];
    
    private static function resolveTarget(string $method,$target){
        if(is_string($target)){
            $reflectionClass = new ReflectionClass($target);
            $fname = trim($method);
            $reflectionMethod = $reflectionClass->getMethod($fname);
            $reflectionParameters = $reflectionMethod->getParameters();
            $size = $reflectionMethod->getNumberOfParameters();
            $namedAndTypedParams = array();
            $namedParams = array();
            foreach($reflectionParameters as $reflectionParameter){
                $name = $reflectionParameter->getName();
                $type = $reflectionParameter->getType()->getName();
                $namedAndTypedParams[] = "$type \$$name";
                $namedParams[] = "\$$name";
            }
            $namedAndTypedParamsString = \implode(",",$namedAndTypedParams);
            $namedParamsString = \implode(",",$namedParams);
            
            $script =<<<EOF
            return function($namedAndTypedParamsString){
                \$instance = new $target();
                return \$instance->$fname($namedParamsString);
            };
            EOF;
            return eval($script);
        }
        return $target;
    }

    public static function forward(string $from, string $to):void{
        if(!isset(self::$httpEvents["@forward"]))
            self::$httpEvents["@forward"][$from] = $to;
    }

    public static function notFound( $block):void{
        if(!isset(self::$httpEvents["@404"]))
            self::$httpEvents["@404"] = array();

        self::$httpEvents["@404"]["GET"] = static::resolveTarget("GET",$block);
    }

    public static function copy(string $path, $block):void{
        if(!isset(self::$httpEvents[$path]))
            self::$httpEvents[$path] = array();

        self::$httpEvents[$path]["COPY"] = static::resolveTarget("COPY",$block);
    }

    public static function delete(string $path, $block):void{
        if(!isset(self::$httpEvents[$path]))
            self::$httpEvents[$path] = array();

        self::$httpEvents[$path]["DELETE"] = static::resolveTarget("DELETE",$block);
    }

    public static function get(string $path, $block):void{
        if(!isset(self::$httpEvents[$path]))
            self::$httpEvents[$path] = array();

        self::$httpEvents[$path]["GET"] = static::resolveTarget("GET",$block);
    }

    public static function head(string $path, $block):void{
        if(!isset(self::$httpEvents[$path]))
            self::$httpEvents[$path] = array();

        self::$httpEvents[$path]["HEAD"] = static::resolveTarget("HEAD",$block);
    }
    
    public static function link(string $path, $block):void{
        if(!isset(self::$httpEvents[$path]))
            self::$httpEvents[$path] = array();

        self::$httpEvents[$path]["LINK"] = static::resolveTarget("LINK",$block);
    }
    
    public static function lock(string $path, $block):void{
        if(!isset(self::$httpEvents[$path]))
            self::$httpEvents[$path] = array();

        self::$httpEvents[$path]["LOCK"] = static::resolveTarget("LOCK",$block);
    }
    
    public static function options(string $path, $block):void{
        if(!isset(self::$httpEvents[$path]))
            self::$httpEvents[$path] = array();

        self::$httpEvents[$path]["OPTIONS"] = static::resolveTarget("OPTIONS",$block);
    }
    
    public static function patch(string $path, $block):void{
        if(!isset(self::$httpEvents[$path]))
            self::$httpEvents[$path] = array();

        self::$httpEvents[$path]["PATCH"] = static::resolveTarget("PATCH",$block);
    }
    
    public static function post(string $path, $block):void{
        if(!isset(self::$httpEvents[$path]))
            self::$httpEvents[$path] = array();

        self::$httpEvents[$path]["POST"] = static::resolveTarget("POST",$block);
    }
    
    public static function propfind(string $path, $block):void{
        if(!isset(self::$httpEvents[$path]))
            self::$httpEvents[$path] = array();

        self::$httpEvents[$path]["PROPFIND"] = static::resolveTarget("PROPFIND",$block);
    }
    
    public static function purge(string $path, $block):void{
        if(!isset(self::$httpEvents[$path]))
            self::$httpEvents[$path] = array();

        self::$httpEvents[$path]["PURGE"] = static::resolveTarget("PURGE",$block);
    }
    
    public static function put(string $path, $block):void{
        if(!isset(self::$httpEvents[$path]))
            self::$httpEvents[$path] = array();

        self::$httpEvents[$path]["PUT"] = static::resolveTarget("PUT",$block);
    }
    
    public static function unknown(string $path, $block):void{
        if(!isset(self::$httpEvents[$path]))
            self::$httpEvents[$path] = array();

        self::$httpEvents[$path]["UNKNOWN"] = static::resolveTarget("UNKNOWN",$block);
    }
    
    public static function unlink(string $path, $block):void{
        if(!isset(self::$httpEvents[$path]))
            self::$httpEvents[$path] = array();

        self::$httpEvents[$path]["UNLINK"] = $block;
    }
    
    public static function unlock(string $path, $block):void{
        if(!isset(self::$httpEvents[$path]))
            self::$httpEvents[$path] = array();

        self::$httpEvents[$path]["UNLOCK"] = static::resolveTarget("UNLOCK",$block);
    }
    
    public static function view(string $path, $block):void{
        if(!isset(self::$httpEvents[$path]))
            self::$httpEvents[$path] = array();

        self::$httpEvents[$path]["VIEW"] = static::resolveTarget("VIEW",$block);
    }
    

    public static function &getHttpEvents():array{
        return self::$httpEvents;
    }
}