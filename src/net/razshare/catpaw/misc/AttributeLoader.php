<?php
namespace net\razshare\catpaw\misc;

use net\razshare\catpaw\attributes\Singleton;
use net\razshare\catpaw\tools\actions\BooleanAction;
use net\razshare\catpaw\tools\helpers\ClassFinder;
use net\razshare\catpaw\tools\helpers\Factory;

#[Singleton]
class AttributeLoader{

    private ClassFinder $finder;
    private string $location = '';
    public function __construct(){
        $this->finder = new ClassFinder();
    }
    public function setLocation(string $location):AttributeLoader{
        $this->location = $location;
        if(!str_ends_with($this->location,'/'))
            $this->location .= '/';
        return $this;
    }

    public function loadSome(string $namespace,BooleanAction $checkClassname):AttributeLoader{
        $this->finder->setAppRoot($this->location);
        $classnames = $this->finder->getClassesInNamespace($namespace);
        foreach($classnames as &$classname){
            if($checkClassname($classname))
                Factory::make($classname);
        }
        return $this;
    }

    public function load(string $namespace=''):AttributeLoader{
        $this->finder->setAppRoot($this->location);
        $classnames = $this->finder->getClassesInNamespace($namespace,fn(string &$dirname)=>$this->load("$namespace\\$dirname"));
        foreach($classnames as &$classname){
            Factory::make($classname);
        }
        return $this;
    }
}