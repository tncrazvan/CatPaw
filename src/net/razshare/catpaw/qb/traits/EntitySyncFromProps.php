<?php
namespace net\razshare\catpaw\qb\traits;

use net\razshare\catpaw\qb\tools\CoreEntity;

trait EntitySyncFromProps{
    public static function _sync_entity_columns_from_props(CoreEntity $entity, object $object):void{
        $entity->reset_columns();
        $columns = $entity->getEntityColumns();
        foreach($columns as &$column){
            $name = $column->getColumnName();
            if(isset($object->$name) && $object->$name !== $column->getColumnValue()){
                $column->setColumnValue($object->$name);
            } else{
                $mgname = 'get'.\ucfirst($name);
                
                if(method_exists($object,$mgname) && ($v = $object->$mgname()) !== $column->getColumnValue()){
                    $column->setColumnValue($v);
                }else{
                    $miname = 'is'.\ucfirst($name);
                    if(method_exists($object,$miname) && ($v = $object->$miname()) !== $column->getColumnValue()){
                        $column->setColumnValue($v);
                    }
                }
            }
        }
    }
}