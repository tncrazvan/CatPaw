<?php
namespace com\github\tncrazvan\catpaw\tools\helpers;

use ReflectionClass;

class Route{
    private function __construct(){}
    private static ?Route $singleton = null;

    public static function make():Route{
        if(static::$singleton == null)
        static::$singleton = new self();

        return static::$singleton;
    }

    private static array $httpEvents = [];
    private static string $currentTarget = '';
    private function resolveTarget(string $method,$target){
        if(is_string($target)){
            $reflectionClass = new ReflectionClass($target);
            $fname = trim($method);
            $reflectionMethod = $reflectionClass->getMethod($fname);
            $reflectionParameters = $reflectionMethod->getParameters();
            $size = $reflectionMethod->getNumberOfParameters();
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
            
            $script =<<<EOF
            return function($namedAndTypedParamsString){
                \$instance = new $target();
                return \$instance->$fname($namedParamsString);
            };
            EOF;
            return eval($script);
        }
        return $target;
    }

    public function target(string $classname):Route{
        static::$currentTarget = $classname;
        return $this;
    }

    public function untarget():void{
        static::$currentTarget = '';
    }

    public function forward(string $from, string $to):void{
        if(!isset(static::$httpEvents["@forward"]))
            static::$httpEvents["@forward"][$from] = $to;
    }

    public function notFound( $block):void{
        if(!isset(static::$httpEvents["@404"]))
            static::$httpEvents["@404"] = array();

        static::$httpEvents["@404"]["GET"] = $this->resolveTarget("GET",static::$currentTarget);
    }

    public function copy(string $path):Route{
        if(!isset(static::$httpEvents[$path]))
            static::$httpEvents[$path] = array();

        static::$httpEvents[$path]["COPY"] = $this->resolveTarget("COPY",static::$currentTarget);
        return $this;
    }

    public function delete(string $path):Route{
        if(!isset(static::$httpEvents[$path]))
            static::$httpEvents[$path] = array();

        static::$httpEvents[$path]["DELETE"] = $this->resolveTarget("DELETE",static::$currentTarget);
        return $this;
    }

    public function get(string $path):Route{
        if(!isset(static::$httpEvents[$path]))
            static::$httpEvents[$path] = array();

        static::$httpEvents[$path]["GET"] = $this->resolveTarget("GET",static::$currentTarget);
        return $this;
    }

    public function head(string $path):Route{
        if(!isset(static::$httpEvents[$path]))
            static::$httpEvents[$path] = array();

        static::$httpEvents[$path]["HEAD"] = $this->resolveTarget("HEAD",static::$currentTarget);
        return $this;
    }
    
    public function link(string $path):Route{
        if(!isset(static::$httpEvents[$path]))
            static::$httpEvents[$path] = array();

        static::$httpEvents[$path]["LINK"] = $this->resolveTarget("LINK",static::$currentTarget);
        return $this;
    }
    
    public function lock(string $path):Route{
        if(!isset(static::$httpEvents[$path]))
            static::$httpEvents[$path] = array();

        static::$httpEvents[$path]["LOCK"] = $this->resolveTarget("LOCK",static::$currentTarget);
        return $this;
    }
    
    public function options(string $path):Route{
        if(!isset(static::$httpEvents[$path]))
            static::$httpEvents[$path] = array();

        static::$httpEvents[$path]["OPTIONS"] = $this->resolveTarget("OPTIONS",static::$currentTarget);
        return $this;
    }
    
    public function patch(string $path):Route{
        if(!isset(static::$httpEvents[$path]))
            static::$httpEvents[$path] = array();

        static::$httpEvents[$path]["PATCH"] = $this->resolveTarget("PATCH",static::$currentTarget);
        return $this;
    }
    
    public function post(string $path):Route{
        if(!isset(static::$httpEvents[$path]))
            static::$httpEvents[$path] = array();

        static::$httpEvents[$path]["POST"] = $this->resolveTarget("POST",static::$currentTarget);
        return $this;
    }
    
    public function propfind(string $path):Route{
        if(!isset(static::$httpEvents[$path]))
            static::$httpEvents[$path] = array();

        static::$httpEvents[$path]["PROPFIND"] = $this->resolveTarget("PROPFIND",static::$currentTarget);
        return $this;
    }
    
    public function purge(string $path):Route{
        if(!isset(static::$httpEvents[$path]))
            static::$httpEvents[$path] = array();

        static::$httpEvents[$path]["PURGE"] = $this->resolveTarget("PURGE",static::$currentTarget);
        return $this;
    }
    
    public function put(string $path):Route{
        if(!isset(static::$httpEvents[$path]))
            static::$httpEvents[$path] = array();

        static::$httpEvents[$path]["PUT"] = $this->resolveTarget("PUT",static::$currentTarget);
        return $this;
    }
    
    public function unknown(string $path):Route{
        if(!isset(static::$httpEvents[$path]))
            static::$httpEvents[$path] = array();

        static::$httpEvents[$path]["UNKNOWN"] = $this->resolveTarget("UNKNOWN",static::$currentTarget);
        return $this;
    }
    
    public function unlink(string $path):Route{
        if(!isset(static::$httpEvents[$path]))
            static::$httpEvents[$path] = array();

        static::$httpEvents[$path]["UNLINK"] = $this->resolveTarget("UNLINK",static::$currentTarget);
        return $this;
    }
    
    public function unlock(string $path):Route{
        if(!isset(static::$httpEvents[$path]))
            static::$httpEvents[$path] = array();

        static::$httpEvents[$path]["UNLOCK"] = $this->resolveTarget("UNLOCK",static::$currentTarget);
        return $this;
    }
    
    public function view(string $path):Route{
        if(!isset(static::$httpEvents[$path]))
            static::$httpEvents[$path] = array();

        static::$httpEvents[$path]["VIEW"] = $this->resolveTarget("VIEW",static::$currentTarget);
        return $this;
    }
    

    public static function &getHttpEvents():array{
        return static::$httpEvents;
    }
}