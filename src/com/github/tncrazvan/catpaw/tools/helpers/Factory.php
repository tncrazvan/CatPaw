<?php
namespace com\github\tncrazvan\catpaw\tools\helpers;

use Closure;
use com\github\tncrazvan\catpaw\attributes\Entry;
use com\github\tncrazvan\catpaw\attributes\Extend;
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
use com\github\tncrazvan\catpaw\attributes\http\Path;
use com\github\tncrazvan\catpaw\attributes\Inject;
use com\github\tncrazvan\catpaw\attributes\Repository;
use com\github\tncrazvan\catpaw\attributes\Service;
use com\github\tncrazvan\catpaw\attributes\Singleton;
use com\github\tncrazvan\catpaw\tools\actions\ArrayAction;
use com\github\tncrazvan\catpaw\tools\AttributeResolver;
use com\github\tncrazvan\catpaw\tools\Strings;

class Factory{

    private static array $args = [];
    public static function setConstructorInjector(string $classname,?Closure $args=null):void{
        static::$args[$classname] = $args;
    }

    public static function getConstructorInjector(string $classname):Closure{
        if(!isset(static::$args[$classname])) return fn()=>[];
        return static::$args[$classname];
    }

    private static function interfaceExtendsClass(\ReflectionClass $reflection_class):string{
        foreach($reflection_class->getInterfaces() as $reflection_interface){
            if(($extend = Extend::findByClass($reflection_interface)))
                return $extend->getClassName();
        }
        return '';
    }

    private static function adaptToRepository(\ReflectionClass $reflection_class, $instance):void{
        foreach($reflection_class->getInterfaces() as $interface){
            if(($repository = Repository::findByClass($reflection_class))){
                $entity_classname = $repository->getEntityClassName();
                $entity_id = $repository->getEntityId();
                $instance->classname = $entity_classname;
                $instance->id = $entity_id;
            }
        }
    }

    /**
     * Make a new instance of the given class.<br />
     * This method will take care of dependency injections.
     * @param string $classname full name name of the class
     */
    public static function make(string $classname, bool $lazy_paths = true):?object{
        if(isset(Singleton::$map[$classname]))
            return Singleton::$map[$classname];
        
        $reflection_class = new \ReflectionClass($classname);

        if($reflection_class->isInterface())
            return null;

        if(count($reflection_class->getAttributes()) === 0) return null;

        $singleton = Singleton::findByClass($reflection_class);
        $repository = Repository::findByClass($reflection_class);
        $service = Service::findByClass($reflection_class);

        $methods = $reflection_class->getMethods();
        $args = isset(static::$args[$classname])? static::$args[$classname]() : [];

        if($reflection_class->getConstructor() !== null) {
            $i = 0;
            foreach ($reflection_class->getConstructor()->getParameters() as &$parameter) {
                if(Inject::findByParameter($parameter)) {
                    $args[$i] = Factory::make($parameter->getType()->getname(),false);
                }
                $i++;
            }
        }

        //resolve other class attributes
        ############################################################################
        if($singleton || $service || $repository){
            Singleton::$map[$classname] = new $classname(...$args);
            if($repository)
                static::adaptToRepository($reflection_class,Singleton::$map[$classname]);
        }


        $instance = 
            $singleton || $service || $repository ?
                    //then
                    Singleton::$map[$classname]
                
                :   //else
                
                    new $classname(...$args)
                
        ;
        
        ############################################################################

        //resolve main "Path" attribute
        ##################################################################################################################
        $path = Path::findByClass($reflection_class);
        if($path){
            if(!$lazy_paths)
                AttributeResolver::injectProperties($classname,$instance);
            static::path($reflection_class,$methods,$path,$singleton,$classname,$lazy_paths);
        }else
            AttributeResolver::injectProperties($classname,$instance);
        

        static::entry($methods,$instance,$classname);

        return $instance;
    }

    private static function path(
        \ReflectionClass $reflection_class,
        array &$methods,
        Path $path,
        ?Singleton $singleton,
        string $classname,
        bool $inject):void{
        $map = [];
        $i = 0;
        static::findHttpMethods($methods,function(string $http_method, \ReflectionMethod $reflection_method) use (&$map,&$i,&$path){
            $local_path = Path::findByMethod($reflection_method);
            
            $map[$i] = [
                'method' => $http_method,
                'path' => $local_path?\preg_replace('/^\/+/','',$local_path->getValue()):'',
                'fname' => $reflection_method->getName()
            ];
            $i++;
        });

        $base_path = \preg_replace('/\/+$/','', $path->getValue());
        if(!Strings::startsWith($base_path,'/'))
            $base_path = "/$base_path";
            
        if($singleton){
            Route::map($map, $reflection_class, $classname, $base_path, $inject, (Singleton::class)."::\$map['$classname']");
        }else {
            $factory = Factory::class;
            Route::map($map, $reflection_class, $classname, $base_path, $inject, "new $classname(...$factory::getConstructorInjector('$classname')())");
        }
    }

    private static function entry($methods,$instance,string &$classname):void{
        foreach($methods as $method){
            $entry = Entry::findByMethod($method);
            if($entry){
                if($method->isStatic()){
                    $method->invoke(null,...[]);
                }else{
                    AttributeResolver::injectProperties($classname,$instance);
                    $method->invoke($instance,...[]);
                }
                break;
            }
        }
    }

    private static function findHttpMethods(array $methods, \Closure $callback):void{
        //resolve methods attributes
        ############################################################################
        foreach($methods as &$method){
            if($method->isStatic()) 
                continue;
            if(COPY::findByMethod($method)) $callback('COPY',$method);
            else if (DELETE::findByMethod($method)) $callback( 'DELETE',$method);
            else if (GET::findByMethod($method)) $callback('GET',$method);
            else if (HEAD::findByMethod($method)) $callback('HEAD',$method);
            else if (LINK::findByMethod($method)) $callback('LINK',$method);
            else if (LOCK::findByMethod($method)) $callback('LOCK',$method);
            else if (OPTIONS::findByMethod($method)) $callback('OPTIONS',$method);
            else if (PATCH::findByMethod($method)) $callback('PATCH',$method);
            else if (POST::findByMethod($method)) $callback('POST',$method);
            else if (PROPFIND::findByMethod($method)) $callback('PROPFIND',$method);
            else if (PURGE::findByMethod($method)) $callback('PURGE',$method);
            else if (PUT::findByMethod($method)) $callback('PUT',$method);
            else if (UNKNOWN::findByMethod($method)) $callback('UNKNOWN',$method);
            else if (UNLINK::findByMethod($method)) $callback('UNLINK',$method);
            else if (UNLOCK::findByMethod($method)) $callback('UNLOCK',$method);
            else if (VIEW::findByMethod($method)) $callback('VIEW',$method);
            else continue;
        }
        ############################################################################
    }
}