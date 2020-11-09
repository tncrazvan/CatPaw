<?php
namespace com\github\tncrazvan\catpaw;

class Event{
    private static array $httpEvents = [];
    private static array $websocketEvents = [];
    
    public static function http(string $path,$block):void{
        self::$httpEvents[$path] = $block;
    }
    
    public static function websocket(string $path,$block):void{
        self::$websocketEvents[$path]= $block;
    }

    public static function &getHttpEvents():array{
        return self::$httpEvents;
    }

    public static function &getWebsocketEvents():array{
        return self::$websocketEvents;
    }
}