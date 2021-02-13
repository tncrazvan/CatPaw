<?php
namespace com\github\tncrazvan\catpaw\qb\traits;

use com\github\tncrazvan\catpaw\attributes\helpers\Factory;
use com\github\tncrazvan\catpaw\qb\tools\Binding;
use com\github\tncrazvan\catpaw\qb\tools\Column;
use com\github\tncrazvan\catpaw\qb\tools\columncallbacks\ColumnHelper;
use com\github\tncrazvan\catpaw\qb\tools\interfaces\ColumnCallback;
use com\github\tncrazvan\catpaw\qb\tools\QueryBuilder;
use com\github\tncrazvan\catpaw\qb\tools\QueryConst;

trait Clause{
    /**
     * Add a WHERE clause to the query
     * @return QueryBuilder the QueryBuilder
     */
    public function by():QueryBuilder{
        return $this->where();
    }

    /**
     * Add a WHERE clause to the query
     * @return QueryBuilder the QueryBuilder
     */
    public function where():QueryBuilder{
        $this->add(QueryConst::WHERE);
        return $this;
    }

    /**
     * Add a AND clause to the query
     * @return QueryBuilder the QueryBuilder
     */
    public function and():QueryBuilder{
        $this->add(QueryConst::AND);
        return $this;
    }

    /**
     * Add a OR clause to the query
     * @return QueryBuilder the QueryBuilder
     */
    public function or():QueryBuilder{
        $this->add(QueryConst::OR);
        return $this;
    }

    /**
     * Specify the behavior of the query when a column is encountered
     * @param string $columnName the name of the column
     * @param ColumnCallback $callback a functional interface that will be called and is expected to return an Operation, 
     * such as Like,Equal,GreaterThan,GreaterThanEqual,LesserThan,LesserThanEqual,Between.
     * This callback function will also be passed the Column as a parameter, which can be used to make new Operations.
     * Example: 
     *  return 
     *       $this
     *       ->build()
     *       ->select($this->columns)
     *       ->from($this)
     *       ->where()
     *       ->column("username",function(Column &$column){
     *           return $column->like("test_account_name");
     *       })
     *       ->run();
     * @return QueryBuilder the QueryBuilder
     */
    public function column(string $columnName, int $operationCode, ...$args):QueryBuilder{
        $entity = Factory::make($this->current_classname);

        switch($operationCode){
            case Column::EQUALS:
                $callback = ColumnHelper::equals($args[0]);
            break;
            case Column::GREATER_THAN:
                $callback = ColumnHelper::greaterThan($args[0]);
            break;
            case Column::LESSER_THAN:
                $callback = ColumnHelper::lesserThan($args[0]);
            break;
            case Column::GREATER_THAN_EQUALS:
                $callback = ColumnHelper::greaterThanEquals($args[0]);
            break;
            case Column::LESSER_THAN_EQUALS:
                $callback = ColumnHelper::lesserThanEquals($args[0]);
            break;
            case Column::BETWEEN:
                $callback = ColumnHelper::between($args[0],$args[1]);
            break;
            case Column::LIKE:
                $callback = ColumnHelper::like($args[0]);
            break;
        }

        $this->add($columnName);
        $cols = $columns = \array_merge($entity->getEntityColumns(),$entity->getEntityAliasColumns());
        $column = $cols[$columnName];

        $operation = $callback->run($column);
        $value = $operation->getValue();
        $length = count($value);
        $random = \uniqid();

        //clean "." in binding name because
        //the "." character in binding a name triggers a SQL syntax error
        $columnName = preg_replace("/\\./","",$columnName);
        if($length === 1){
            $operation->setValue(QueryConst::VARIABLE_SYMBOL.$columnName.$random);
            $this->bind($columnName.$random,new Binding($value[0]));
        }else if($length === 2){
            $operation->setValue(QueryConst::VARIABLE_SYMBOL.$columnName.'0'.$random,QueryConst::VARIABLE_SYMBOL.$columnName.'1'.$random);
            $this->bind($columnName.'0'.$random,new Binding($value[0]));
            $this->bind($columnName.'1'.$random,new Binding($value[1]));
        }
        $this->add($operation->toString());
        return $this;
    }
}
