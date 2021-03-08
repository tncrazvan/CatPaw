<?php
namespace net\razshare\catpaw\attributes;

use net\razshare\catpaw\attributes\Entry;
use net\razshare\catpaw\attributes\interfaces\AttributeInterface;
use net\razshare\catpaw\attributes\traits\CoreAttributeDefinition;
use net\razshare\catpaw\tools\helpers\Factory;

#[\Attribute]
class Filter implements AttributeInterface{
    use CoreAttributeDefinition;
    
    private array $callbacks = [];
    public function __construct(
        string ...$classnames,
    ){
        foreach ($classnames as $classname){
            try {
                $reflection_class = new \ReflectionClass($classname);
                foreach ($reflection_class->getMethods() as &$reflection_method){
                    if(Entry::findByMethod($reflection_method)){
                        $instance = Factory::make($classname);
                        $this->callbacks[$classname] = $reflection_method->getClosure($instance);
                        break;
                    }
                }
            } catch (\ReflectionException $e) {
                echo "Could not apply filter class $classname\n.";
            }
        }
    }

    public function getCallbacks():array{
        return $this->callbacks??[];
    }
}