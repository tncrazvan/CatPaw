<?php
namespace com\github\tncrazvan\catpaw\tools;

class ClassTools{
    /**
     * Cast and \stdClass object to a specific class.
     * @param $object the object to cast.
     * @param $className the name of the class you want to cast the $object as.
     * @return the newly cast object.
     */
    public static function cast(\stdClass $object, string $className){
        return unserialize(sprintf(
            'O:%d:"%s"%s',
            strlen($className),
            $className,
            strstr(strstr(serialize($object), '"'), ':')
        ));
    }
}