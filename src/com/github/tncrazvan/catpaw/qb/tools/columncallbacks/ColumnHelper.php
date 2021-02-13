<?php
namespace com\github\tncrazvan\catpaw\qb\tools\columncallbacks;

use com\github\tncrazvan\catpaw\qb\tools\Column;
use com\github\tncrazvan\catpaw\qb\tools\interfaces\ColumnCallback;

class ColumnHelper{
    public static function like (string $value):ColumnCallback{
        return new class($value) implements ColumnCallback{
            private $value;
            public function __construct(string &$value){
                $this->value = $value;
            }
            public function run(?Column $column){
                return $column->like($this->value);
            }
        };
    }
    public static function equals ($value):ColumnCallback{
        return new class($value) implements ColumnCallback{
            private $value;
            public function __construct($value){
                $this->value = $value;
            }
            public function run(?Column $column){
                return $column->equals($this->value);
            }
        };
    }
    public static function lesserThan ($value):ColumnCallback{
        return new class($value) implements ColumnCallback{
            private $value;
            public function __construct($value){
                $this->value = $value;
            }
            public function run(?Column $column){
                return $column->lt($this->value);
            }
        };
    }
    public static function greaterThan ($value):ColumnCallback{
        return new class($value) implements ColumnCallback{
            private $value;
            public function __construct($value){
                $this->value = $value;
            }
            public function run(?Column $column){
                return $column->gt($this->value);
            }
        };
    }
    public static function lesserThanEquals ($value):ColumnCallback{
        return new class($value) implements ColumnCallback{
            private $value;
            public function __construct($value){
                $this->value = $value;
            }
            public function run(?Column $column){
                return $column->lte($this->value);
            }
        };
    }
    public static function greaterThanEquals ($value):ColumnCallback{
        return new class($value) implements ColumnCallback{
            private $value;
            public function __construct($value){
                $this->value = $value;
            }
            public function run(?Column $column){
                return $column->gte($this->value);
            }
        };
    }
    public static function between ($a,$b):ColumnCallback{
        return new class($a,$b) implements ColumnCallback{
            private $a,$b;
            public function __construct($a,$b){
                $this->a = $a;
                $this->b = $b;
            }
            public function run(?Column $column){
                return $column->between($this->a,$this->b);
            }
        };
    }
}