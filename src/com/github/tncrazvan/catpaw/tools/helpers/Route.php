<?php
namespace com\github\tncrazvan\catpaw\tools\helpers;

use com\github\tncrazvan\catpaw\attributes\Filter;
use com\github\tncrazvan\catpaw\attributes\http\PathParam;
use com\github\tncrazvan\catpaw\attributes\Produces;
use com\github\tncrazvan\catpaw\attributes\metadata\Meta;
use com\github\tncrazvan\catpaw\attributes\templates\Twig;
use com\github\tncrazvan\catpaw\tools\process\Process;

class Route{
    
    private static array $initialized_attributes = [];

    public static function map(
        \ReflectionClass $reflection_class,
        string $basePath,
        array $map, 
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

            $reflection_method = $reflection_class->getMethod($fname);
            
            static::initialize($method,$path,$reflection_class,$reflection_method,null);
        }
    }
    private static function initialize_class(
        string &$method, 
        string &$path, 
        ?\ReflectionClass $reflection_class, 
        ?Filter &$filter = null
    ):void{
        Meta::$KLASS[$method][$path] = $reflection_class;

        $attributes = $reflection_class->getAttributes();
        foreach($attributes as &$attribute){
            $classname = $attribute->getName();
            if(Filter::class === $classname && $filter) continue;
            $instance = new $classname(...$attribute->getArguments());
            Meta::$CLASS_ATTRIBUTES[$method][$path][$classname] = $instance;
        }
    }
    private static function initialize_method(
        string &$method, 
        string &$path, 
        ?\ReflectionMethod $reflection_method, 
        ?Filter &$filter = null
    ):void{
        Meta::$METHODS[$method][$path] = $reflection_method;
        $params = $reflection_method->getParameters();
        Meta::$METHODS_ARGS[$method][$path] = $params;

        foreach(Meta::$METHODS_ARGS[$method][$path] as &$param){
            Meta::$METHODS_ARGS_NAMES[$method][$path][] = $param->getName();
        }
        $produces = null;
        $attributes = $reflection_method->getAttributes();
        foreach($attributes as &$attribute){
            $classname = $attribute->getName();
            $instance = new $classname(...$attribute->getArguments());
            if(Filter::class === $classname)
                $filter = $instance;
            else if (Process::class === $classname)
                $produces = $instance;
            Meta::$METHODS_ATTRIBUTES[$method][$path][$classname] = $instance;
        }

        if(!$produces)
            Meta::$METHODS_ATTRIBUTES[$method][$path][Produces::class] = new Produces("text/plain");
        
        foreach($params as $reflection_param){
            $classname = $reflection_param->getType()->getName();
            $attributes = $reflection_param->getAttributes();
            foreach($attributes as &$attribute){
                $paramName = $attribute->getName();
                $instance = new $paramName(...$attribute->getArguments());
                if(PathParam::class === $paramName){
                    Meta::$PATH_PARAMS[$method][$path][$paramName] = $instance;
                }else{
                    Meta::$METHODS_ARGS_ATTRIBUTES[$method][$path][$paramName] = $instance;
                }
            }
        }
    }
    private static function initialize_function(
        string &$method, 
        string &$path, 
        ?\ReflectionFunction $reflection_function,
        ?Filter &$filter = null
    ):void{
        Meta::$FUNCTIONS[$method][$path] = $reflection_function;
        
    
        $params = $reflection_function->getParameters();
        Meta::$FUNCTIONS_ARGS[$method][$path] = $params;

        foreach(Meta::$FUNCTIONS_ARGS[$method][$path] as &$param){
            Meta::$FUNCTIONS_ARGS_NAMES[$method][$path][] = $param->getName();
        }

        $produces = null;
        $attributes = $reflection_function->getAttributes();
        foreach($attributes as &$attribute){
            $classname = $attribute->getName();
            if(!class_exists($classname)) continue;
            $instance = new $classname(...$attribute->getArguments());
            if(Filter::class === $classname)
                $filter = $instance;
            else if (Process::class === $classname)
                $produces = $instance;

            Meta::$FUNCTIONS_ATTRIBUTES[$method][$path][$classname] = $instance;
        }

        if(!$produces)
            Meta::$FUNCTIONS_ATTRIBUTES[$method][$path][Produces::class] = new Produces("text/plain");
        
        foreach($params as $reflection_param){
            $classname = $reflection_param->getType()->getName();
            $attributes = $reflection_param->getAttributes();
            foreach($attributes as &$attribute){
                $paramName = $attribute->getName();
                $instance = new $paramName(...$attribute->getArguments());
                if(PathParam::class === $classname){
                    Meta::$PATH_PARAMS[$method][$path][$paramName] = $instance;
                }else{
                    Meta::$FUNCTIONS_ARGS_ATTRIBUTES[$method][$path][$paramName] = $instance;
                }
            }
        }
    }
    private static function initialize_filter(
        string $method,
        string &$path,
        string &$klassname,
        ?\ReflectionFunction $reflection_function
    ):void{
        Meta::$FILTERS[$method][$path][$klassname] = $reflection_function;  //normaly this is a method, but this time it's a function
        $params = $reflection_function->getParameters();
        Meta::$FILTERS_ARGS[$method][$path][$klassname] = $params;

        foreach(Meta::$FILTERS_ARGS[$method][$path][$klassname] as &$param){
            Meta::$FILTERS_ARGS_NAMES[$method][$path][$klassname][] = $param->getName();
        }

        $produces = null;
        $attributes = $reflection_function->getAttributes();
        foreach($attributes as &$attribute){
            $classname = $attribute->getName();
            $instance = new $classname(...$attribute->getArguments());
            if (Process::class === $classname)
                $produces = $instance;
            Meta::$FILTERS_ATTRIBUTES[$method][$path][$classname] = $instance;
        }

        if(!$produces)
            Meta::$FILTERS_ATTRIBUTES[$method][$path][$klassname][Produces::class] = new Produces("text/plain");

        foreach($params as $reflection_param){
            $classname = $reflection_param->getType()->getName();
            $attributes = $reflection_param->getAttributes();
            foreach($attributes as &$attribute){
                $paramName = $attribute->getName();
                $instance = new $paramName(...$attribute->getArguments());
                Meta::$FILTERS_ARGS_ATTRIBUTES[$method][$path][$paramName] = $instance;
                
            }
        }
    }
    private static function initialize(
        string $method,
        string &$path,
        ?\ReflectionClass $reflection_class,
        ?\ReflectionMethod $reflection_method,
        ?\ReflectionFunction $reflection_function,
    ):void{
        $filter = null;
        if(!isset(static::$initialized_attributes[$method][$path]) || !static::$initialized_attributes[$method][$path]){
            if($reflection_method){
                static::initialize_method($method,$path,$reflection_method,$filter);
            }else if($reflection_function){
                static::initialize_function(
                    $method,
                    $path,
                    $reflection_function,
                    $filter
                );
            }
            
            if($reflection_class){
                static::initialize_class($method,$path,$reflection_class,$filter);
            }

            if($filter){
                foreach($filter->getCallbacks() as $classname => $callback){
                    static::initialize_filter($method,$path,$classname,new \ReflectionFunction($callback));
                }
            }

            static::$initialized_attributes[$method][$path] = true;
        }
    }

    public static function getMappedParameters(\ReflectionMethod $reflection_method):array{
        $reflectionParameters = $reflection_method->getParameters();
        $namedAndTypedParams = array();
        $namedParams = array();
        foreach($reflectionParameters as $reflectionParameter){
            $name = $reflectionParameter->getName();
            $type = $reflectionParameter->getType()->getName();
            $namedAndTypedParams[] = "$type &\$$name";
            $namedParams[] = "\$$name";
        }
        $namedAndTypedParamsString = \implode(',',$namedAndTypedParams);
        $namedParamsString = \implode(',',$namedParams);
        return [$namedAndTypedParamsString,$namedParamsString];
    }

    /**
     * Capture requests $from a certain path and forward them $to a different path.
     * @param string $method http method of the 2 paths
     * @param string $from path to capture
     * @param string $to path to forward to
     */
    public static function forward(string $method, string $from, string $to):void{
        if(!isset(Meta::$FUNCTIONS[$from][$method])){
            Meta::$FUNCTIONS[$from][$method] = Meta::$FUNCTIONS[$to][$method];
        }
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
     * Define an event callback for a custom http method.
     * @param string $method the name of the http method.
     * @param string $path the path the event should listen to.
     * @param \Closure $callback the callback to execute.
     */
    public static function custom(string $method, string $path, \Closure $callback):void{
        static::initialize($method,$path,null,null,new \ReflectionFunction($callback));
    }

    /**
     * Define an event callback for the "COPY" http method.
     * @param string $path the path the event should listen to.
     * @param \Closure $callback the callback to execute.
     */
    public static function copy(string $path, \Closure $callback):void{
        static::initialize('COPY',$path,null,null,new \ReflectionFunction($callback));
    }

    /**
     * Define an event callback for the "COPY" http method.
     * @param string $path the path the event should listen to.
     * @param \Closure $callback the callback to execute.
     */
    public static function delete(string $path, \Closure $callback):void{
        static::initialize('DELETE',$path,null,null,new \ReflectionFunction($callback));
    }

    /**
     * Define an event callback for the "COPY" http method.
     * @param string $path the path the event should listen to.
     * @param \Closure $callback the callback to execute.
     */
    public static function get(string $path, \Closure $callback):void{
        static::initialize('GET',$path,null,null,new \ReflectionFunction($callback));
    }

    /**
     * Define an event callback for the "HEAD" http method.
     * @param string $path the path the event should listen to.
     * @param \Closure $callback the callback to execute.
     */
    public static function head(string $path, \Closure $callback):void{
        static::initialize('HEAD',$path,null,null,new \ReflectionFunction($callback));
    }
    
    /**
     * Define an event callback for the "LINK" http method.
     * @param string $path the path the event should listen to.
     * @param \Closure $callback the callback to execute.
     */
    public static function link(string $path, \Closure $callback):void{
        static::initialize('LINK',$path,null,null,new \ReflectionFunction($callback));
    }
    
    /**
     * Define an event callback for the "LOCK" http method.
     * @param string $path the path the event should listen to.
     * @param \Closure $callback the callback to execute.
     */
    public static function lock(string $path, \Closure $callback):void{
        static::initialize('LOCK',$path,null,null,new \ReflectionFunction($callback));
    }
    
    /**
     * Define an event callback for the "OPTIONS" http method.
     * @param string $path the path the event should listen to.
     * @param \Closure $callback the callback to execute.
     */
    public static function options(string $path, \Closure $callback):void{
        static::initialize('OPTIONS',$path,null,null,new \ReflectionFunction($callback));
    }
    
    /**
     * Define an event callback for the "PATCH" http method.
     * @param string $path the path the event should listen to.
     * @param \Closure $callback the callback to execute.
     */
    public static function patch(string $path, \Closure $callback):void{
        static::initialize('PATCH',$path,null,null,new \ReflectionFunction($callback));
    }
    
    /**
     * Define an event callback for the "POST" http method.
     * @param string $path the path the event should listen to.
     * @param \Closure $callback the callback to execute.
     */
    public static function post(string $path, \Closure $callback):void{
        static::initialize('POST',$path,null,null,new \ReflectionFunction($callback));
    }
    
    /**
     * Define an event callback for the "PROPFIND" http method.
     * @param string $path the path the event should listen to.
     * @param \Closure $callback the callback to execute.
     */
    public static function propfind(string $path, \Closure $callback):void{
        static::initialize('PROPFIND',$path,null,null,new \ReflectionFunction($callback));
    }
    
    /**
     * Define an event callback for the "PURGE" http method.
     * @param string $path the path the event should listen to.
     * @param \Closure $callback the callback to execute.
     */
    public static function purge(string $path, \Closure $callback):void{
        static::initialize('PURGA',$path,null,null,new \ReflectionFunction($callback));
    }
    
    /**
     * Define an event callback for the "PUT" http method.
     * @param string $path the path the event should listen to.
     * @param \Closure $callback the callback to execute.
     */
    public static function put(string $path, \Closure $callback):void{
        static::initialize('PUT',$path,null,null,new \ReflectionFunction($callback));
    }
    
    /**
     * Define an event callback for the "UNKNOWN" http method.
     * @param string $path the path the event should listen to.
     * @param \Closure $callback the callback to execute.
     */
    public static function unknown(string $path, \Closure $callback):void{
        static::initialize('UNKNOWN',$path,null,null,new \ReflectionFunction($callback));
    }
    
    /**
     * Define an event callback for the "UNLINK" http method.
     * @param string $path the path the event should listen to.
     * @param \Closure $callback the callback to execute.
     */
    public static function unlink(string $path, \Closure $callback):void{
        static::initialize('UNLINK',$path,null,null,new \ReflectionFunction($callback));
    }
    
    /**
     * Define an event callback for the "UNLOCK" http method.
     * @param string $path the path the event should listen to.
     * @param \Closure $callback the callback to execute.
     */
    public static function unlock(string $path, \Closure $callback):void{
        static::initialize('UNLOCK',$path,null,null,new \ReflectionFunction($callback));
    }
    
    /**
     * Define an event callback for the "VIEW" http method.
     * @param string $path the path the event should listen to.
     * @param \Closure $callback the callback to execute.
     */
    public static function view(string $path, \Closure $callback):void{
        static::initialize('VIEW',$path,null,null,new \ReflectionFunction($callback));
    }
}