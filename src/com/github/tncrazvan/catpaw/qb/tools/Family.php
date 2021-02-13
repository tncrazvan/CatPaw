<?php

namespace com\github\tncrazvan\catpaw\qb\tools;

trait Family{
    public function inherit(&$object){
        self::inheritTo($object,$this);
    }

    public static function inheritTo(&$object,&$target,string $subobject=null){
        $properties = \get_object_vars($object);
        foreach($properties as $key => &$value){
            if($subobject === null){
                $target->$key = $value;
            }else{
                if(!isset($target->$$subobject)) 
                    $target->$$subobject = new \stdClass();
                    $target->$$subobject->$key = $value;
            }
        }
    }
}
