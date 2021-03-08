<?php
namespace net\razshare\catpaw\qb\tools\abstracts;

abstract class EntityDefinition{
    public abstract function columns(int $domain = 0):array;
    public abstract function primaryKeys():array;
    public abstract function tableName():string;
}