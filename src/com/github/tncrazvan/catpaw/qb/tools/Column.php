<?php

namespace com\github\tncrazvan\catpaw\qb\tools;

use com\github\tncrazvan\catpaw\qb\operations\Like;
use com\github\tncrazvan\catpaw\qb\operations\Equals;
use com\github\tncrazvan\catpaw\qb\operations\Between;
use com\github\tncrazvan\catpaw\qb\operations\LesserThan;
use com\github\tncrazvan\catpaw\qb\operations\GreaterThan;
use com\github\tncrazvan\catpaw\qb\operations\GreaterThanEqual;
use com\github\tncrazvan\catpaw\qb\operations\LesserThanEqual;

class Column{
    public const EQUALS = 0;
    public const GREATER_THAN = 1;
    public const LESSER_THAN = 2;
    public const GREATER_THAN_EQUALS = 3;
    public const LESSER_THAN_EQUALS = 4;
    public const BETWEEN = 5;
    public const LIKE = 6;
    public const PARAM_STR = \PDO::PARAM_STR;
    public const PARAM_INT = \PDO::PARAM_INT;
    public const PARAM_BOOL = \PDO::PARAM_BOOL;
    public const PARAM_DOUBLE = 777777700;
    public const PARAM_FLOAT = 777777701;
    public const PARAM_DECIMAL = 777777702;

    private bool $changed = false;

    //Class vars
    protected string $name;
    protected string $alias;
    protected int $type;
    protected $value;
    protected bool $nullable;
    protected array $size;

    //Keys
    protected bool $pk=false;
    protected ?CoreEntity $fk=null;

    public function __construct(string $name, int $type=\PDO::PARAM_STR, bool $nullable = false, bool $pk=false, ?CoreEntity $fk=null, array $size = [0,0]){
        $this->name = $name;
        $this->type = $type;
        $this->nullable = $nullable;
        $this->size = $size;
        if($pk) $this->setPrimaryKey();
        if($fk !== null) $this->setForeignKey($fk);
    }

    public function setSize(int $digits, int $decimals = 0):void{
        $this->size = [$digits,$decimals];
    }

    public function getSize():array{
        return $this->size;
    }

    public function setNullable(bool $nullable):void{
        $this->nullable = $nullable;
    }

    public function isNullable():bool{
        return $this->nullable;
    }

    public function hasBeenChanged():bool{
        return $this->changed;
    }

    public function fakeChange():void{
        $this->changed = true;
    }

    public function setAlias(string $alias){
        $this->alias = $alias;
    }

    public function getAlias():string{
        return $this->alias;
    }

    public function hasAlias():bool{
        return isset($this->alias);
    }

    //Columns methods
    /**
     * Get the value of this column
     * @return &mixed the value of the collumn
     */
    public function &getColumnValue(){
        return $this->value;
    }

    /**
     * Set the value of this column
     * @param mixed $value the value to be set
     */
    public function setColumnValue($value):void{
        $this->value = $value;
        $this->changed = true;
    }

    /**
     * Get the name of this column
     * @return &string the name of the column
     */
    public function &getColumnName():string{
        return $this->name;
    }

    /**
     * Get the type of this column
     * @return &int the type of the column
     */
    public function &getColumnType():int{
        return $this->type;
    }


    //Primary Key methods
    /**
     * Set this column as primary key (does not affect the database)
     * @return void
     */
    public function setPrimaryKey():void{
        $this->pk = true;
    }
    /**
     * Unset this column as primary key (does not affect the database)
     * @return void
     */
    public function unsetPrimaryKey():void{
        $this->pk = false;
    }
    /**
     * Check if this column is a primary key
     * @return bool true if it's a primary key, false otherwise
     */
    public function isPrimaryKey():bool{
        return $this->pk;
    }

    //Foreign Key methods
    /**
     * Set the pointer of a Entity as foreign key of this column (does not affect database)
     * @param Entity &$repository the pointer of a table to be set as a foreign key
     * @return void
     */
    public function setForeignKey(CoreEntity &$entity):void{
        $this->fk = $entity;
    }
    /**
     * Unset the foreign key of this column (does not affect database)
     * @return void
     */
    public function unsetForeignKey():void{
        $this->fk = null;
    }
    /**
     * Check if this column is a foreign key
     * @return boold true if it's a foreign key, false otherwise
     */
    public function isForeignKey():bool{
        return $this->fk !== null;
    }


    //Operations methods
    /**
     * Make a new Like operation.
     * @param $value the Column should be Like this value
     * @return Like the operation itself.
     */
    public function &like($value):Like{
        $operation = new Like($value);
        return $operation;
    }
    /**
     * Make a new Equals operation.
     * @param $value the Column should be Equal to this value
     * @return Equals the operation itself.
     */
    public function &equals($value):Equals{
        $operation = new Equals($value);
        return $operation;
    }
    /**
     * Make a new GreaterThan operation.
     * @param $value the Column should be GreaterThan this value
     * @return GreaterThan the operation itself.
     */
    public function &gt($value):GreaterThan{
        $operation = new GreaterThan($value);
        return $operation;
    }
    /**
     * Make a new GreaterThanEqual operation.
     * @param $value the Column should be GreaterThan or Equal to this value
     * @return GreaterThanEqual the operation itself.
     */
    public function &gte($value):GreaterThanEqual{
        $operation = new GreaterThanEqual($value);
        return $operation;
    }
    /**
     * Make a new LesserThan operation.
     * @param $value the Column should be LesserThan this value
     * @return LesserThan the operation itself.
     */
    public function &lt($value):LesserThan{
        $operation = new LesserThan($value);
        return $operation;
    }
    /**
     * Make a new LesserThanEqual operation
     * @param $value the Column should be LesserThan or Equal to this value
     * @return LesserThanEqual the operation itself.
     */
    public function &lte($value):LesserThanEqual{
        $operation = new LesserThanEqual($value);
        return $operation;
    }
    /**
     * Make a new Between operation. This Column should be between $a and $b
     * @param $a this is the offset of the operation
     * @param $b this is the endpoint of the operation
     * @return Between the operation itself.
     */
    public function &between($a,$b):Between{
        $operation = new Between($a,$b);
        return $operation;
    }
}
