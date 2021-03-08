<?php

namespace net\razshare\catpaw\qb\operations;

//use net\razshare\catpaw\qb\Column;
use net\razshare\catpaw\qb\operations\Operation;

class Like extends Operation{
    public function __construct($value){
        parent::__construct(self::LIKE,$value);
    }
}
