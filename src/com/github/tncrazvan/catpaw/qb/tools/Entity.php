<?php
namespace com\github\tncrazvan\catpaw\qb\tools;

use com\github\tncrazvan\catpaw\qb\tools\abstracts\EntityDefinition;
use com\github\tncrazvan\catpaw\qb\traits\EntitySyncFromProps;

abstract class Entity extends EntityDefinition{
    use EntitySyncFromProps;

    private array $savedColumns = [];
    private array $aliasColumns = [];
    public function __construct(){
        static::_sync_entity_columns_from_props($this);
    }
    
    public function reset_columns():void{
        foreach($this->columns() as $name => &$type){
            $this->savedColumns[$name] = new Column($name,$type,false,(\in_array($name,$this->primaryKeys()))?true:false);
            $this->config($this->savedColumns[$name]);
        }
    }

    public function config(Column $column):void{}

    /**
     * Get the columns of this entity
     * @return array an array of Column objects
     */
    public function &getEntityColumns():array{
        return $this->savedColumns;
    }

    /**
     * Get the columns of this entity
     * @return array an array of Column objects
     */
    public function &getEntityAliasColumns():array{
        return $this->aliasColumns;
    }

    /**
     * Set an alias for the columns of this entity.<br />
     * Useful when joining multiple repositories together.
     * @param alias the alias string to apply.
     */
    public function setRepositoryColumnsAlias(string $alias):void{
        foreach($this->savedColumns as $column){
            $column->setAlias("$alias#{$column->getColumnName()} as '$alias:{$column->getColumnName()}'");
        }
    }
}