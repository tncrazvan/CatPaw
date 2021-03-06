<?php

namespace net\razshare\catpaw\qb\tools;

use net\razshare\catpaw\qb\tools\Binding;
use net\razshare\catpaw\qb\tools\QueryConst;
use net\razshare\catpaw\qb\traits\From;
use net\razshare\catpaw\qb\traits\Join;
use net\razshare\catpaw\qb\traits\Alias;
use net\razshare\catpaw\qb\traits\Clause;
use net\razshare\catpaw\qb\traits\Delete;
use net\razshare\catpaw\qb\traits\Insert;
use net\razshare\catpaw\qb\traits\Select;
use net\razshare\catpaw\qb\traits\Update;
use net\razshare\catpaw\qb\traits\Limit;
use net\razshare\catpaw\qb\traits\Order;
use React\EventLoop\LoopInterface;
use React\Promise\Promise;

class QueryBuilder implements QueryConst{
    use From;
    use Select;
    use Alias;
    use Join;
    use Clause;
    use Insert;
    use Update;
    use Delete;
    use Limit;
    use Order;
    
    //private $database;
    private $query = "";
    private $selections = null;
    private $bindings;
    private $alias = [];
    protected $current_classname = '';
    public function __construct(
        private \PDO $database,
        private LoopInterface $loop
    ){
        //$this->database = $database;
        $this->bindings = [];
    }

    public function getDatabase():\PDO{
        return $this->database;
    }

    public function reset():void{
        $this->query = "";
        $this->selections = null;
        $this->bindings = [];
        $this->alias = [];
        $this->firstMatch = true;
        $this->current_classname = '';
        $this->ordered = 0;
    }

    /**
     * Add a string to the query
     * @param string $value the string to be added to the query
     * @return QueryBuilder $this QueryBuilder
     */
    private function add(string $value,bool $prepend=false):QueryBuilder{
        if($prepend){
            $this->query = $value.' '.$this->query;
            return $this;
        }
        $this->query .= " $value";
        return $this;
    }

    /**
     * Add an object to the bindings array. 
     * This array will be iterated before executing the prepared statement, 
     * and every match will be bound to the statement as a value.
     * @param string $name this is the name of the Binding object
     * @param Binding $object this is the Binding object, it contains a type and a value
     * @return QueryBuilder $this QueryBuilder
     */
    public function bind(string $name, Binding $object):QueryBuilder{
        $this->bindings[$name] = $object;
        return $this;
    }

    /**
     * Execute the prepared statement.
     * @return mixed the first result of the statement as class $classname
     */
    public function fetchObjects(string $classname):Promise{
        return $this->execute(\PDO::FETCH_CLASS,$classname);
    }

    /**
     * Execute the prepared statement.
     * @return array the results of the statement.
     */
    public function fetchAssoc():Promise{
        return $this->execute(\PDO::FETCH_ASSOC);
    }

    private function fetch(\PDOStatement $stm, array &$result, \Closure &$resolve, int $fetch_style):void{
        $this->loop->futureTick(function() use(&$stm,&$resolve,&$result,&$fetch_style){
            $item = $stm->fetch();
            if(!$item) {
                $resolve($result);
                return;
            }
            $result[] = $item;
            $this->fetch($stm,$result,$resolve,$fetch_style);
        });
    }

    /**
     * Execute the prepared statement.
     * @return mixed the result of the statement.
     */
    public function execute(int $fetch_style = -1, $fetch_argument = null):Promise{
        $results = [];

        $stm = $this->database->prepare($this->query,array(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => false));
        foreach($this->bindings as $key => &$binding){
            $value = $binding->getValue();
            $type = $binding->getType();
            $stm->bindValue(self::VARIABLE_SYMBOL.$key,$value,$type);
        }
        //echo "\n\nQUERY: {$this->query}\n\n\n";
        // try{
            if (!$stm->execute()) 
                throw new \Exception(json_encode($stm->errorInfo()));
            
            if($fetch_style >= 0)
                if($fetch_argument === null){
                    //results = $stm->fetchAll($fetch_style);
                    return new Promise(function($resolve) use(&$stm,&$fetch_style){
                        $result = [];
                        $stm->setFetchMode($fetch_style);
                        $this->fetch($stm,$result,$resolve,$fetch_style);
                    });
                }else
                    // if($fetch_style === \PDO::FETCH_CLASS){
                    //     return new Promise(function($resolve) use(&$stm,&$fetch_argument){
                    //         $results = $stm->fetchObject($fetch_argument);
                    //         $resolve(!$results?null:$results);
                    //     });
                    // }else{
                        //$results = $stm->fetchAll($fetch_style,$fetch_argument);
                        return new Promise(function($resolve) use(&$stm,&$fetch_style,&$fetch_argument){
                            $result = [];
                            $stm->setFetchMode($fetch_style,$fetch_argument);
                            $this->fetch($stm,$result,$resolve,$fetch_style);
                        });
                    //}
        // }catch(\Throwable $e){
        //     return new Promise(function($resolve,$rejected) use(&$e){
        //         $rejected($e);
        //     });
        // }
        
        return new Promise(function($resolve) use(&$results){
            $resolve($results);
        });
    }

    
    /**
     * Get the query as a string. Alias of QueryBuilder::getQuery.
     * @return string the query string
     */
    public function toString():string{
        return $this->query;
    }

    /**
     * Get the query as a string
     * @return string the query string
     */
    public function getQuery():string{
        return $this->query;
    }

}
