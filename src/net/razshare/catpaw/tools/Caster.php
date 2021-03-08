<?php
namespace net\razshare\catpaw\tools;

class Caster{
    /**
     * Cast an \stdClass object to a specific classname.
     * @param obj the object to cast.
     * @param className the name of the class you want to cast the $object as.
     * @return mixed the newly cast object.
     */
    public static function &cast($obj, string $className){
        if($obj === null) return $obj;
        $result = new $className();
        if(\is_array($obj)){
            foreach($obj as $key => &$value){
                $result->$key = $value;
            }
        }else{
            $props = \get_object_vars($obj);
            foreach($props as $key => &$value){
                $result->$key = $value;
            }
        }
        return $result;
    }
}