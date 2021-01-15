<?php
namespace com\github\tncrazvan\catpaw\tools\helpers;

use com\github\tncrazvan\catpaw\tools\AttributeResolver;

class Route{
    protected static array $httpEvents = [];


    public static function map(
        array $map, 
        \ReflectionClass $reflectionClass,
        string &$classname,
        string $basePath,
        string $execute
    ):void{
        foreach($map as &$item){
            $method= $item['method'];
            $fname = $item['fname'];

            if('' === $item['path'])
                $path = $basePath;
            else
                $path= implode('/',[
                    $basePath,
                    $item['path']
                ]);
            
            $path = \preg_replace('/^\/{2,}/','/',$path);

            $reflectionMethod = $reflectionClass->getMethod($fname);
            
            [
                $namedAndTypedParamsString,
                $namedParamsString
            ] = static::getMappedParameters($reflectionMethod);
            $resolver = AttributeResolver::class;
            $script =<<<EOF
            return function($namedAndTypedParamsString){
                \$instance = $execute;
                \$classname = '$classname';
                $resolver::injectProperties(\$classname,\$instance);
                return \$instance->$fname($namedParamsString);
            };
            EOF;
            $callback = eval($script);

            static::target($method,$path,$callback);
        }
    }

    private static function getMappedParameters(\ReflectionMethod $reflectionMethod):array{
        $reflectionParameters = $reflectionMethod->getParameters();
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
        return [$namedAndTypedParamsString,$namedParamsString];
    }

    private static function target(string $method, string $path, \Closure $callback):void{
        switch($method){
            case "COPY":
                static::copy($path,$callback);
            break;
            case "DELETE":
                static::delete($path,$callback);
            break;
            case "GET":
                static::get($path,$callback);
            break;
            case "HEAD":
                static::head($path,$callback);
            break;
            case "LINK":
                static::link($path,$callback);
            break;
            case "LOCK":
                static::lock($path,$callback);
            break;
            case "OPTIONS":
                static::options($path,$callback);
            break;
            case "PATCH":
                static::patch($path,$callback);
            break;
            case "POST":
                static::post($path,$callback);
            break;
            case "PROPFIND":
                static::propfind($path,$callback);
            break;
            case "PURGE":
                static::purge($path,$callback);
            break;
            case "PUT":
                static::put($path,$callback);
            break;
            case "UNKNOWN":
                static::unknown($path,$callback);
            break;
            case "UNLINK":
                static::unlink($path,$callback);
            break;
            case "UNLOCK":
                static::unlock($path,$callback);
            break;
            case "VIEW":
                static::view($path,$callback);
            break;
        }
    }

    /**
     * Capture requests $from a certain path and forward them $to a different path.
     * @param string $from path to capture
     * @param string $to path to forward to
     */
    public static function forward(string $from, string $to):void{
        if(!isset(static::$httpEvents["@forward"]))
            static::$httpEvents["@forward"][$from] = $to;
    }

    /**
     * Define a callback to run when a resource is not found.<br />
     * The default callback will look for "index.html" or "index.php" in the closes web directory  to the requested path and serve  the file.
     * @param mixed $block an object that can be either a \Closure or an associative array of \Closures.
     * Each key of the associative array must define the http method name (in ALL CAPS).<br />
     * Example:<br/>
     * [
     *      "GET" => function(){...},
     *      "POST" => function(){...},
     *      "COPY" => function(){...},
     * ]<br />
     * If this parameter is instead passed as a plain \Closure, the callback will be assigned to the "GET" http method.
     */
    public static function notFound(\Closure $callback):void{
        static::copy("@404",$callback);
        static::delete("@404",$callback);
        static::get("@404",$callback);
        static::head("@404",$callback);
        static::link("@404",$callback);
        static::lock("@404",$callback);
        static::options("@404",$callback);
        static::patch("@404",$callback);
        static::post("@404",$callback);
        static::propfind("@404",$callback);
        static::purge("@404",$callback);
        static::put("@404",$callback);
        static::unknown("@404",$callback);
        static::unlink("@404",$callback);
        static::unlock("@404",$callback);
        static::view("@404",$callback);
    }

    /**
     * Define an event callback for the "COPY" http method.
     * @param string $path the path the event should listen to.
     * @param \Closure $callback the callback to execute.
     */
    public static function copy(string $path, \Closure $callback):void{
        if(!isset(static::$httpEvents[$path]))
            static::$httpEvents[$path] = array();

        static::$httpEvents[$path]["COPY"] = $callback;
    }

    /**
     * Define an event callback for the "COPY" http method.
     * @param string $path the path the event should listen to.
     * @param \Closure $callback the callback to execute.
     */
    public static function delete(string $path, \Closure $callback):void{
        if(!isset(static::$httpEvents[$path]))
            static::$httpEvents[$path] = array();

        static::$httpEvents[$path]["DELETE"] = $callback;
    }

    /**
     * Define an event callback for the "COPY" http method.
     * @param string $path the path the event should listen to.
     * @param \Closure $callback the callback to execute.
     */
    public static function get(string $path, \Closure $callback):void{
        if(!isset(static::$httpEvents[$path]))
            static::$httpEvents[$path] = array();

        static::$httpEvents[$path]["GET"] = $callback;
    }

    /**
     * Define an event callback for the "HEAD" http method.
     * @param string $path the path the event should listen to.
     * @param \Closure $callback the callback to execute.
     */
    public static function head(string $path, \Closure $callback):void{
        if(!isset(static::$httpEvents[$path]))
            static::$httpEvents[$path] = array();

        static::$httpEvents[$path]["HEAD"] = $callback;
    }
    
    /**
     * Define an event callback for the "LINK" http method.
     * @param string $path the path the event should listen to.
     * @param \Closure $callback the callback to execute.
     */
    public static function link(string $path, \Closure $callback):void{
        if(!isset(static::$httpEvents[$path]))
            static::$httpEvents[$path] = array();

        static::$httpEvents[$path]["LINK"] = $callback;
    }
    
    /**
     * Define an event callback for the "LOCK" http method.
     * @param string $path the path the event should listen to.
     * @param \Closure $callback the callback to execute.
     */
    public static function lock(string $path, \Closure $callback):void{
        if(!isset(static::$httpEvents[$path]))
            static::$httpEvents[$path] = array();

        static::$httpEvents[$path]["LOCK"] = $callback;
    }
    
    /**
     * Define an event callback for the "OPTIONS" http method.
     * @param string $path the path the event should listen to.
     * @param \Closure $callback the callback to execute.
     */
    public static function options(string $path, \Closure $callback):void{
        if(!isset(static::$httpEvents[$path]))
            static::$httpEvents[$path] = array();

        static::$httpEvents[$path]["OPTIONS"] = $callback;
    }
    
    /**
     * Define an event callback for the "PATCH" http method.
     * @param string $path the path the event should listen to.
     * @param \Closure $callback the callback to execute.
     */
    public static function patch(string $path, \Closure $callback):void{
        if(!isset(static::$httpEvents[$path]))
            static::$httpEvents[$path] = array();

        static::$httpEvents[$path]["PATCH"] = $callback;
    }
    
    /**
     * Define an event callback for the "POST" http method.
     * @param string $path the path the event should listen to.
     * @param \Closure $callback the callback to execute.
     */
    public static function post(string $path, \Closure $callback):void{
        if(!isset(static::$httpEvents[$path]))
            static::$httpEvents[$path] = array();

        static::$httpEvents[$path]["POST"] = $callback;
    }
    
    /**
     * Define an event callback for the "PROPFIND" http method.
     * @param string $path the path the event should listen to.
     * @param \Closure $callback the callback to execute.
     */
    public static function propfind(string $path, \Closure $callback):void{
        if(!isset(static::$httpEvents[$path]))
            static::$httpEvents[$path] = array();

        static::$httpEvents[$path]["PROPFIND"] = $callback;
    }
    
    /**
     * Define an event callback for the "PURGE" http method.
     * @param string $path the path the event should listen to.
     * @param \Closure $callback the callback to execute.
     */
    public static function purge(string $path, \Closure $callback):void{
        if(!isset(static::$httpEvents[$path]))
            static::$httpEvents[$path] = array();

        static::$httpEvents[$path]["PURGE"] = $callback;
    }
    
    /**
     * Define an event callback for the "PUT" http method.
     * @param string $path the path the event should listen to.
     * @param \Closure $callback the callback to execute.
     */
    public static function put(string $path, \Closure $callback):void{
        if(!isset(static::$httpEvents[$path]))
            static::$httpEvents[$path] = array();

        static::$httpEvents[$path]["PUT"] = $callback;
    }
    
    /**
     * Define an event callback for the "UNKNOWN" http method.
     * @param string $path the path the event should listen to.
     * @param \Closure $callback the callback to execute.
     */
    public static function unknown(string $path, \Closure $callback):void{
        if(!isset(static::$httpEvents[$path]))
            static::$httpEvents[$path] = array();

        static::$httpEvents[$path]["UNKNOWN"] = $callback;
    }
    
    /**
     * Define an event callback for the "UNLINK" http method.
     * @param string $path the path the event should listen to.
     * @param \Closure $callback the callback to execute.
     */
    public static function unlink(string $path, \Closure $callback):void{
        if(!isset(static::$httpEvents[$path]))
            static::$httpEvents[$path] = array();

        static::$httpEvents[$path]["UNLINK"] = $callback;
    }
    
    /**
     * Define an event callback for the "UNLOCK" http method.
     * @param string $path the path the event should listen to.
     * @param \Closure $callback the callback to execute.
     */
    public static function unlock(string $path, \Closure $callback):void{
        if(!isset(static::$httpEvents[$path]))
            static::$httpEvents[$path] = array();

        static::$httpEvents[$path]["UNLOCK"] = $callback;
    }
    
    /**
     * Define an event callback for the "VIEW" http method.
     * @param string $path the path the event should listen to.
     * @param \Closure $callback the callback to execute.
     */
    public static function view(string $path, \Closure $callback):void{
        if(!isset(static::$httpEvents[$path]))
            static::$httpEvents[$path] = array();

        static::$httpEvents[$path]["VIEW"] = $callback;
    }
    

    public static function &getHttpEvents():array{
        return static::$httpEvents;
    }
}