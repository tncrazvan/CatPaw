<?php
namespace com\github\tncrazvan\catpaw\tools\helpers;

use com\github\tncrazvan\catpaw\tools\Strings;
use com\github\tncrazvan\catpaw\attributes\Singleton;
use com\github\tncrazvan\catpaw\attributes\http\Path;
use com\github\tncrazvan\catpaw\attributes\http\methods\COPY;
use com\github\tncrazvan\catpaw\attributes\http\methods\DELETE;
use com\github\tncrazvan\catpaw\attributes\http\methods\GET;
use com\github\tncrazvan\catpaw\attributes\http\methods\HEAD;
use com\github\tncrazvan\catpaw\attributes\http\methods\LINK;
use com\github\tncrazvan\catpaw\attributes\http\methods\LOCK;
use com\github\tncrazvan\catpaw\attributes\http\methods\OPTIONS;
use com\github\tncrazvan\catpaw\attributes\http\methods\PATCH;
use com\github\tncrazvan\catpaw\attributes\http\methods\POST;
use com\github\tncrazvan\catpaw\attributes\http\methods\PROPFIND;
use com\github\tncrazvan\catpaw\attributes\http\methods\PURGE;
use com\github\tncrazvan\catpaw\attributes\http\methods\PUT;
use com\github\tncrazvan\catpaw\attributes\http\methods\UNKNOWN;
use com\github\tncrazvan\catpaw\attributes\http\methods\UNLINK;
use com\github\tncrazvan\catpaw\attributes\http\methods\UNLOCK;
use com\github\tncrazvan\catpaw\attributes\http\methods\VIEW;
use com\github\tncrazvan\catpaw\attributes\Service;
use ReflectionClass;
use ReflectionMethod;

class Route{
    protected static array $httpEvents = [];

    /**
     * Make a new route from a classname.<br />
     * @param string $basePath Base path of your route (must always start with "/")
     * @param string $classname classname name of your class (usually MyClass::class)
     */
    public static function make(string $classname):void{
        static::handler($classname);
    }

    private static function getClassAttributeArguments(ReflectionClass $reflection_class, string $attribute_name):?array{
        $attributes = $reflection_class->getAttributes();
        foreach($attributes as &$attribute){
            $local_attribute_name = $attribute->getName();
            if($local_attribute_name === $attribute_name)
                return $attribute->getArguments();
        }
        return null;
    }

    private static function issetClassAttribute(ReflectionClass $reflection_class, string ...$attribute_names):bool{
        $attributes = $reflection_class->getAttributes();
        foreach($attributes as &$attribute){
            $classname = $attribute->getName();
            if(in_array($classname,$attribute_names,true))
                return true;
            
        }
        return false;
    }

    private static function getMethodAttributeArguments(ReflectionMethod $reflection_method, string $attribute_name):?array{
        $attributes = $reflection_method->getAttributes();
        foreach($attributes as &$attribute){
            $classname = $attribute->getName();
            if($classname === $attribute_name)
                return $attribute->getArguments();
        }
        return null;
    }

    private static function issetMethodAttribute(ReflectionMethod $reflection_method, string $attribute_name):bool{
        $attributes = $reflection_method->getAttributes();
        foreach($attributes as &$attribute){
            $classname = $attribute->getName();
            if($classname === $attribute_name)
                return true;
        }
        return false;
    }

    private static function getMethodAttribute(\ReflectionMethod $method):array{
        return [
            "COPY" => 
            static::issetMethodAttribute($method,COPY::class),
            "DELETE" =>
            static::issetMethodAttribute($method,DELETE::class),
            "GET" => 
            static::issetMethodAttribute($method,GET::class),
            "HEAD" => 
            static::issetMethodAttribute($method,HEAD::class),
            "LINK" => 
            static::issetMethodAttribute($method,LINK::class),
            "LOCK" => 
            static::issetMethodAttribute($method,LOCK::class),
            "OPTIONS" => 
            static::issetMethodAttribute($method,OPTIONS::class),
            "PATCH" => 
            static::issetMethodAttribute($method,PATCH::class),
            "POST" => 
            static::issetMethodAttribute($method,POST::class),
            "PROPFIND" => 
            static::issetMethodAttribute($method,PROPFIND::class),
            "PURGE" => 
            static::issetMethodAttribute($method,PURGE::class),
            "PUT" => 
            static::issetMethodAttribute($method,PUT::class),
            "UNKNOWN" => 
            static::issetMethodAttribute($method,UNKNOWN::class),
            "UNLINK" => 
            static::issetMethodAttribute($method,UNLINK::class),
            "UNLOCK" => 
            static::issetMethodAttribute($method,UNLOCK::class),
            "VIEW" => 
            static::issetMethodAttribute($method,VIEW::class)
        ];
    }

    private static function resolveMethodAttributes(\ReflectionMethod $method, array &$map, int $i):void{
        $p = static::getMethodAttributeArguments($method,Path::class);
        $path = $p && count($p) > 0?$p[0]:'';

        $http_methods = static::getMethodAttribute($method);

        $http_method = '';
        foreach($http_methods as $key => &$httpm){
            if($httpm){
                $http_method = $key;
                break;
            }
        }

        if('' !== $http_method || '' !== $path){
            $map[$i] = [
                "method" => '' !== $http_method?$http_method:"GET",
                "path" => '' !== $path?\preg_replace('/^\/+/','', $path):'',
                "fname" => $method->getName(),
            ];
        }
    }

    public static function resolveClassPropertiesAttributes(string &$classname,$instance):void{
        $reflectionClass = new \ReflectionClass($classname);
        $props = $reflectionClass->getProperties();
        foreach($props as &$prop){
            $prop->setAccessible(true);
            if(
                $prop->isInitialized($instance) 
                || '' === $classname 
                || 'string' === $classname 
                || 'array' === $classname 
                || 'int' === $classname 
                || 'bool' === $classname
                )
                continue;
            try{
                $proptype = $prop->getType()->getName();
                if(!isset(Singleton::$map[$proptype])){
                    Singleton::$map[$proptype] = new $proptype();
                }
                $prop->setValue($instance,Singleton::$map[$proptype]);
            }catch(\ReflectionException $e){
                //echo "$name is not injectable because it does not specify a static 'inject' method.\n";
            }
            $prop->setAccessible(false);
        }
    }

    private static function handler(string &$classname):void{
        $reflectionClass = new ReflectionClass($classname);
        $methods = $reflectionClass->getMethods();
        $map = [];
        $i = 0;

        //resolve methods attributes
        ############################################################################
        foreach($methods as &$method){
            if($method->isStatic()) 
                continue;
            static::resolveMethodAttributes($method,$map,$i);
            $i++;
        }
        ############################################################################

        //resolve main "Path" attribute
        ##################################################################################################################
        $args = static::getClassAttributeArguments($reflectionClass,Path::class);
        $args_length = $args === null?0:\count($args);
        //if no args are provided for Path on class, or Path is note provided at all, ignore class.
        if($args_length === 0) 
            return;
        
        $basePath = $args[0];
        $basePath = \preg_replace('/\/+$/','',$basePath);
        if(!Strings::startsWith($basePath,'/'))
            $basePath = "/$basePath";
        ##################################################################################################################


        //resolve other class attributes
        ############################################################################
        $singleton = static::issetClassAttribute($reflectionClass,Singleton::class,Service::class);
        ############################################################################

        if($singleton){
            if(!isset(Singleton::$map[$classname])){
                Singleton::$map[$classname] = new $classname();
            }
            
            static::map($map,$reflectionClass,$classname,$basePath,(Singleton::class)."::\$map['$classname']");
        }else
            static::map($map,$reflectionClass,$classname,$basePath,"new $classname()");
    }

    private static function map(
        array $map, 
        ReflectionClass $reflectionClass,
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
            $route = Route::class;
            $script =<<<EOF
            return function($namedAndTypedParamsString){
                \$instance = $execute;
                \$classname = '$classname';
                $route::resolveClassPropertiesAttributes(\$classname,\$instance);
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