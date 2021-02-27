<?php

namespace com\github\tncrazvan\catpaw\qb\operations;

//use com\github\tncrazvan\catpaw\qb\Column;
use com\github\tncrazvan\catpaw\qb\operations\Operation;


class GreaterThanEqual extends Operation{
    public function __construct($value){
        parent::__construct(self::GREATER_THAN_EQUALE,$value);
    }
}
