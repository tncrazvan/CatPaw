<?php

namespace com\github\tncrazvan\catpaw\qb\operations;

use com\github\tncrazvan\catpaw\qb\tools\QueryConst;


abstract class Operation implements QueryConst{
    protected $operation,$value;
    public function __construct(string $operation,...$value){
        $this->operation=$operation;
        $this->value=$value;
    }

    /**
     * Get the value of this Operation.
     * If the Operation is of type Operation::BETWEEN, the value is an 
     * array of 2 elements, $a and $b, $a being the start $offset and $b the endpoint.
     * In any other case the array contains just one element.
     * @return &array the value of the operation
     */
    public function &getValue():array{
        return $this->value;
    }

    /**
     * Set the value of this operation
     * @param mixed $value the value of the operation
     * @return void
     */
    public function setValue(...$value):void{
        $this->value=$value;
    }

    /**
     * Convert the Operation to a string
     * @return string the operation as a string
     */
    public function toString():string{
        switch($this->operation){
            case  self::BETWEEN:
                return self::BETWEEN.' '.$this->value[0].' '.self::AND.' '.$this->value[1];
            break;
            default:
                return $this->operation.' '.$this->value[0];
            break;
        }
    }
}
