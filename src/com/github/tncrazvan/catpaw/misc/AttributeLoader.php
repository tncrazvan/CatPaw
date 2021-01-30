<?php
namespace com\github\tncrazvan\catpaw\misc;

use com\github\tncrazvan\catpaw\attributes\Singleton;
use com\github\tncrazvan\catpaw\tools\actions\BooleanAction;
use com\github\tncrazvan\catpaw\tools\helpers\ClassFinder;
use com\github\tncrazvan\catpaw\tools\helpers\Factory;
use com\github\tncrazvan\catpaw\tools\Strings;

#[Singleton]
class AttributeLoader{
    public function setLocation(string $location):AttributeLoader{
        $this->location = $location;
        return $this;
    }

    public function loadSome(string $namespace,BooleanAction $checkClassname):AttributeLoader{
        ClassFinder::setAppRoot($this->location);
        foreach(ClassFinder::getClassesInNamespace($namespace) as &$classname){
            if($checkClassname($classname))
                Factory::make($classname);
        }
        return $this;
    }

    public function load(string $namespace):AttributeLoader{
        ClassFinder::setAppRoot($this->location);
        foreach(ClassFinder::getClassesInNamespace($namespace) as &$classname){
            Factory::make($classname);
        }
        return $this;
    }
}