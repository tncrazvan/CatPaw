<?php
namespace com\github\tncrazvan\catpaw\http;
abstract class HttpEventHandler{
    protected static function target(string $method, string $path, string $functionName){
        
        $path = \preg_replace('/^\/+/','',$path);
        
        return [
            "method" => $method,
            "path" => $path,
            "fname" => $functionName,
        ];
    }
    public function getHandlerName():string{
        return get_called_class();
    }
    public abstract static function map():array;
}