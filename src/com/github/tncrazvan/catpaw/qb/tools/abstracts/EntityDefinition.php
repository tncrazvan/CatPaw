<?php
namespace com\github\tncrazvan\catpaw\qb\tools\abstracts;

use com\github\tncrazvan\catpaw\qb\tools\Entity;

abstract class EntityDefinition{
    public abstract function columns():array;
    public abstract function primaryKeys():array;
    public abstract function tableName():string;
}