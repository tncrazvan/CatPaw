<?php
namespace com\github\tncrazvan\catpaw\tools\helpers;

class WebsocketRoute{
    private static array $websocketEvents = [];
    
    public static function forward(string $from, string $to):void{
        if(!isset(self::$websocketEvents["@forward"]))
            self::$websocketEvents["@forward"][$from] = $to;
    }

    public static function notFound(\Closure $block):void{
        if(!isset(self::$websocketEvents["@404"]))
            self::$websocketEvents["@404"] = array();

        self::$websocketEvents["@404"]["GET"] = $block;
    }

    public static function set(string $path,\Closure $block):void{
        if(!isset(self::$websocketEvents[$path]))
            self::$websocketEvents[$path] = array();

        self::$websocketEvents[$path] = $block;
    }
    

    public static function &getWebsocketEvents():array{
        return self::$websocketEvents;
    }
}