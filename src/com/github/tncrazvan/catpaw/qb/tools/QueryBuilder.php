<?php

namespace com\github\tncrazvan\catpaw\qb\tools;

use com\github\tncrazvan\catpaw\qb\tools\Binding;
use com\github\tncrazvan\catpaw\qb\tools\QueryConst;
use com\github\tncrazvan\catpaw\qb\traits\From;
use com\github\tncrazvan\catpaw\qb\traits\Join;
use com\github\tncrazvan\catpaw\qb\traits\Alias;
use com\github\tncrazvan\catpaw\qb\traits\Clause;
use com\github\tncrazvan\catpaw\qb\traits\Delete;
use com\github\tncrazvan\catpaw\qb\traits\Insert;
use com\github\tncrazvan\catpaw\qb\traits\Select;
use com\github\tncrazvan\catpaw\qb\traits\Update;

class QueryBuilder implements QueryConst{
    use From;
    use Select;
    use Alias;
    use Join;
    use Clause;
    use Insert;
    use Update;
    use Delete;
    
    private $database;
    private $query = "";
    private $selections = null;
    private $bindings;
    private $alias = [];
    protected $current_classname = '';
    public function __construct(\PDO $database){
        $this->database = $database;
        $this->bindings = [];
    }

    public function reset():void{
        $this->query = "";
        $this->selections = null;
        $this->bindings = [];
        $this->alias = [];
        $this->firstMatch = true;
        $this->current_classname = '';
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
    public function fetchObject(string $classname){
        return $this->execute(\PDO::FETCH_CLASS,$classname);
    }

    /**
     * Execute the prepared statement.
     * @return array the results of the statement.
     */
    public function fetchAssoc(){
        return $this->execute(\PDO::FETCH_ASSOC);
    }

    

    /**
     * Execute the prepared statement.
     * @return mixed the result of the statement.
     */
    public function execute(int $fetch_style = -1, $fetch_argument = null){
        $results = [];

        $stm = $this->database->prepare($this->query);
        foreach($this->bindings as $key => &$binding){
            $value = $binding->getValue();
            $type = $binding->getType();
            $stm->bindValue(self::VARIABLE_SYMBOL.$key,$value,$type);
        }
        //echo "\n\nQUERY: {$this->query}\n\n\n";
        try{
            if (!$stm->execute()) 
                throw new \Exception(json_encode($stm->errorInfo()));
            
            if($fetch_style >= 0)
            if($fetch_argument === null)
                $results = $stm->fetchAll($fetch_style);
            else
                if($fetch_style === \PDO::FETCH_CLASS){
                    $results = $stm->fetchObject($fetch_argument);
                    return !$results?null:$results;
                }else
                    $results = $stm->fetchAll($fetch_style,$fetch_argument);
        }catch(\Throwable $e){
            echo "{$e->getMessage()}\n{$e->getTraceAsString()}\n";
            return false;
        }
        
        return $results;
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
