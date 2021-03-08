<?php

namespace net\razshare\catpaw\qb\traits;

use net\razshare\catpaw\tools\helpers\Factory;
use net\razshare\catpaw\qb\tools\Binding;
use net\razshare\catpaw\qb\tools\Column;
use net\razshare\catpaw\qb\tools\CoreEntity;
use net\razshare\catpaw\qb\tools\QueryBuilder;
use net\razshare\catpaw\qb\tools\QueryConst;
use net\razshare\catpaw\tools\helpers\Entity;

trait Update{
    public function update(string $classname, object $object):QueryBuilder{  
        $entity_r = Factory::make($classname);
        CoreEntity::_sync_entity_columns_from_props($entity_r,$object);
        $this->reset();
        $this->current_classname = $classname;
        $random = '';
        $this->add(QueryConst::UPDATE);
        $this->add($entity_r->tableName());
        $this->add(QueryConst::SET);
        
        $columns = \array_merge($entity_r->getEntityColumns(Entity::FOR_UPDATE),$entity_r->getEntityAliasColumns(Entity::FOR_UPDATE));
        
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
            $random = \uniqid().'u';
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
