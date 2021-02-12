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
use com\github\tncrazvan\catpaw\tools\AttributeResolver;
use com\github\tncrazvan\catpaw\tools\Status;
use \stdClass;

class Route{
    public static ?array $reflection_methods = [];
    public static ?array $reflection_methods_attributes_path = [];
    public static ?array $reflection_methods_attributes_consumes = [];
    public static ?array $reflection_methods_attributes_produces = [];

    public static ?array $reflection_methods_parameters = [];
    public static ?array $reflection_methods_parameters_attributes_status = [];
    public static ?array $reflection_methods_parameters_attributes_headers = [];

    public static ?array $reflection_functions = [];
    public static ?array $reflection_functions_attributes_path = [];
    public static ?array $reflection_functions_attributes_consumes = [];
    public static ?array $reflection_functions_attributes_produces = [];

    public static ?array $reflection_functions_parameters = [];
    public static ?array $reflection_functions_parameters_attributes_status = [];
    public static ?array $reflection_functions_parameters_attributes_headers = [];

    public static ?array $reflection_class = [];
    public static ?array $reflection_class_attributes_path = [];
    public static ?array $reflection_class_attributes_service = [];
    public static ?array $reflection_class_attributes_singleton = [];
    public static ?array $reflection_class_attributes_repository = [];
    public static ?array $reflection_class_attributes_consumes = [];
    public static ?array $reflection_class_attributes_produces = [];
    public static ?array $reflection_class_attributes_entity = [];


    public static array $path_params = [];
    
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
                static::$reflection_methods[$method][$path] = $reflection_method;
                static::$reflection_methods_parameters[$method][$path] = $reflection_method->getParameters();

                static::$reflection_methods_attributes_path[$method][$path] = Path::findByMethod($reflection_method);
                static::$reflection_methods_attributes_consumes[$method][$path] = Consumes::findByMethod($reflection_method);
                static::$reflection_methods_attributes_produces[$method][$path] = Produces::findByMethod($reflection_method);
                
                $params = $reflection_method->getParameters();
                foreach($params as $param){
                    $path_param = PathParam::findByParameter($param);
                    static::$path_params[$method][$path][$param->getName()] = $path_param;
                    static::$reflection_methods_parameters_attributes_headers[$method][$path][$param->getName()] = Headers::findByParameter($param);
                    static::$reflection_methods_parameters_attributes_status[$method][$path][$param->getName()] = Status::findByParameter($param);
                }
            }else if($reflection_function){
                static::$reflection_functions[$method][$path] = $reflection_method;
                static::$reflection_functions_parameters[$method][$path] = $reflection_function->getParameters();

                static::$reflection_functions_attributes_path[$method][$path] = Path::findByFunction($reflection_function);
                static::$reflection_functions_attributes_consumes[$method][$path] = Consumes::findByFunction($reflection_function);
                static::$reflection_functions_attributes_produces[$method][$path] = Produces::findByFunction($reflection_function);
                
                $params = $reflection_function->getParameters();
                foreach($params as $param){
                    $path_param = PathParam::findByParameter($param);
                    static::$path_params[$method][$path][$param->getName()] = $path_param;
                    static::$reflection_methods_parameters_attributes_headers[$method][$path][$param->getName()] = Headers::findByParameter($param);
                    static::$reflection_methods_parameters_attributes_status[$method][$path][$param->getName()] = Status::findByParameter($param);
                }
            }
            
            if($reflection_class){
                static::$reflection_class[$method][$path] = $reflection_class;
                static::$reflection_class_attributes_service[$method][$path] = Service::findByClass($reflection_class);
                static::$reflection_class_attributes_singleton[$method][$path] = Singleton::findByClass($reflection_class);
                static::$reflection_class_attributes_repository[$method][$path] = Repository::findByClass($reflection_class);
                static::$reflection_class_attributes_consumes[$method][$path] = Consumes::findByClass($reflection_class);
                static::$reflection_class_attributes_produces[$method][$path] = Produces::findByClass($reflection_class);
                static::$reflection_class_attributes_entity[$method][$path] = Entity::findByClass($reflection_class);
                static::$reflection_class_attributes_path[$method][$path] = Path::findByClass($reflection_class);
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
        if(!isset(static::$reflection_functions[$from][$method])){
            static::$reflection_functions[$from][$method] = static::$reflection_functions[$to][$method];
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
        if( !isset( static::$reflection_functions_parameters['COPY'] ) )
            static::$reflection_functions_parameters['COPY'] = array();
        
        $reflection_method = new \ReflectionFunction($callback);
        static::initialize('COPY',$path,null,null,$reflection_method);

        static::$reflection_functions_parameters['COPY'][$path] = $reflection_method->getParameters();

        if( !isset( static::$reflection_functions['COPY'] ) )
            static::$reflection_functions['COPY'] = array();

        static::$reflection_functions['COPY'][$path] = $callback;
    }

    /**
     * Define an event callback for the "COPY" http method.
     * @param string $path the path the event should listen to.
     * @param \Closure $callback the callback to execute.
     */
    public static function delete(string $path, \Closure $callback):void{
        if( !isset( static::$reflection_functions_parameters['DELETE'] ) )
            static::$reflection_functions_parameters['DELETE'] = array();
        
        $reflection_method = new \ReflectionFunction($callback);
        static::initialize('DELETE',$path,null,null,$reflection_method);

        static::$reflection_functions_parameters['DELETE'][$path] = $reflection_method->getParameters();

        
        if( !isset( static::$reflection_functions['DELETE'] ) )
            static::$reflection_functions['DELETE'] = array();

        static::$reflection_functions['DELETE'][$path] = $callback;
    }

    /**
     * Define an event callback for the "COPY" http method.
     * @param string $path the path the event should listen to.
     * @param \Closure $callback the callback to execute.
     */
    public static function get(string $path, \Closure $callback):void{
        if( !isset( static::$reflection_functions_parameters['GET'] ) )
            static::$reflection_functions_parameters['GET'] = array();
        
        $reflection_method = new \ReflectionFunction($callback);
        static::initialize('GET',$path,null,null,$reflection_method);
        
        static::$reflection_functions_parameters['GET'][$path] = $reflection_method->getParameters();

        
        if( !isset( static::$reflection_functions['GET'] ) )
            static::$reflection_functions['GET'] = array();

        static::$reflection_functions['GET'][$path] = $callback;
    }

    /**
     * Define an event callback for the "HEAD" http method.
     * @param string $path the path the event should listen to.
     * @param \Closure $callback the callback to execute.
     */
    public static function head(string $path, \Closure $callback):void{
        if( !isset( static::$reflection_functions_parameters['HEAD'] ) )
            static::$reflection_functions_parameters['HEAD'] = array();
        
        $reflection_method = new \ReflectionFunction($callback);
        static::initialize('HEAD',$path,null,null,$reflection_method);
        
        static::$reflection_functions_parameters['HEAD'][$path] = $reflection_method->getParameters();

        
        if( !isset( static::$reflection_functions['HEAD'] ) )
            static::$reflection_functions['HEAD'] = array();

        static::$reflection_functions['HEAD'][$path] = $callback;
    }
    
    /**
     * Define an event callback for the "LINK" http method.
     * @param string $path the path the event should listen to.
     * @param \Closure $callback the callback to execute.
     */
    public static function link(string $path, \Closure $callback):void{
        if( !isset( static::$reflection_functions_parameters['LINK'] ) )
            static::$reflection_functions_parameters['LINK'] = array();
        
        $reflection_method = new \ReflectionFunction($callback);
        static::initialize('LINK',$path,null,null,$reflection_method);
        
        static::$reflection_functions_parameters['LINK'][$path] = $reflection_method->getParameters();

        
        if( !isset( static::$reflection_functions['LINK'] ) )
            static::$reflection_functions['LINK'] = array();

        static::$reflection_functions['LINK'][$path] = $callback;
    }
    
    /**
     * Define an event callback for the "LOCK" http method.
     * @param string $path the path the event should listen to.
     * @param \Closure $callback the callback to execute.
     */
    public static function lock(string $path, \Closure $callback):void{
        if( !isset( static::$reflection_functions_parameters['LOCK'] ) )
            static::$reflection_functions_parameters['LOCK'] = array();
        
        $reflection_method = new \ReflectionFunction($callback);
        static::initialize('LOCK',$path,null,null,$reflection_method);
        
        static::$reflection_functions_parameters['COPY'][$path] = $reflection_method->getParameters();

        
        if( !isset( static::$reflection_functions['LOCK'] ) )
            static::$reflection_functions['LOCK'] = array();

        static::$reflection_functions['LOCK'][$path] = $callback;
    }
    
    /**
     * Define an event callback for the "OPTIONS" http method.
     * @param string $path the path the event should listen to.
     * @param \Closure $callback the callback to execute.
     */
    public static function options(string $path, \Closure $callback):void{
        if( !isset( static::$reflection_functions_parameters['OPTIONS'] ) )
            static::$reflection_functions_parameters['OPTIONS'] = array();
        
        $reflection_method = new \ReflectionFunction($callback);
        static::initialize('OPTIONS',$path,null,null,$reflection_method);
        
        static::$reflection_functions_parameters['COPY'][$path] = $reflection_method->getParameters();

        
        if( !isset( static::$reflection_functions['OPTIONS'] ) )
            static::$reflection_functions['OPTIONS'] = array();

        static::$reflection_functions['OPTIONS'][$path] = $callback;
    }
    
    /**
     * Define an event callback for the "PATCH" http method.
     * @param string $path the path the event should listen to.
     * @param \Closure $callback the callback to execute.
     */
    public static function patch(string $path, \Closure $callback):void{
        if( !isset( static::$reflection_functions_parameters['PATCH'] ) )
            static::$reflection_functions_parameters['PATCH'] = array();
        
        $reflection_method = new \ReflectionFunction($callback);
        static::initialize('PATCH',$path,null,null,$reflection_method);
        
        static::$reflection_functions_parameters['PATCH'][$path] = $reflection_method->getParameters();

        
        if( !isset( static::$reflection_functions['PATCH'] ) )
            static::$reflection_functions['PATCH'] = array();

        static::$reflection_functions['PATCH'][$path] = $callback;
    }
    
    /**
     * Define an event callback for the "POST" http method.
     * @param string $path the path the event should listen to.
     * @param \Closure $callback the callback to execute.
     */
    public static function post(string $path, \Closure $callback):void{
        if( !isset( static::$reflection_functions_parameters['POST'] ) )
            static::$reflection_functions_parameters['POST'] = array();
        
        $reflection_method = new \ReflectionFunction($callback);
        static::initialize('POST',$path,null,null,$reflection_method);
        
        static::$reflection_functions_parameters['POST'][$path] = $reflection_method->getParameters();

        
        if( !isset( static::$reflection_functions['POST'] ) )
            static::$reflection_functions['POST'] = array();

        static::$reflection_functions['POST'][$path] = $callback;
    }
    
    /**
     * Define an event callback for the "PROPFIND" http method.
     * @param string $path the path the event should listen to.
     * @param \Closure $callback the callback to execute.
     */
    public static function propfind(string $path, \Closure $callback):void{
        if( !isset( static::$reflection_functions_parameters['PROPFIND'] ) )
            static::$reflection_functions_parameters['PROPFIND'] = array();
        
        $reflection_method = new \ReflectionFunction($callback);
        static::initialize('PROPFIND',$path,null,null,$reflection_method);
        
        static::$reflection_functions_parameters['PROPFIND'][$path] = $reflection_method->getParameters();

        
        if( !isset( static::$reflection_functions['PROPFIND'] ) )
            static::$reflection_functions['PROPFIND'] = array();

        static::$reflection_functions['PROPFIND'][$path] = $callback;
    }
    
    /**
     * Define an event callback for the "PURGE" http method.
     * @param string $path the path the event should listen to.
     * @param \Closure $callback the callback to execute.
     */
    public static function purge(string $path, \Closure $callback):void{
        if( !isset( static::$reflection_functions_parameters['PURGE'] ) )
            static::$reflection_functions_parameters['PURGE'] = array();
        
        $reflection_method = new \ReflectionFunction($callback);
        static::initialize('PURGE',$path,null,null,$reflection_method);
        
        static::$reflection_functions_parameters['PURGE'][$path] = $reflection_method->getParameters();

        
        if( !isset( static::$reflection_functions['PURGE'] ) )
            static::$reflection_functions['PURGE'] = array();

        static::$reflection_functions['PURGE'][$path] = $callback;
    }
    
    /**
     * Define an event callback for the "PUT" http method.
     * @param string $path the path the event should listen to.
     * @param \Closure $callback the callback to execute.
     */
    public static function put(string $path, \Closure $callback):void{
        if( !isset( static::$reflection_functions_parameters['PUT'] ) )
            static::$reflection_functions_parameters['PUT'] = array();
        
        $reflection_method = new \ReflectionFunction($callback);
        static::initialize('PUT',$path,null,null,$reflection_method);
        
        static::$reflection_functions_parameters['PUT'][$path] = $reflection_method->getParameters();

        
        if( !isset( static::$reflection_functions['PUT'] ) )
            static::$reflection_functions['PUT'] = array();

        static::$reflection_functions['PUT'][$path] = $callback;
    }
    
    /**
     * Define an event callback for the "UNKNOWN" http method.
     * @param string $path the path the event should listen to.
     * @param \Closure $callback the callback to execute.
     */
    public static function unknown(string $path, \Closure $callback):void{
        if( !isset( static::$reflection_functions_parameters['UNKNOWN'] ) )
            static::$reflection_functions_parameters['UNKNOWN'] = array();
        
        $reflection_method = new \ReflectionFunction($callback);
        static::initialize('UNKNOWN',$path,null,null,$reflection_method);
        
        static::$reflection_functions_parameters['UNKNOWN'][$path] = $reflection_method->getParameters();

        
        if( !isset( static::$reflection_functions['UNKNOWN'] ) )
            static::$reflection_functions['UNKNOWN'] = array();

        static::$reflection_functions['UNKNOWN'][$path] = $callback;
    }
    
    /**
     * Define an event callback for the "UNLINK" http method.
     * @param string $path the path the event should listen to.
     * @param \Closure $callback the callback to execute.
     */
    public static function unlink(string $path, \Closure $callback):void{
        if( !isset( static::$reflection_functions_parameters['UNLINK'] ) )
            static::$reflection_functions_parameters['UNLINK'] = array();
        
        $reflection_method = new \ReflectionFunction($callback);
        static::initialize('UNLINK',$path,null,null,$reflection_method);
        
        static::$reflection_functions_parameters['UNLINK'][$path] = $reflection_method->getParameters();

        
        if( !isset( static::$reflection_functions['UNLINK'] ) )
            static::$reflection_functions['UNLINK'] = array();

        static::$reflection_functions['UNLINK'][$path] = $callback;
    }
    
    /**
     * Define an event callback for the "UNLOCK" http method.
     * @param string $path the path the event should listen to.
     * @param \Closure $callback the callback to execute.
     */
    public static function unlock(string $path, \Closure $callback):void{
        if( !isset( static::$reflection_functions_parameters['UNLOCK'] ) )
            static::$reflection_functions_parameters['UNLOCK'] = array();
        
        $reflection_method = new \ReflectionFunction($callback);
        static::initialize('UNLOCK',$path,null,null,$reflection_method);
        
        static::$reflection_functions_parameters['UNLOCK'][$path] = $reflection_method->getParameters();

        
        if( !isset( static::$reflection_functions['UNLOCK'] ) )
            static::$reflection_functions['UNLOCK'] = array();

        static::$reflection_functions['UNLOCK'][$path] = $callback;
    }
    
    /**
     * Define an event callback for the "VIEW" http method.
     * @param string $path the path the event should listen to.
     * @param \Closure $callback the callback to execute.
     */
    public static function view(string $path, \Closure $callback):void{
        if( !isset( static::$reflection_functions_parameters['VIEW'] ) )
            static::$reflection_functions_parameters['VIEW'] = array();
        
        $reflection_method = new \ReflectionFunction($callback);
        static::initialize('VIEW',$path,null,null,$reflection_method);
        
        static::$reflection_functions_parameters['VIEW'][$path] = $reflection_method->getParameters();

        
        if( !isset( static::$reflection_functions['VIEW'] ) )
            static::$reflection_functions['VIEW'] = array();

        static::$reflection_functions['VIEW'][$path] = $callback;
    }
}