<?php
namespace com\github\tncrazvan\catpaw\tools\helpers;

use Closure;
use com\github\tncrazvan\catpaw\attributes\ApplicationScoped;
use com\github\tncrazvan\catpaw\attributes\AttributeResolver;
use com\github\tncrazvan\catpaw\attributes\database\Column;
use com\github\tncrazvan\catpaw\attributes\database\Id;
use com\github\tncrazvan\catpaw\tools\helpers\Entity;
use com\github\tncrazvan\catpaw\attributes\Entry;
use com\github\tncrazvan\catpaw\attributes\Extend;
use com\github\tncrazvan\catpaw\attributes\Filter;
use com\github\tncrazvan\catpaw\attributes\FilterItem;
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
use com\github\tncrazvan\catpaw\tools\helpers\Route;
use com\github\tncrazvan\catpaw\tools\helpers\CrudRepository;
use Exception;
use React\EventLoop\LoopInterface;

class Factory{
    private static array $tables = [];
    
    private static array $singletons = [];

    private static array $args = [];

    public static function isset(string $classname):bool{
        return isset(static::$singletons[$classname]);
    }

    public static function setObject(string $classname, mixed $object):void{
        static::$singletons[$classname] = $object;
    }

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

    private static function adaptToEntity(\ReflectionClass $reflection_class, Entity $entity):void{
        static::$singletons[$reflection_class->getName()] = $entity;
        $tableName = $entity->tableName();
        $entity->setTableName($tableName!==''?$tableName:\strtolower($reflection_class->getShortName()));
        $columns = [];
        $pks = [];
        foreach($reflection_class->getProperties() as $reflection_property){
            $id = Id::findByProperty($reflection_property);
            if($id){
                $colName = $id->getName();
                if($colName === '') $colName = $reflection_property->getName();
                $colType = $id->getType();
                if($colType < 0){
                    switch($reflection_property->getType()->getName()){
                        case 'int':
                            $colType = \PDO::PARAM_INT;
                        break;
                        case 'float':
                        case 'string':
                        default:
                            $colType = \PDO::PARAM_STR;
                        break;
                    }
                }
                $columns[$colName] = $colType;
                $pks[] = $colName;
            }else{
                $column = Column::findByProperty($reflection_property);
                if($column){
                    $colName = $column->getName();
                    if($colName === '') $colName = $reflection_property->getName();
                    $colType = $column->getType();
                    if($colType < 0){
                        switch($reflection_property->getType()->getName()){
                            case 'int':
                                $colType = \PDO::PARAM_INT;
                            break;
                            case 'float':
                            case 'string':
                            default:
                                $colType = \PDO::PARAM_STR;
                            break;
                        }
                    }
                    $columns[$colName] = $colType;
                }
            }
        }
        
        $entity->setColumns($columns);
        $entity->setPrimaryKeys($pks);
        $entity->reset_columns();
        
        //Entity::_sync_entity_columns_from_props($entity,$object);
    }
    private static function adaptToRepository(CrudRepository $instance, Repository $repository):void{
        $entity_classname = $repository->getEntityClassName();
        $entity_id = $repository->getEntityId();
        $instance->classname = $entity_classname;
        $instance->id = $entity_id;   
    }

    /**
     * Make a new instance of the given class.<br />
     * This method will take care of dependency injections.
     * @param string $classname full name name of the class
     */
    public static function make(string $classname, bool $lazy_paths = true):?object{
        if(isset(static::$singletons[$classname]))
            return static::$singletons[$classname];
        
        $reflection_class = new \ReflectionClass($classname);

        if($reflection_class->isInterface())
            return null;

        if(count($reflection_class->getAttributes()) === 0) return null;

        $entity = Entity::findByClass($reflection_class);

        if($entity){
            static::adaptToEntity($reflection_class,$entity);
            return $entity;
        }


        $service = $entity?null:Service::findByClass($reflection_class);
        $repository = $service||$entity?null:Repository::findByClass($reflection_class);
        $singleton = $entity?null:Singleton::findByClass($reflection_class);
        $filter_item = $entity?null:FilterItem::findByClass($reflection_class);
        $scoped = $entity?null:ApplicationScoped::findByClass($reflection_class);

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
        if($singleton || $service || $repository || $filter_item){
            static::$singletons[$classname] = new $classname(...$args);
            if($repository)
                static::adaptToRepository(static::$singletons[$classname],$repository);
        }


        $instance = 
            $singleton || $service || $repository || $filter_item ?
                    //then
                    static::$singletons[$classname]
                
                :   //else
                
                    new $classname(...$args)
                
        ;
        
        ############################################################################

        //resolve main "Path" attribute
        ##################################################################################################################
        $path = $entity?null:Path::findByClass($reflection_class);
        if($path){
            if(!$lazy_paths)
                AttributeResolver::injectProperties($classname,$instance);
            static::path(
                $instance,
                $reflection_class,
                $methods,
                $path,
                $singleton,
                $classname,
                $lazy_paths
            );
        }else
            AttributeResolver::injectProperties($classname,$instance);
        
        if($scoped)
            static::entry($methods,$instance,$classname);

        return $instance;
    }

    private static function path(
        mixed &$instance,
        \ReflectionClass $reflection_class,
        array &$methods,
        Path $path,
        ?Singleton $singleton,
        string $classname,
        bool $inject
    ):void{
        $map = [];
        $i = 0;
        static::findHttpMethods($methods,function(string $http_method, \ReflectionMethod $reflection_method) use (&$map,&$i){
            $local_path = Path::findByMethod($reflection_method);
            
            $map[$i] = [
                'method' => $http_method,
                'path' => $local_path?\preg_replace('/^\/+/','',$local_path->getValue()):'',
                'fname' => $reflection_method->getName()
            ];
            $i++;
        });

        $base_path = \preg_replace('/\/+$/','', $path->getValue());
        if(!str_starts_with($base_path,'/'))
            $base_path = "/$base_path";
            
        if($singleton){
            Route::map(
                $reflection_class,
                $base_path,
                $map
            );
        }else {
            $factory = Factory::class;
            Route::map(
                $reflection_class,
                $base_path,
                $map
            );
        }
    }

    private static function entry(array &$methods,mixed $instance,string &$classname):void{
        foreach($methods as $method){
            
            $entry = Entry::findByMethod($method);
            if($entry){
                if($method instanceof \ReflectionMethod){
                    $args = [];
                    $i = 0;
                    foreach($method->getParameters() as $parameter){
                        if(Inject::findByParameter($parameter)) {
                            $args[$i] = Factory::make($parameter->getType()->getname(),false);
                        }
                        $i++;
                    }
                    if($method->isStatic()){
                        $result = $method->invoke(null,...$args);
                    }else{
                        AttributeResolver::injectProperties($classname,$instance);
                        $result = $method->invoke($instance,...$args);
                    }

                    if($result instanceof \Generator){
                        if(!isset(static::$singletons[LoopInterface::class])){
                            throw new Exception("Entry of class $classname could not be executed because it returns a Generator and no main loop singleton has been registered.");
                        }
                        $loop = static::$singletons[LoopInterface::class];
                        Yielder::toPromise($loop,$result);
                    }
                    break;
                }
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