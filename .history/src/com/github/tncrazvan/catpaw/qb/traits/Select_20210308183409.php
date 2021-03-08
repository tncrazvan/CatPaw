<?php

namespace net\razshare\catpaw\qb\traits;

use net\razshare\catpaw\tools\helpers\Factory;
use net\razshare\catpaw\qb\tools\QueryBuilder;
use net\razshare\catpaw\qb\tools\QueryConst;
use net\razshare\catpaw\tools\helpers\Entity;

trait Select{

    /**
     * Select Columns
     * @param string classname that represents tha requested table
     * @return QueryBuilder the QueryBuilder
     */
    public function select(string $classname):QueryBuilder{
        $entity_r = Factory::make($classname);
        $this->selectColumns(...\array_keys($entity_r->columns(Entity::FOR_SELECT)));
        $this->from($classname);
        return $this;
    }

    /**
     * Select Columns
     * @param array $columns an array of Column objects
     * @return QueryBuilder the QueryBuilder
     */
    private function selectColumns(...$columns):QueryBuilder{
        $this->reset();

        if(\count($columns) === 0)
            $columns = ["*"];

        $this->add(QueryConst::SELECT);
        //$length = \count($columns);
        $firstColumn = true;
        foreach($columns as &$column){
            if($firstColumn){
                $firstColumn = false;
            }else{
                $this->add(QueryConst::COMMA);
            }

            $this->add($column);
        }
        return $this;
    }
}
