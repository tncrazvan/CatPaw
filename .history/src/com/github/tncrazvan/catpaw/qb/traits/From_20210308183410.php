<?php
namespace net\razshare\catpaw\qb\traits;

use net\razshare\catpaw\tools\helpers\Factory;
use net\razshare\catpaw\qb\tools\QueryBuilder;
use net\razshare\catpaw\qb\tools\QueryConst;

trait From{
    /**
     * Specify from which table the query should select/delete
     * @param Entity $entity the table to select form (usualy this is a singleton)
     * @return QueryBuilder the QueryBuilder
     */
    protected function from(string $classname, string $as = null):QueryBuilder{
        $this->current_classname = $classname;
        $entity = Factory::make($classname);
        //$repository = Repository::getRepository($classname);
        if($as === null)
            $as = $entity->tableName();
        $this->add(QueryConst::FROM);
        $name = $entity->tableName();
        $this->add($name);
        if($as !== null){
            $this->add(QueryConst::AS);
            $this->add($as);
            $columnsRef = &$entity->getEntityAliasColumns();
            $columns = $columnsRef;
            foreach($columns as $key => &$column){
                $columnsRef[$as.QueryConst::PERIOD.$key] = $column; 
            }
            //$this->alias[$as] = $name;
        }
        return $this;
    }
}
