<?php

namespace com\github\tncrazvan\catpaw\qb\traits;

use com\github\tncrazvan\catpaw\tools\helpers\Factory;
use com\github\tncrazvan\catpaw\qb\tools\QueryBuilder;
use com\github\tncrazvan\catpaw\qb\tools\QueryConst;

trait Select{

    /**
     * Select Columns
     * @param string classname that represents tha requested table
     * @return QueryBuilder the QueryBuilder
     */
    public function select(string $classname):QueryBuilder{
        $entity_r = Factory::make($classname);
        $this->selectColumns(...\array_keys($entity_r->columns()));
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
