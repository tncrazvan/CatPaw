<?php

namespace com\github\tncrazvan\catpaw\qb\traits;

use com\github\tncrazvan\catpaw\qb\tools\QueryBuilder;
use com\github\tncrazvan\catpaw\qb\tools\QueryConst;

trait Alias{
    /**
     * Set an alias
     * @param string $alias the name of the new alias
     * @return QueryBuilder the QueryBuilder
     */
    public function as(string $alias):QueryBuilder{
        $this->add(QueryConst::AS);
        $this->add($alias);
        return $this;
    }
}
