<?php
namespace com\github\tncrazvan\catpaw\tools\helpers;

class Route{
    private static array $httpEvents = [];
    
    public static function copy(string $path,\Closure $block):void{
        if(!isset(self::$httpEvents[$path]))
            self::$httpEvents[$path] = array();

        self::$httpEvents[$path]["COPY"] = $block;
    }

    public static function delete(string $path,\Closure $block):void{
        if(!isset(self::$httpEvents[$path]))
            self::$httpEvents[$path] = array();

        self::$httpEvents[$path]["DELETE"] = $block;
    }

    public static function get(string $path,\Closure $block):void{
        if(!isset(self::$httpEvents[$path]))
            self::$httpEvents[$path] = array();

        self::$httpEvents[$path]["GET"] = $block;
    }

    public static function head(string $path,\Closure $block):void{
        if(!isset(self::$httpEvents[$path]))
            self::$httpEvents[$path] = array();

        self::$httpEvents[$path]["HEAD"] = $block;
    }
    
    public static function link(string $path,\Closure $block):void{
        if(!isset(self::$httpEvents[$path]))
            self::$httpEvents[$path] = array();

        self::$httpEvents[$path]["LINK"] = $block;
    }
    
    public static function lock(string $path,\Closure $block):void{
        if(!isset(self::$httpEvents[$path]))
            self::$httpEvents[$path] = array();

        self::$httpEvents[$path]["LOCK"] = $block;
    }
    
    public static function options(string $path,\Closure $block):void{
        if(!isset(self::$httpEvents[$path]))
            self::$httpEvents[$path] = array();

        self::$httpEvents[$path]["OPTIONS"] = $block;
    }
    
    public static function patch(string $path,\Closure $block):void{
        if(!isset(self::$httpEvents[$path]))
            self::$httpEvents[$path] = array();

        self::$httpEvents[$path]["PATCH"] = $block;
    }
    
    public static function post(string $path,\Closure $block):void{
        if(!isset(self::$httpEvents[$path]))
            self::$httpEvents[$path] = array();

        self::$httpEvents[$path]["POST"] = $block;
    }
    
    public static function propfind(string $path,\Closure $block):void{
        if(!isset(self::$httpEvents[$path]))
            self::$httpEvents[$path] = array();

        self::$httpEvents[$path]["PROPFIND"] = $block;
    }
    
    public static function purge(string $path,\Closure $block):void{
        if(!isset(self::$httpEvents[$path]))
            self::$httpEvents[$path] = array();

        self::$httpEvents[$path]["PURGE"] = $block;
    }
    
    public static function put(string $path,\Closure $block):void{
        if(!isset(self::$httpEvents[$path]))
            self::$httpEvents[$path] = array();

        self::$httpEvents[$path]["PUT"] = $block;
    }
    
    public static function unknown(string $path,\Closure $block):void{
        if(!isset(self::$httpEvents[$path]))
            self::$httpEvents[$path] = array();

        self::$httpEvents[$path]["UNKNOWN"] = $block;
    }
    
    public static function unlink(string $path,\Closure $block):void{
        if(!isset(self::$httpEvents[$path]))
            self::$httpEvents[$path] = array();

        self::$httpEvents[$path]["UNLINK"] = $block;
    }
    
    public static function unlock(string $path,\Closure $block):void{
        if(!isset(self::$httpEvents[$path]))
            self::$httpEvents[$path] = array();

        self::$httpEvents[$path]["UNLOCK"] = $block;
    }
    
    public static function view(string $path,\Closure $block):void{
        if(!isset(self::$httpEvents[$path]))
            self::$httpEvents[$path] = array();

        self::$httpEvents[$path]["VIEW"] = $block;
    }
    

    public static function &getHttpEvents():array{
        return self::$httpEvents;
    }
}