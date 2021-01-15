<?php
namespace com\github\tncrazvan\catpaw\tools\helpers;

use com\github\tncrazvan\catpaw\attributes\Entry;
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
use com\github\tncrazvan\catpaw\attributes\Singleton;
use com\github\tncrazvan\catpaw\tools\AttributeResolver;
use com\github\tncrazvan\catpaw\tools\Strings;

class Factory{
    /**
     * Make a new route from a classname.<br />
     * @param string $basePath Base path of your route (must always start with "/")
     * @param string $classname classname name of your class (usually MyClass::class)
     */
    public static function make(string $classname,...$args):?object{
        $reflection_class = new \ReflectionClass($classname);
        $methods = $reflection_class->getMethods();

        //resolve other class attributes
        ############################################################################
        $singleton = Singleton::findByClass($reflection_class);
        if($singleton && !isset(Singleton::$map[$classname]))
            Singleton::$map[$classname] = new $classname(...$args);

        $instance = $singleton ? Singleton::$map[$classname] : new $classname(...$args);
        ############################################################################

        //resolve main "Path" attribute
        ##################################################################################################################
        $path = Path::findByClass($reflection_class);
        if($path)
            static::path($reflection_class,$methods,$path,$singleton,$classname);
        else
            AttributeResolver::injectProperties($classname,$instance);
        

        static::entry($methods,$instance);

        return $instance;
    }

    private static function path(
        \ReflectionClass $reflection_class,
        array &$methods,
        Path $path,
        Singleton $singleton,
        string $classname):void{
        $map = [];
        $i = 0;
        static::findHttpMethods($methods,function(string $http_method, \ReflectionMethod $reflection_method) use (&$map,&$i){
            $map[$i] = [
                'method' => $http_method,
                'path' => '',
                'fname' => $reflection_method->getName()
            ];
        });

        $base_path = \preg_replace('/\/+$/','', $path->getValue());
        if(!Strings::startsWith($base_path,'/'))
            $base_path = "/$base_path";
            
        if($singleton){
            Route::map($map, $reflection_class, $classname, $base_path, (Singleton::class)."::\$map['$classname']");
        }else {
            Route::map($map, $reflection_class, $classname, $base_path, "new $classname()");
        }
    }

    private static function entry($methods,$instance):void{
        foreach($methods as $method){
            $entry = Entry::findByMethod($method);
            if($entry){
                $method->invoke($instance,...[]);
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