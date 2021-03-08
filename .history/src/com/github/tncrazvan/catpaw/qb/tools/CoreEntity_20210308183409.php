<?php
namespace net\razshare\catpaw\qb\tools;

use net\razshare\catpaw\qb\tools\abstracts\EntityDefinition;
use net\razshare\catpaw\qb\traits\EntitySyncFromProps;

abstract class CoreEntity extends EntityDefinition{
    use EntitySyncFromProps;

    protected array $savedColumns = [];
    protected array $aliasColumns = [];
    
    public function __construct(){
        //static::_sync_entity_columns_from_props($this);
    }
    
    public function reset_columns(int $domain = 0):void{
        foreach($this->columns($domain) as $name => &$type){
            $this->savedColumns[$name] = new Column($name,$type,false,(\in_array($name,$this->primaryKeys()))?true:false);
            $this->config($this->savedColumns[$name]);
        }
    }

    public function config(Column $column):void{}

    /**
     * Get the columns of this entity
     * @return array an array of Column objects
     */
    public function &getEntityColumns(int $domain = 0):array{
        $columns = [];
        foreach($this->columns($domain) as $name => &$column){
            if(isset($this->savedColumns[$name]))
                $columns[$name] = $this->savedColumns[$name];
        }
        return $columns;
    }

    /**
     * Get the columns of this entity
     * @return array an array of Column objects
     */
    public function &getEntityAliasColumns(int $domain = 0):array{
        $columns = [];
        foreach($this->columns($domain) as $name => &$column){
            if(isset($this->aliasColumns[$name]))
                $columns[$name] = $this->aliasColumns[$name];
        }
        return $columns;
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