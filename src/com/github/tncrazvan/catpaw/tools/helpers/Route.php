<?php
namespace com\github\tncrazvan\catpaw\tools\helpers;

use com\github\tncrazvan\catpaw\attributes\Consumes;
use com\github\tncrazvan\catpaw\attributes\Entity;
use com\github\tncrazvan\catpaw\attributes\http\Headers;
use com\github\tncrazvan\catpaw\attributes\http\Path;
use com\github\tncrazvan\catpaw\attributes\http\PathParam;
use com\github\tncrazvan\catpaw\attributes\Produces;
use com\github\tncrazvan\catpaw\attributes\Repository;
use com\github\tncrazvan\catpaw\attributes\Service;
use com\github\tncrazvan\catpaw\attributes\Singleton;
use com\github\tncrazvan\catpaw\tools\helpers\metadata\Meta;
use com\github\tncrazvan\catpaw\tools\Status;

class Route{
    
    private static array $initialized_attributes = [];

    public static function map(
        array $map, 
        mixed &$instance,
        \ReflectionClass $reflection_class,
        string &$classname,
        string $basePath,
        bool $inject,
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

            $reflection_method = $reflection_class->getMethod($fname);
            
            static::initialize($method,$path,$reflection_class,$reflection_method,null);
        }
    }

    private static function initialize(
        string $method, 
        string &$path, 
        ?\ReflectionClass $reflection_class,
        ?\ReflectionMethod $reflection_method,
        ?\ReflectionFunction $reflection_function
    ):void{
        if(!isset(static::$initialized_attributes[$method][$path]) || !static::$initialized_attributes[$method][$path]){
            if($reflection_method){
                Meta::METHODS[$method][$path] = $reflection_method;
                Meta::METHODS_ARGS[$method][$path] = $reflection_method->getParameters();

                Meta::METHODS_ATTRIBUTES[$method][$path][Path::class] = Path::findByMethod($reflection_method);
                Meta::METHODS_ATTRIBUTES[$method][$path][Consumes::class] = Consumes::findByMethod($reflection_method);
                Meta::METHODS_ATTRIBUTES[$method][$path][Produces::class] = Produces::findByMethod($reflection_method);
                
                $params = $reflection_method->getParameters();
                foreach($params as $param){
                    $path_param = PathParam::findByParameter($param);
                    Meta::PATH_PARAMS[$method][$path][$param->getName()] = $path_param;
                    Meta::METHODS_ARGS_ATTRIBUTES[$method][$path][$param->getName()][Headers::class] = Headers::findByParameter($param);
                    Meta::METHODS_ARGS_ATTRIBUTES[$method][$path][$param->getName()][Status::class] = Status::findByParameter($param);
                }
            }else if($reflection_function){
                Meta::FUNCTIONS[$method][$path] = $reflection_method;
                Meta::FUNCTIONS_ARGS[$method][$path] = $reflection_function->getParameters();

                Meta::FUNCTIONS_ATTRIBUTES[$method][$path][Path::class] = Path::findByFunction($reflection_function);
                Meta::FUNCTIONS_ATTRIBUTES[$method][$path][Consumes::class] = Consumes::findByFunction($reflection_function);
                Meta::FUNCTIONS_ATTRIBUTES[$method][$path][Produces::class] = Produces::findByFunction($reflection_function);
                
                $params = $reflection_function->getParameters();
                foreach($params as $param){
                    $path_param = PathParam::findByParameter($param);
                    Meta::PATH_PARAMS[$method][$path][$param->getName()] = $path_param;
                    Meta::METHODS_ARGS_ATTRIBUTES[$method][$path][$param->getName()][Headers::class] = Headers::findByParameter($param);
                    Meta::METHODS_ARGS_ATTRIBUTES[$method][$path][$param->getName()][Status::class] = Status::findByParameter($param);
                }
            }
            
            if($reflection_class){
                Meta::KLASS[$method][$path] = $reflection_class;
                Meta::CLASS_ATTRIBUTES[$method][$path][Service::class] = Service::findByClass($reflection_class);
                Meta::CLASS_ATTRIBUTES[$method][$path][Singleton::class] = Singleton::findByClass($reflection_class);
                Meta::CLASS_ATTRIBUTES[$method][$path][Repository::class] = Repository::findByClass($reflection_class);
                Meta::CLASS_ATTRIBUTES[$method][$path][Consumes::class] = Consumes::findByClass($reflection_class);
                Meta::CLASS_ATTRIBUTES[$method][$path][Produces::class] = Produces::findByClass($reflection_class);
                Meta::CLASS_ATTRIBUTES[$method][$path][Entity::class] = Entity::findByClass($reflection_class);
                Meta::CLASS_ATTRIBUTES[$method][$path][Path::class] = Path::findByClass($reflection_class);
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
     * @param string $method http method of the 2 paths
     * @param string $from path to capture
     * @param string $to path to forward to
     */
    public static function forward(string $method, string $from, string $to):void{
        if(!isset(Meta::FUNCTIONS[$from][$method])){
            Meta::FUNCTIONS[$from][$method] = Meta::FUNCTIONS[$to][$method];
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
     * Define an event callback for the "COPY" http method.
     * @param string $path the path the event should listen to.
     * @param \Closure $callback the callback to execute.
     */
    public static function copy(string $path, \Closure $callback):void{
        if( !isset( Meta::FUNCTIONS_ARGS['COPY'] ) )
            Meta::FUNCTIONS_ARGS['COPY'] = array();
        
        $reflection_method = new \ReflectionFunction($callback);
        static::initialize('COPY',$path,null,null,$reflection_method);

        Meta::FUNCTIONS_ARGS['COPY'][$path] = $reflection_method->getParameters();

        if( !isset( Meta::FUNCTIONS['COPY'] ) )
            Meta::FUNCTIONS['COPY'] = array();

        Meta::FUNCTIONS['COPY'][$path] = $callback;
    }

    /**
     * Define an event callback for the "COPY" http method.
     * @param string $path the path the event should listen to.
     * @param \Closure $callback the callback to execute.
     */
    public static function delete(string $path, \Closure $callback):void{
        if( !isset( Meta::FUNCTIONS_ARGS['DELETE'] ) )
            Meta::FUNCTIONS_ARGS['DELETE'] = array();
        
        $reflection_method = new \ReflectionFunction($callback);
        static::initialize('DELETE',$path,null,null,$reflection_method);

        Meta::FUNCTIONS_ARGS['DELETE'][$path] = $reflection_method->getParameters();

        
        if( !isset( Meta::FUNCTIONS['DELETE'] ) )
            Meta::FUNCTIONS['DELETE'] = array();

        Meta::FUNCTIONS['DELETE'][$path] = $callback;
    }

    /**
     * Define an event callback for the "COPY" http method.
     * @param string $path the path the event should listen to.
     * @param \Closure $callback the callback to execute.
     */
    public static function get(string $path, \Closure $callback):void{
        if( !isset( Meta::FUNCTIONS_ARGS['GET'] ) )
            Meta::FUNCTIONS_ARGS['GET'] = array();
        
        $reflection_method = new \ReflectionFunction($callback);
        static::initialize('GET',$path,null,null,$reflection_method);
        
        Meta::FUNCTIONS_ARGS['GET'][$path] = $reflection_method->getParameters();

        
        if( !isset( Meta::FUNCTIONS['GET'] ) )
            Meta::FUNCTIONS['GET'] = array();

        Meta::FUNCTIONS['GET'][$path] = $callback;
    }

    /**
     * Define an event callback for the "HEAD" http method.
     * @param string $path the path the event should listen to.
     * @param \Closure $callback the callback to execute.
     */
    public static function head(string $path, \Closure $callback):void{
        if( !isset( Meta::FUNCTIONS_ARGS['HEAD'] ) )
            Meta::FUNCTIONS_ARGS['HEAD'] = array();
        
        $reflection_method = new \ReflectionFunction($callback);
        static::initialize('HEAD',$path,null,null,$reflection_method);
        
        Meta::FUNCTIONS_ARGS['HEAD'][$path] = $reflection_method->getParameters();

        
        if( !isset( Meta::FUNCTIONS['HEAD'] ) )
            Meta::FUNCTIONS['HEAD'] = array();

        Meta::FUNCTIONS['HEAD'][$path] = $callback;
    }
    
    /**
     * Define an event callback for the "LINK" http method.
     * @param string $path the path the event should listen to.
     * @param \Closure $callback the callback to execute.
     */
    public static function link(string $path, \Closure $callback):void{
        if( !isset( Meta::FUNCTIONS_ARGS['LINK'] ) )
            Meta::FUNCTIONS_ARGS['LINK'] = array();
        
        $reflection_method = new \ReflectionFunction($callback);
        static::initialize('LINK',$path,null,null,$reflection_method);
        
        Meta::FUNCTIONS_ARGS['LINK'][$path] = $reflection_method->getParameters();

        
        if( !isset( Meta::FUNCTIONS['LINK'] ) )
            Meta::FUNCTIONS['LINK'] = array();

        Meta::FUNCTIONS['LINK'][$path] = $callback;
    }
    
    /**
     * Define an event callback for the "LOCK" http method.
     * @param string $path the path the event should listen to.
     * @param \Closure $callback the callback to execute.
     */
    public static function lock(string $path, \Closure $callback):void{
        if( !isset( Meta::FUNCTIONS_ARGS['LOCK'] ) )
            Meta::FUNCTIONS_ARGS['LOCK'] = array();
        
        $reflection_method = new \ReflectionFunction($callback);
        static::initialize('LOCK',$path,null,null,$reflection_method);
        
        Meta::FUNCTIONS_ARGS['COPY'][$path] = $reflection_method->getParameters();

        
        if( !isset( Meta::FUNCTIONS['LOCK'] ) )
            Meta::FUNCTIONS['LOCK'] = array();

        Meta::FUNCTIONS['LOCK'][$path] = $callback;
    }
    
    /**
     * Define an event callback for the "OPTIONS" http method.
     * @param string $path the path the event should listen to.
     * @param \Closure $callback the callback to execute.
     */
    public static function options(string $path, \Closure $callback):void{
        if( !isset( Meta::FUNCTIONS_ARGS['OPTIONS'] ) )
            Meta::FUNCTIONS_ARGS['OPTIONS'] = array();
        
        $reflection_method = new \ReflectionFunction($callback);
        static::initialize('OPTIONS',$path,null,null,$reflection_method);
        
        Meta::FUNCTIONS_ARGS['COPY'][$path] = $reflection_method->getParameters();

        
        if( !isset( Meta::FUNCTIONS['OPTIONS'] ) )
            Meta::FUNCTIONS['OPTIONS'] = array();

        Meta::FUNCTIONS['OPTIONS'][$path] = $callback;
    }
    
    /**
     * Define an event callback for the "PATCH" http method.
     * @param string $path the path the event should listen to.
     * @param \Closure $callback the callback to execute.
     */
    public static function patch(string $path, \Closure $callback):void{
        if( !isset( Meta::FUNCTIONS_ARGS['PATCH'] ) )
            Meta::FUNCTIONS_ARGS['PATCH'] = array();
        
        $reflection_method = new \ReflectionFunction($callback);
        static::initialize('PATCH',$path,null,null,$reflection_method);
        
        Meta::FUNCTIONS_ARGS['PATCH'][$path] = $reflection_method->getParameters();

        
        if( !isset( Meta::FUNCTIONS['PATCH'] ) )
            Meta::FUNCTIONS['PATCH'] = array();

        Meta::FUNCTIONS['PATCH'][$path] = $callback;
    }
    
    /**
     * Define an event callback for the "POST" http method.
     * @param string $path the path the event should listen to.
     * @param \Closure $callback the callback to execute.
     */
    public static function post(string $path, \Closure $callback):void{
        if( !isset( Meta::FUNCTIONS_ARGS['POST'] ) )
            Meta::FUNCTIONS_ARGS['POST'] = array();
        
        $reflection_method = new \ReflectionFunction($callback);
        static::initialize('POST',$path,null,null,$reflection_method);
        
        Meta::FUNCTIONS_ARGS['POST'][$path] = $reflection_method->getParameters();

        
        if( !isset( Meta::FUNCTIONS['POST'] ) )
            Meta::FUNCTIONS['POST'] = array();

        Meta::FUNCTIONS['POST'][$path] = $callback;
    }
    
    /**
     * Define an event callback for the "PROPFIND" http method.
     * @param string $path the path the event should listen to.
     * @param \Closure $callback the callback to execute.
     */
    public static function propfind(string $path, \Closure $callback):void{
        if( !isset( Meta::FUNCTIONS_ARGS['PROPFIND'] ) )
            Meta::FUNCTIONS_ARGS['PROPFIND'] = array();
        
        $reflection_method = new \ReflectionFunction($callback);
        static::initialize('PROPFIND',$path,null,null,$reflection_method);
        
        Meta::FUNCTIONS_ARGS['PROPFIND'][$path] = $reflection_method->getParameters();

        
        if( !isset( Meta::FUNCTIONS['PROPFIND'] ) )
            Meta::FUNCTIONS['PROPFIND'] = array();

        Meta::FUNCTIONS['PROPFIND'][$path] = $callback;
    }
    
    /**
     * Define an event callback for the "PURGE" http method.
     * @param string $path the path the event should listen to.
     * @param \Closure $callback the callback to execute.
     */
    public static function purge(string $path, \Closure $callback):void{
        if( !isset( Meta::FUNCTIONS_ARGS['PURGE'] ) )
            Meta::FUNCTIONS_ARGS['PURGE'] = array();
        
        $reflection_method = new \ReflectionFunction($callback);
        static::initialize('PURGE',$path,null,null,$reflection_method);
        
        Meta::FUNCTIONS_ARGS['PURGE'][$path] = $reflection_method->getParameters();

        
        if( !isset( Meta::FUNCTIONS['PURGE'] ) )
            Meta::FUNCTIONS['PURGE'] = array();

        Meta::FUNCTIONS['PURGE'][$path] = $callback;
    }
    
    /**
     * Define an event callback for the "PUT" http method.
     * @param string $path the path the event should listen to.
     * @param \Closure $callback the callback to execute.
     */
    public static function put(string $path, \Closure $callback):void{
        if( !isset( Meta::FUNCTIONS_ARGS['PUT'] ) )
            Meta::FUNCTIONS_ARGS['PUT'] = array();
        
        $reflection_method = new \ReflectionFunction($callback);
        static::initialize('PUT',$path,null,null,$reflection_method);
        
        Meta::FUNCTIONS_ARGS['PUT'][$path] = $reflection_method->getParameters();

        
        if( !isset( Meta::FUNCTIONS['PUT'] ) )
            Meta::FUNCTIONS['PUT'] = array();

        Meta::FUNCTIONS['PUT'][$path] = $callback;
    }
    
    /**
     * Define an event callback for the "UNKNOWN" http method.
     * @param string $path the path the event should listen to.
     * @param \Closure $callback the callback to execute.
     */
    public static function unknown(string $path, \Closure $callback):void{
        if( !isset( Meta::FUNCTIONS_ARGS['UNKNOWN'] ) )
            Meta::FUNCTIONS_ARGS['UNKNOWN'] = array();
        
        $reflection_method = new \ReflectionFunction($callback);
        static::initialize('UNKNOWN',$path,null,null,$reflection_method);
        
        Meta::FUNCTIONS_ARGS['UNKNOWN'][$path] = $reflection_method->getParameters();

        
        if( !isset( Meta::FUNCTIONS['UNKNOWN'] ) )
            Meta::FUNCTIONS['UNKNOWN'] = array();

        Meta::FUNCTIONS['UNKNOWN'][$path] = $callback;
    }
    
    /**
     * Define an event callback for the "UNLINK" http method.
     * @param string $path the path the event should listen to.
     * @param \Closure $callback the callback to execute.
     */
    public static function unlink(string $path, \Closure $callback):void{
        if( !isset( Meta::FUNCTIONS_ARGS['UNLINK'] ) )
            Meta::FUNCTIONS_ARGS['UNLINK'] = array();
        
        $reflection_method = new \ReflectionFunction($callback);
        static::initialize('UNLINK',$path,null,null,$reflection_method);
        
        Meta::FUNCTIONS_ARGS['UNLINK'][$path] = $reflection_method->getParameters();

        
        if( !isset( Meta::FUNCTIONS['UNLINK'] ) )
            Meta::FUNCTIONS['UNLINK'] = array();

        Meta::FUNCTIONS['UNLINK'][$path] = $callback;
    }
    
    /**
     * Define an event callback for the "UNLOCK" http method.
     * @param string $path the path the event should listen to.
     * @param \Closure $callback the callback to execute.
     */
    public static function unlock(string $path, \Closure $callback):void{
        if( !isset( Meta::FUNCTIONS_ARGS['UNLOCK'] ) )
            Meta::FUNCTIONS_ARGS['UNLOCK'] = array();
        
        $reflection_method = new \ReflectionFunction($callback);
        static::initialize('UNLOCK',$path,null,null,$reflection_method);
        
        Meta::FUNCTIONS_ARGS['UNLOCK'][$path] = $reflection_method->getParameters();

        
        if( !isset( Meta::FUNCTIONS['UNLOCK'] ) )
            Meta::FUNCTIONS['UNLOCK'] = array();

        Meta::FUNCTIONS['UNLOCK'][$path] = $callback;
    }
    
    /**
     * Define an event callback for the "VIEW" http method.
     * @param string $path the path the event should listen to.
     * @param \Closure $callback the callback to execute.
     */
    public static function view(string $path, \Closure $callback):void{
        if( !isset( Meta::FUNCTIONS_ARGS['VIEW'] ) )
            Meta::FUNCTIONS_ARGS['VIEW'] = array();
        
        $reflection_method = new \ReflectionFunction($callback);
        static::initialize('VIEW',$path,null,null,$reflection_method);
        
        Meta::FUNCTIONS_ARGS['VIEW'][$path] = $reflection_method->getParameters();

        
        if( !isset( Meta::FUNCTIONS['VIEW'] ) )
            Meta::FUNCTIONS['VIEW'] = array();

        Meta::FUNCTIONS['VIEW'][$path] = $callback;
    }
}