<?php

namespace com\github\tncrazvan\catpaw\qb\tools;

class Binding{
    private $value;
    private $type;
    public function __construct($value,int $type=\PDO::PARAM_STR){
        $this->value = $value;
        $this->type = $type;
    }
    /**
     * Set a value to this Binding
     * @return void
     */
    public function setValue(&$value):void{
        $this->value=$value;
    }

    /**
     * Get the value of this Binding
     * @return &mixed value
     */
    public function &getValue(){
        return $this->value;
    }

    /**
     * Get the type of this Binding
     * @return &int value
     */
    public function &getType():int{
        return $this->type;
    }
}
