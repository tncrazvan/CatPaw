<?php
namespace com\github\tncrazvan\catpaw\tools\helpers;

use com\github\tncrazvan\catpaw\qb\tools\Entity;

class SimpleEntity extends Entity{
    private array $columns = [];
    private array $primaryKeys = [];
    private string $tableName = '';

    public function columns():array{
        return $this->columns;
    }
    public function primaryKeys():array{
        return $this->primaryKeys;
    }
    public function tableName():string{
        return $this->tableName;
    }

    public function setColumns(array $columns):void{
        $this->columns = $columns;
    }

    public function setPrimaryKeys(array $primaryKeys):void{
        $this->primaryKeys = $primaryKeys;
    }

    public function setTableName(string $tableName):void{
        $this->tableName = $tableName;
    }
}