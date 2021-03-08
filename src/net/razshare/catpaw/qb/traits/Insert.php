<?php
namespace net\razshare\catpaw\qb\traits;

use net\razshare\catpaw\tools\helpers\Factory;
use net\razshare\catpaw\qb\tools\Binding;
use net\razshare\catpaw\qb\tools\Column;
use net\razshare\catpaw\qb\tools\CoreEntity;
use net\razshare\catpaw\qb\tools\QueryBuilder;
use net\razshare\catpaw\qb\tools\interfaces\IntoCallback;
use net\razshare\catpaw\qb\tools\QueryConst;
use net\razshare\catpaw\tools\helpers\Entity;

trait Insert{
    private ?IntoCallback $default_into_callback = null;
    /**
     * Insert data.
     * @param mixed $repository an object of a class that extends Repository, this object will be inserted to the database.
     * @param callable $callback a callback function which if specified will be passed a clone of the original $repository.
     * This cloned object can be inserted to the database instead of the $repository object.
     * This can be useful if you want to make small changes to the object before inserting it to 
     * the database but don't want to change the actual $repository.
     * This $callback should return an object of a class that extends Repository, or an array of them, 
     * in which case the whole array will be inserted to the database.
     * @return QueryBuilder the QueryBuilder
     */
    public function insert(string $classname, object $object, IntoCallback $callback=null):QueryBuilder{
        $this->reset();
        $this->current_classname = $classname;
        $entity = Factory::make($classname);
        CoreEntity::_sync_entity_columns_from_props($entity,$object);
        $this->add(QueryConst::INSERT);
        $cloning = true;
        if($callback === null){
            $cloning = false;
            if($this->default_into_callback === null)
                $this->default_into_callback = new class implements IntoCallback{
                    public function run(?object $object){
                        return $object;
                    }
                };
            $callback = $this->default_into_callback;
        }
        $this->into($classname,$object,$callback,$cloning);
        return $this;
    }

    /**
     * Specify to which table the data should be inserted to
     * @return QueryBuilder the QueryBuilder
     */
    private function into(string &$classname, object $object, IntoCallback &$callback, bool $cloning=true):QueryBuilder{
        $entity_r = Factory::make($classname);
        $this->add(QueryConst::INTO);
        $this->add($entity_r->tableName());
        $this->mapColumns($entity_r->getEntityColumns(Entity::FOR_INSERT));
        if($cloning){
            $clone = clone($object);
            $results = $callback->run($clone);
        }else{
            $results = $callback->run($object);
        }
        if(\is_array($results)){
            $this->add(QueryConst::VALUES);
            $length = \count($results);
            for($i=0;$i<$length;$i++){
                if($i>0) 
                    $this->add(QueryConst::COMMA);
                $this->value($entity_r,$results[$i]);
            }
        }else{
            $this->add(QueryConst::VALUE);
            $this->value($entity_r,$results);
        }
        return $this;
    }

    /**
     * Map the column names into the query string
     * @return QueryBuilder the QueryBuilder
     */
    private function mapColumns(array $columns=[]){
        $length = count($columns);
        if($length <= 0) return $this;
        $first = true;
        $this->add(QueryConst::PARENTHESIS_LEFT);
        foreach($columns as $name => &$object){
            if($first) $first = false;
            else $this->add(QueryConst::COMMA);
            $this->add($name);
        }
        $this->add(QueryConst::PARENTHESIS_RIGHT);
        return $this;
    }

    /**
     * Add the data of $repository into the query string and into the bindings array so 
     * that the values can be bound to the statement later (before the statement execute)
     * @return QueryBuilder the QueryBuilder
     */
    private function value(CoreEntity $entity, object &$object):QueryBuilder{
        $columns = &$entity->getEntityColumns(Entity::FOR_INSERT);
        $random = '';
        $vname = '';
        $this->add(QueryConst::PARENTHESIS_LEFT);
        $first = true;
        foreach($columns as $name => &$o){
            if($first) $first = false;
            else $this->add(QueryConst::COMMA);
            $random = \uniqid().'i';
            $this->add(QueryConst::VARIABLE_SYMBOL.$name.$random);
            $type = $columns[$name]->getColumnType();
            switch($type){
                case Column::PARAM_FLOAT:
                case Column::PARAM_DOUBLE:
                case Column::PARAM_DECIMAL:
                    $type = \PDO::PARAM_STR;
                break;
            }
            $this->bind($name.$random,new Binding($columns[$name]->getColumnValue(),$type));
        }
        $this->add(QueryConst::PARENTHESIS_RIGHT);
        return $this;
    }
    
}
