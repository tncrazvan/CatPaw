<?php
namespace com\github\tncrazvan\catpaw\tools\helpers;

use com\github\tncrazvan\catpaw\tools\Strings;
use ReflectionClass;
use ReflectionMethod;

class Route{
    private static array $httpEvents = [];

    /**
     * Make a new route from a classname.<br />
     * The given classname MUST extend com\github\tncrazvan\catpaw\http\HttpEventHandler.
     * @param string $basePath Base path of your route (must always start with "/")
     * @param string $classname classname name of your class (usually MyClass::class)
     */
    public static function make(string $basePath, string $classname):void{
        static::handler($basePath,$classname,false);
    }

    public static function singleton(string $basePath, string $classname):void{
        static::handler($basePath,$classname,true);
    }

    private static function handler(string &$basePath, string &$classname, bool $singleton = false):void{
        $reflectionClass = new ReflectionClass($classname);
        $instance = new $classname();
        $methods = $reflectionClass->getMethods();
        $eventMethods = new \stdClass();
        foreach($methods as $method){
            $name = $method->getName();
            if($method->isStatic()) 
                continue;
            $eventMethods->$name = $name;
        }
        $map = $instance::map($eventMethods);

        $basePath = \preg_replace('/\/+$/','',$basePath);

        if(!Strings::startsWith($basePath,"/"))
            $basePath = "/$basePath";

        if($singleton)
            static::map($map,$reflectionClass,$basePath,"$classname::singleton();");
        else
            static::map($map,$reflectionClass,$basePath,"new $classname();");
    }

    private static function map(
        array $map, 
        ReflectionClass $reflectionClass,
        string $basePath,
        string $execute
    ):void{
        foreach($map as &$item){
            $method= $item["method"];
            $fname = $item["fname"];

            if("" === $item["path"])
                $path = $basePath;
            else
                $path= implode("/",[
                    $basePath,
                    $item["path"]
                ]);
            
            $path = \preg_replace('/^\/{2,}/','/',$path);

            $reflectionMethod = $reflectionClass->getMethod($fname);
            
            [
                $namedAndTypedParamsString,
                $namedParamsString
            ] = static::getMappedParameters($reflectionMethod);
            
            $script =<<<EOF
            return function($namedAndTypedParamsString){
                \$instance = $execute;
                return \$instance->$fname($namedParamsString);
            };
            EOF;
            $callback = eval($script);

            static::target($method,$path,$callback);
        }
    }

    private static function getMappedParameters(ReflectionMethod $reflectionMethod):array{
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
        if(!isset(self::$httpEvents["@forward"]))
            self::$httpEvents["@forward"][$from] = $to;
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
    public static function notFound($block):void{
        if(!isset(self::$httpEvents["@404"]))
            self::$httpEvents["@404"] = array();

        if(is_array($block)){
            self::$httpEvents["@404"] = $block;
        }else{
            self::$httpEvents["@404"]["GET"] = $block;
        }
    }

    /**
     * Define an event callback for the "COPY" http method.
     * @param string $path the path the event should listen to.
     * @param \Closure $callback the callback to execute.
     */
    public static function copy(string $path, \Closure $callback):void{
        if(!isset(self::$httpEvents[$path]))
            self::$httpEvents[$path] = array();

        self::$httpEvents[$path]["COPY"] = $callback;
    }

    /**
     * Define an event callback for the "COPY" http method.
     * @param string $path the path the event should listen to.
     * @param \Closure $callback the callback to execute.
     */
    public static function delete(string $path, \Closure $callback):void{
        if(!isset(self::$httpEvents[$path]))
            self::$httpEvents[$path] = array();

        self::$httpEvents[$path]["DELETE"] = $callback;
    }

    /**
     * Define an event callback for the "COPY" http method.
     * @param string $path the path the event should listen to.
     * @param \Closure $callback the callback to execute.
     */
    public static function get(string $path, \Closure $callback):void{
        if(!isset(self::$httpEvents[$path]))
            self::$httpEvents[$path] = array();

        self::$httpEvents[$path]["GET"] = $callback;
    }

    /**
     * Define an event callback for the "HEAD" http method.
     * @param string $path the path the event should listen to.
     * @param \Closure $callback the callback to execute.
     */
    public static function head(string $path, \Closure $callback):void{
        if(!isset(self::$httpEvents[$path]))
            self::$httpEvents[$path] = array();

        self::$httpEvents[$path]["HEAD"] = $callback;
    }
    
    /**
     * Define an event callback for the "LINK" http method.
     * @param string $path the path the event should listen to.
     * @param \Closure $callback the callback to execute.
     */
    public static function link(string $path, \Closure $callback):void{
        if(!isset(self::$httpEvents[$path]))
            self::$httpEvents[$path] = array();

        self::$httpEvents[$path]["LINK"] = $callback;
    }
    
    /**
     * Define an event callback for the "LOCK" http method.
     * @param string $path the path the event should listen to.
     * @param \Closure $callback the callback to execute.
     */
    public static function lock(string $path, \Closure $callback):void{
        if(!isset(self::$httpEvents[$path]))
            self::$httpEvents[$path] = array();

        self::$httpEvents[$path]["LOCK"] = $callback;
    }
    
    /**
     * Define an event callback for the "OPTIONS" http method.
     * @param string $path the path the event should listen to.
     * @param \Closure $callback the callback to execute.
     */
    public static function options(string $path, \Closure $callback):void{
        if(!isset(self::$httpEvents[$path]))
            self::$httpEvents[$path] = array();

        self::$httpEvents[$path]["OPTIONS"] = $callback;
    }
    
    /**
     * Define an event callback for the "PATCH" http method.
     * @param string $path the path the event should listen to.
     * @param \Closure $callback the callback to execute.
     */
    public static function patch(string $path, \Closure $callback):void{
        if(!isset(self::$httpEvents[$path]))
            self::$httpEvents[$path] = array();

        self::$httpEvents[$path]["PATCH"] = $callback;
    }
    
    /**
     * Define an event callback for the "POST" http method.
     * @param string $path the path the event should listen to.
     * @param \Closure $callback the callback to execute.
     */
    public static function post(string $path, \Closure $callback):void{
        if(!isset(self::$httpEvents[$path]))
            self::$httpEvents[$path] = array();

        self::$httpEvents[$path]["POST"] = $callback;
    }
    
    /**
     * Define an event callback for the "PROPFIND" http method.
     * @param string $path the path the event should listen to.
     * @param \Closure $callback the callback to execute.
     */
    public static function propfind(string $path, \Closure $callback):void{
        if(!isset(self::$httpEvents[$path]))
            self::$httpEvents[$path] = array();

        self::$httpEvents[$path]["PROPFIND"] = $callback;
    }
    
    /**
     * Define an event callback for the "PURGE" http method.
     * @param string $path the path the event should listen to.
     * @param \Closure $callback the callback to execute.
     */
    public static function purge(string $path, \Closure $callback):void{
        if(!isset(self::$httpEvents[$path]))
            self::$httpEvents[$path] = array();

        self::$httpEvents[$path]["PURGE"] = $callback;
    }
    
    /**
     * Define an event callback for the "PUT" http method.
     * @param string $path the path the event should listen to.
     * @param \Closure $callback the callback to execute.
     */
    public static function put(string $path, \Closure $callback):void{
        if(!isset(self::$httpEvents[$path]))
            self::$httpEvents[$path] = array();

        self::$httpEvents[$path]["PUT"] = $callback;
    }
    
    /**
     * Define an event callback for the "UNKNOWN" http method.
     * @param string $path the path the event should listen to.
     * @param \Closure $callback the callback to execute.
     */
    public static function unknown(string $path, \Closure $callback):void{
        if(!isset(self::$httpEvents[$path]))
            self::$httpEvents[$path] = array();

        self::$httpEvents[$path]["UNKNOWN"] = $callback;
    }
    
    /**
     * Define an event callback for the "UNLINK" http method.
     * @param string $path the path the event should listen to.
     * @param \Closure $callback the callback to execute.
     */
    public static function unlink(string $path, \Closure $callback):void{
        if(!isset(self::$httpEvents[$path]))
            self::$httpEvents[$path] = array();

        self::$httpEvents[$path]["UNLINK"] = $callback;
    }
    
    /**
     * Define an event callback for the "UNLOCK" http method.
     * @param string $path the path the event should listen to.
     * @param \Closure $callback the callback to execute.
     */
    public static function unlock(string $path, \Closure $callback):void{
        if(!isset(self::$httpEvents[$path]))
            self::$httpEvents[$path] = array();

        self::$httpEvents[$path]["UNLOCK"] = $callback;
    }
    
    /**
     * Define an event callback for the "VIEW" http method.
     * @param string $path the path the event should listen to.
     * @param \Closure $callback the callback to execute.
     */
    public static function view(string $path, \Closure $callback):void{
        if(!isset(self::$httpEvents[$path]))
            self::$httpEvents[$path] = array();

        self::$httpEvents[$path]["VIEW"] = $callback;
    }
    

    public static function &getHttpEvents():array{
        return self::$httpEvents;
    }
}