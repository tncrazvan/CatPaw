<?php
namespace com\github\tncrazvan\catpaw\tools\helpers;

use com\github\tncrazvan\catpaw\attributes\interfaces\AttributeInterface;
use com\github\tncrazvan\catpaw\attributes\traits\CoreAttributeDefinition;
use com\github\tncrazvan\catpaw\qb\tools\CoreEntity;

#[\Attribute]
class Entity extends CoreEntity implements AttributeInterface{
    use CoreAttributeDefinition;
    private array $columns = [];
    private array $primaryKeys = [];
    private string $tableName = '';

    public function __construct(string $tableName = '') {
        $this->tableName = $tableName;
    }

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