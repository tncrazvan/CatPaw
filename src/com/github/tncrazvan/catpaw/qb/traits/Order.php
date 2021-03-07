<?php
namespace com\github\tncrazvan\catpaw\qb\traits;

use com\github\tncrazvan\catpaw\qb\tools\QueryBuilder;
use com\github\tncrazvan\catpaw\qb\tools\QueryConst;

trait Order{
    /**
     * Add a ORDER BY keyword to the query
     * @param $name name to order by (column or alias)
     * @param $order if greater than 0 will force order by "asc", if lesser than 0 will force order by "desc".
     * @return QueryBuilder the QueryBuilder
     */
    protected int $ordered = 0;
    public function orderBy(string $name, int $order = 0):QueryBuilder{
        if($this->ordered === 0){
            $this->add(QueryConst::ORDER_BY);
        }else{
            $this->add(QueryConst::COMMA);
        }
        $this->add($name);
        if($order > 0)
            $this->add(QueryConst::ASC);
        if($order < 0)
            $this->add(QueryConst::DESC);
        $this->ordered++;
        return $this;
    }
}