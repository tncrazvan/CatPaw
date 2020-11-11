<?php
namespace com\github\tncrazvan\catpaw\tools\helpers;

use ReflectionClass;

class Route{
    private static array $httpEvents = [];
    private static string $currentTarget = '';
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

    public static function target(string $classname):Route{
        static::$currentTarget = $classname;
        return static;
    }

    public static function untarget():void{
        static::$currentTarget = '';
        return static;
    }

    public static function forward(string $from, string $to):void{
        if(!isset(self::$httpEvents["@forward"]))
            self::$httpEvents["@forward"][$from] = $to;
    }

    public static function notFound( $block):void{
        if(!isset(self::$httpEvents["@404"]))
            self::$httpEvents["@404"] = array();

        self::$httpEvents["@404"]["GET"] = static::resolveTarget("GET",static::$currentTarget);
    }

    public static function copy(string $path):Route{
        if(!isset(self::$httpEvents[$path]))
            self::$httpEvents[$path] = array();

        self::$httpEvents[$path]["COPY"] = static::resolveTarget("COPY",static::$currentTarget);
        return static;
    }

    public static function delete(string $path):Route{
        if(!isset(self::$httpEvents[$path]))
            self::$httpEvents[$path] = array();

        self::$httpEvents[$path]["DELETE"] = static::resolveTarget("DELETE",static::$currentTarget);
        return static;
    }

    public static function get(string $path):Route{
        if(!isset(self::$httpEvents[$path]))
            self::$httpEvents[$path] = array();

        self::$httpEvents[$path]["GET"] = static::resolveTarget("GET",static::$currentTarget);
        return static;
    }

    public static function head(string $path):Route{
        if(!isset(self::$httpEvents[$path]))
            self::$httpEvents[$path] = array();

        self::$httpEvents[$path]["HEAD"] = static::resolveTarget("HEAD",static::$currentTarget);
        return static;
    }
    
    public static function link(string $path):Route{
        if(!isset(self::$httpEvents[$path]))
            self::$httpEvents[$path] = array();

        self::$httpEvents[$path]["LINK"] = static::resolveTarget("LINK",static::$currentTarget);
        return static;
    }
    
    public static function lock(string $path):Route{
        if(!isset(self::$httpEvents[$path]))
            self::$httpEvents[$path] = array();

        self::$httpEvents[$path]["LOCK"] = static::resolveTarget("LOCK",static::$currentTarget);
        return static;
    }
    
    public static function options(string $path):Route{
        if(!isset(self::$httpEvents[$path]))
            self::$httpEvents[$path] = array();

        self::$httpEvents[$path]["OPTIONS"] = static::resolveTarget("OPTIONS",static::$currentTarget);
        return static;
    }
    
    public static function patch(string $path):Route{
        if(!isset(self::$httpEvents[$path]))
            self::$httpEvents[$path] = array();

        self::$httpEvents[$path]["PATCH"] = static::resolveTarget("PATCH",static::$currentTarget);
        return static;
    }
    
    public static function post(string $path):Route{
        if(!isset(self::$httpEvents[$path]))
            self::$httpEvents[$path] = array();

        self::$httpEvents[$path]["POST"] = static::resolveTarget("POST",static::$currentTarget);
        return static;
    }
    
    public static function propfind(string $path):Route{
        if(!isset(self::$httpEvents[$path]))
            self::$httpEvents[$path] = array();

        self::$httpEvents[$path]["PROPFIND"] = static::resolveTarget("PROPFIND",static::$currentTarget);
        return static;
    }
    
    public static function purge(string $path):Route{
        if(!isset(self::$httpEvents[$path]))
            self::$httpEvents[$path] = array();

        self::$httpEvents[$path]["PURGE"] = static::resolveTarget("PURGE",static::$currentTarget);
        return static;
    }
    
    public static function put(string $path):Route{
        if(!isset(self::$httpEvents[$path]))
            self::$httpEvents[$path] = array();

        self::$httpEvents[$path]["PUT"] = static::resolveTarget("PUT",static::$currentTarget);
        return static;
    }
    
    public static function unknown(string $path):Route{
        if(!isset(self::$httpEvents[$path]))
            self::$httpEvents[$path] = array();

        self::$httpEvents[$path]["UNKNOWN"] = static::resolveTarget("UNKNOWN",static::$currentTarget);
        return static;
    }
    
    public static function unlink(string $path):Route{
        if(!isset(self::$httpEvents[$path]))
            self::$httpEvents[$path] = array();

        self::$httpEvents[$path]["UNLINK"] = static::resolveTarget("UNLINK",static::$currentTarget);
        return static;
    }
    
    public static function unlock(string $path):Route{
        if(!isset(self::$httpEvents[$path]))
            self::$httpEvents[$path] = array();

        self::$httpEvents[$path]["UNLOCK"] = static::resolveTarget("UNLOCK",static::$currentTarget);
        return static;
    }
    
    public static function view(string $path):Route{
        if(!isset(self::$httpEvents[$path]))
            self::$httpEvents[$path] = array();

        self::$httpEvents[$path]["VIEW"] = static::resolveTarget("VIEW",static::$currentTarget);
        return static;
    }
    

    public static function &getHttpEvents():array{
        return self::$httpEvents;
    }
}