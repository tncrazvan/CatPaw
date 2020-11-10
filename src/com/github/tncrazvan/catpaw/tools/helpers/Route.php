<?php
namespace com\github\tncrazvan\catpaw\tools\helpers;

class Route{
    private static array $httpEvents = [];
    
    private static function resolveTarget($target){
        if(is_string($target)){
            return fn() => new $target();
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

        self::$httpEvents["@404"]["GET"] = static::resolveTarget($block);
    }

    public static function copy(string $path, $block):void{
        if(!isset(self::$httpEvents[$path]))
            self::$httpEvents[$path] = array();

        self::$httpEvents[$path]["COPY"] = static::resolveTarget($block);
    }

    public static function delete(string $path, $block):void{
        if(!isset(self::$httpEvents[$path]))
            self::$httpEvents[$path] = array();

        self::$httpEvents[$path]["DELETE"] = static::resolveTarget($block);
    }

    public static function get(string $path, $block):void{
        if(!isset(self::$httpEvents[$path]))
            self::$httpEvents[$path] = array();

        self::$httpEvents[$path]["GET"] = static::resolveTarget($block);
    }

    public static function head(string $path, $block):void{
        if(!isset(self::$httpEvents[$path]))
            self::$httpEvents[$path] = array();

        self::$httpEvents[$path]["HEAD"] = static::resolveTarget($block);
    }
    
    public static function link(string $path, $block):void{
        if(!isset(self::$httpEvents[$path]))
            self::$httpEvents[$path] = array();

        self::$httpEvents[$path]["LINK"] = static::resolveTarget($block);
    }
    
    public static function lock(string $path, $block):void{
        if(!isset(self::$httpEvents[$path]))
            self::$httpEvents[$path] = array();

        self::$httpEvents[$path]["LOCK"] = static::resolveTarget($block);
    }
    
    public static function options(string $path, $block):void{
        if(!isset(self::$httpEvents[$path]))
            self::$httpEvents[$path] = array();

        self::$httpEvents[$path]["OPTIONS"] = static::resolveTarget($block);
    }
    
    public static function patch(string $path, $block):void{
        if(!isset(self::$httpEvents[$path]))
            self::$httpEvents[$path] = array();

        self::$httpEvents[$path]["PATCH"] = static::resolveTarget($block);
    }
    
    public static function post(string $path, $block):void{
        if(!isset(self::$httpEvents[$path]))
            self::$httpEvents[$path] = array();

        self::$httpEvents[$path]["POST"] = static::resolveTarget($block);
    }
    
    public static function propfind(string $path, $block):void{
        if(!isset(self::$httpEvents[$path]))
            self::$httpEvents[$path] = array();

        self::$httpEvents[$path]["PROPFIND"] = static::resolveTarget($block);
    }
    
    public static function purge(string $path, $block):void{
        if(!isset(self::$httpEvents[$path]))
            self::$httpEvents[$path] = array();

        self::$httpEvents[$path]["PURGE"] = static::resolveTarget($block);
    }
    
    public static function put(string $path, $block):void{
        if(!isset(self::$httpEvents[$path]))
            self::$httpEvents[$path] = array();

        self::$httpEvents[$path]["PUT"] = static::resolveTarget($block);
    }
    
    public static function unknown(string $path, $block):void{
        if(!isset(self::$httpEvents[$path]))
            self::$httpEvents[$path] = array();

        self::$httpEvents[$path]["UNKNOWN"] = static::resolveTarget($block);
    }
    
    public static function unlink(string $path, $block):void{
        if(!isset(self::$httpEvents[$path]))
            self::$httpEvents[$path] = array();

        self::$httpEvents[$path]["UNLINK"] = $block;
    }
    
    public static function unlock(string $path, $block):void{
        if(!isset(self::$httpEvents[$path]))
            self::$httpEvents[$path] = array();

        self::$httpEvents[$path]["UNLOCK"] = static::resolveTarget($block);
    }
    
    public static function view(string $path, $block):void{
        if(!isset(self::$httpEvents[$path]))
            self::$httpEvents[$path] = array();

        self::$httpEvents[$path]["VIEW"] = static::resolveTarget($block);
    }
    

    public static function &getHttpEvents():array{
        return self::$httpEvents;
    }
}