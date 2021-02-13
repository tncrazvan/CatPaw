<?php
namespace com\github\tncrazvan\catpaw\qb\traits;

use com\github\tncrazvan\catpaw\qb\tools\Entity;

trait EntitySyncFromProps{
    public static function _sync_entity_columns_from_props(Entity $entity):void{
        $entity->reset_columns();
        $columns = $entity->getEntityColumns();
        foreach($columns as &$column){
            $name = $column->getColumnName();
            if(isset($entity->$name) && $entity->$name !== $column->getColumnValue()){
                $column->setColumnValue($entity->$name);
            } else{
                $mgname = 'get'.\ucfirst($name);
                
                if(method_exists($entity,$mgname) && ($v = $entity->$mgname()) !== $column->getColumnValue()){
                    $column->setColumnValue($v);
                }else{
                    $miname = 'is'.\ucfirst($name);
                    if(method_exists($entity,$miname) && ($v = $entity->$miname()) !== $column->getColumnValue()){
                        $column->setColumnValue($v);
                    }
                }
            }
        }
    }
}