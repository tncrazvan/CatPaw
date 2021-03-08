<?php

namespace net\razshare\catpaw\qb\traits;

use net\razshare\catpaw\qb\tools\QueryBuilder;
use net\razshare\catpaw\qb\tools\QueryConst;

trait Delete{
    private $table = null;
    /**
     * Select Columns
     * @param array $columns an array of Column objects
     * @return QueryBuilder the QueryBuilder
     */
    public function delete(string $classname):QueryBuilder{
        $this->reset();
        $this->add(QueryConst::DELETE);
        $this->from($classname);
        return $this;
    }
}
