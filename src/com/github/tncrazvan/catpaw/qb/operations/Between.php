<?php

namespace com\github\tncrazvan\catpaw\qb\operations;

//use com\github\tncrazvan\catpaw\qb\Column;
use com\github\tncrazvan\catpaw\qb\operations\Operation;


class Between extends Operation{
    public function __construct($a,$b){
        parent::__construct(self::BETWEEN,$a,$b);
    }
}
