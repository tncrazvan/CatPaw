<?php
namespace com\github\tncrazvan\catpaw\tools;

class Caster{
    /**
     * Cast and \stdClass object to a specific class.
     * @param $object the object to cast.
     * @param $className the name of the class you want to cast the $object as.
     * @return the newly cast object.
     */
    public static function &cast($object, string $className){
        if($object === null) return $object;
        $result = new $className();
        $props = get_object_vars($object);
        foreach($props as $key => &$value){
            $result->$key = $value;
        }
        return $result;
    }
}