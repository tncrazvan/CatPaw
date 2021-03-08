<?php
namespace net\razshare\catpaw\tools\helpers;

use net\razshare\catpaw\attributes\interfaces\AttributeInterface;
use net\razshare\catpaw\attributes\traits\CoreAttributeDefinition;
use net\razshare\catpaw\qb\tools\Column;
use net\razshare\catpaw\qb\tools\CoreEntity;

#[\Attribute]
class Entity extends CoreEntity implements AttributeInterface{
    use CoreAttributeDefinition;
    private array $columns = [];
    private array $columnsForInsert = [];
    private array $columnsForUpdate = [];
    private array $columnsForSelect = [];
    private array $primaryKeys = [];
    private string $tableName = '';
    public const ALL = 0;
    public const FOR_INSERT = 1;
    public const FOR_UPDATE = 2;
    public const FOR_SELECT = 3;

    public function __construct(string $tableName = '') {
        $this->tableName = $tableName;
    }

    public function columns(int $domain = 0):array{
        switch($domain){
            case static::FOR_INSERT:
                return $this->columnsForInsert;
            case static::FOR_UPDATE:
                return $this->columnsForUpdate;
            case static::FOR_SELECT:
                return $this->columnsForSelect;
        }
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
    public function setColumnsForInsert(array $columns):void{
        $this->columnsForInsert = $columns;
    }
    public function setColumnsForUpdate(array $columns):void{
        $this->columnsForUpdate = $columns;
    }
    public function setColumnsForSelect(array $columns):void{
        $this->columnsForSelect = $columns;
    }

    public function setPrimaryKeys(array $primaryKeys):void{
        $this->primaryKeys = $primaryKeys;
    }

    public function setTableName(string $tableName):void{
        $this->tableName = $tableName;
    }

    public function ignoreColumnForDomain(string $colName, int $domain):void{
        switch($domain){
            case static::FOR_INSERT:
                unset($this->columnsForInsert[$colName]);
            break;
            case static::FOR_UPDATE:
                unset($this->columnsForInsert[$colName]);
            break;
        }
    }
}