<?php

namespace net\razshare\catpaw\qb\traits;

use net\razshare\catpaw\qb\tools\Binding;
use net\razshare\catpaw\qb\tools\Page;
use net\razshare\catpaw\qb\tools\QueryBuilder;
use net\razshare\catpaw\qb\tools\QueryConst;

trait Limit{
    /**
     * Set a window limit for the result.
     * @param int $offset offset of the result.
     * @param int $length maximum length of the result.
     * @return QueryBuilder the QueryBuilder
     */
    public function limit(int $offset, ?int $length = null):QueryBuilder{
        $randomo = \uniqid().'lo';
        $randoml = \uniqid().'ll';
        $name = 'lim';

        $this->add(QueryConst::LIMIT);

        $this->add(QueryConst::VARIABLE_SYMBOL.$name.$randomo);
        $this->bind($name.$randomo,new Binding($offset,\PDO::PARAM_INT));

        if($length !== null){
            $this->add(QueryConst::COMMA);
            $this->add(QueryConst::VARIABLE_SYMBOL.$name.$randoml);
            $this->bind($name.$randoml,new Binding($length,\PDO::PARAM_INT));
        }

        return $this;
    }

    /**
     * Request a page from the result.
     * @param Page $page page details.
     * @return QueryBuilder the QueryBuilder
     */
    public function page(Page $page):QueryBuilder{
        $this->limit(...$page->get());
        return $this;
    }
}
