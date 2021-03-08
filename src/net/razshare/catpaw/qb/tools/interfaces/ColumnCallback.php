<?php
namespace net\razshare\catpaw\qb\tools\interfaces;

use net\razshare\catpaw\qb\tools\Column;

interface ColumnCallback{
    public function run(?Column $column);
}