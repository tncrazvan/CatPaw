<?php
namespace com\github\tncrazvan\catpaw\http;
abstract class HttpEventHandler{

    /**
     * Register a local method as an http event.
     * @param string $method the http method (in ALL CAPS).
     * @param string $path the http path (MUST always start with "/").<br />
     * This path is relative to the path the class itself has been registered to.<br />
     * @param string $functionName name of the local function.
     * @return array an array that contains metadata for this registration.
     */
    protected static function target(string $method, string $path, string $functionName):array{
        
        $path = \preg_replace('/^\/+/','',$path);
        
        return [
            "method" => $method,
            "path" => $path,
            "fname" => $functionName,
        ];
    }

    /**
     * Get all registered local methods of this HttpEventHandler.
     * @return array an array containing all registrations.
     */
    public abstract static function map(\stdClass $e):array;
}