<?php
namespace com\github\tncrazvan\catpaw\qb\tools\interfaces;

use com\github\tncrazvan\catpaw\qb\tools\Column;

interface ColumnCallback{
    public function run(?Column $column);
}