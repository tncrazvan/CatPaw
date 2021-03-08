<?php

namespace net\razshare\catpaw\qb\operations;

//use net\razshare\catpaw\qb\Column;
use net\razshare\catpaw\qb\operations\Operation;


class Between extends Operation{
    public function __construct($a,$b){
        parent::__construct(self::BETWEEN,$a,$b);
    }
}
