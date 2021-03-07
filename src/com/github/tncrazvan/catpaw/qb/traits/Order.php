<?php
namespace com\github\tncrazvan\catpaw\qb\traits;

use com\github\tncrazvan\catpaw\qb\tools\QueryBuilder;
use com\github\tncrazvan\catpaw\qb\tools\QueryConst;

trait Order{
    /**
     * Add a ORDER BY keyword to the query
     * @return QueryBuilder the QueryBuilder
     */
    public function orderBy():QueryBuilder{
        $this->add(QueryConst::ORDER_BY);
        return $this;
    }

    /**
     * Add a DESC keyword to the query
     * @return QueryBuilder the QueryBuilder
     */
    public function desc():QueryBuilder{
        $this->add(QueryConst::DESC);
        return $this;
    }

    /**
     * Add a ASC keyword to the query
     * @return QueryBuilder the QueryBuilder
     */
    public function asc():QueryBuilder{
        $this->add(QueryConst::ASC);
        return $this;
    }
}