<?php

namespace net\razshare\catpaw\qb\operations;

//use net\razshare\catpaw\qb\Column;
use net\razshare\catpaw\qb\operations\Operation;


class LesserThanEqual extends Operation{
    public function __construct($value){
        parent::__construct(self::LESSER_THAN_EQUAL,$value);
    }
}
