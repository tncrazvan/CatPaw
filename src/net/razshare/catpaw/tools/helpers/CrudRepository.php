<?php
namespace net\razshare\catpaw\tools\helpers;

use net\razshare\catpaw\attributes\Inject;
use net\razshare\catpaw\tools\helpers\SimpleQueryBuilder;
use net\razshare\catpaw\qb\tools\Column;
use net\razshare\catpaw\qb\tools\Page;
use net\razshare\catpaw\qb\tools\QueryBuilder;
use React\Promise\PromiseInterface;

class CrudRepository{
    private ?Page $pg = null;
    #[Inject]
    protected SimpleQueryBuilder $builder;

    public function findAll(string $classname = ''):PromiseInterface{
        $query = 
                $this
                ->builder
                ->select($this->classname);
        
        if($this->pg){
            $query->limit(...$this->pg->get());
            $this->pg = null;
        }
        
        return $classname !== ''?$query->fetchObjects($classname):$query->fetchAssoc();
    }

    private function match_pks(QueryBuilder $query, object $model):void{
        $j = 0;
        foreach($this->id as &$i){
            if($j > 0) $query->and();
            $query->column($i, Column::EQUALS, $model->$i);
            $j++;
        }
    }

    public function page(Page $page):CrudRepository{
        $this->pg = $page;
        return $this;
    }

    public function find(object $object):PromiseInterface{
        $query = 
            $this->builder
            ->select($this->classname)
            ->where();
        
        $this->match_pks($query,$object);
        

        if($this->pg){
            $query->limit(...$this->pg->get());
            $this->pg = null;
        }

        return $query->fetchObjects($this->classname)->then(fn($items)=>$items[0]??null);
    }

    public function delete(object $object):PromiseInterface{
        $query = 
        $this
            ->builder
            ->delete($this->classname)
            ->where();

        $this->match_pks($query,$object);
        
        return $query->execute(-1);
    }

    public function insert(object $object):PromiseInterface{
        return $this
            ->builder
            ->insert($this->classname, $object)
            ->execute(-1)
            ->then(fn()=>$this->builder->getDatabase()->lastInsertId());
    }
}