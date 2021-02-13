<?php

namespace com\github\tncrazvan\catpaw\qb\traits;

use com\github\tncrazvan\catpaw\attributes\helpers\Factory;
use com\github\tncrazvan\catpaw\qb\tools\Binding;
use com\github\tncrazvan\catpaw\qb\tools\Column;
use com\github\tncrazvan\catpaw\qb\tools\Entity;
use com\github\tncrazvan\catpaw\qb\tools\QueryBuilder;
use com\github\tncrazvan\catpaw\qb\tools\QueryConst;

trait Update{
    public function update(string $classname, Entity $entity):QueryBuilder{
        Entity::_sync_entity_columns_from_props($entity);
        $this->current_classname = $classname;
        $entity_r = Factory::make($classname);
        $this->reset();
        $random = '';
        $this->add(QueryConst::UPDATE);
        $this->add($entity_r->tableName());
        $this->add(QueryConst::SET);
        
        $columns = \array_merge($entity->getEntityColumns(),$entity->getEntityAliasColumns());
        
        //$length = \count($columns);

        $firstColumn = true;
        foreach($columns as $name => &$column){
            if(!$column->hasBeenChanged()) continue;
            if($firstColumn){
                $firstColumn = false;
            }else{
                $this->add(QueryConst::COMMA);
            }
            $this->add($name);
            $this->add(QueryConst::EQUALS);
            $random = \uniqid();
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
        return $this;
    }
}
