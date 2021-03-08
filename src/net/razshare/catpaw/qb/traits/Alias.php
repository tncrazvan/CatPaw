<?php

namespace net\razshare\catpaw\qb\traits;

use net\razshare\catpaw\qb\tools\QueryBuilder;
use net\razshare\catpaw\qb\tools\QueryConst;

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
