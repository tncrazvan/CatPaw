<?php
namespace com\github\tncrazvan\catpaw\tools\helpers;

use com\github\tncrazvan\catpaw\attributes\Inject;
use com\github\tncrazvan\catpaw\tools\helpers\SimpleQueryBuilder;
use com\github\tncrazvan\catpaw\qb\tools\Column;
use com\github\tncrazvan\catpaw\qb\tools\Page;
use com\github\tncrazvan\catpaw\qb\tools\QueryBuilder;
use React\Promise\Promise;

class CrudRepository{
    private ?Page $pg = null;
    #[Inject]
    protected SimpleQueryBuilder $builder;

    public function findAll():Promise{
        $query = 
                $this
                ->builder
                ->select($this->classname);
        
        if($this->pg){
            $query->limit(...$this->pg->get());
            $this->pg = null;
        }

        return $query->fetchAssoc();
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

    public function find(object $object):Promise{
        $query = 
            $this->builder
            ->select($this->classname)
            ->where();
        
        $this->match_pks($query,$object);
        

        if($this->pg){
            $query->limit(...$this->pg->get());
            $this->pg = null;
        }

        return $query->fetchObject($this->classname);
    }

    public function delete(object $object):Promise{
        $query = 
        $this
            ->builder
            ->delete($this->classname)
            ->where();

        $this->match_pks($query,$object);
        
        return $query->execute(-1);
    }

    public function insert(object $object):Promise{
        return $this
            ->builder
            ->insert($this->classname, $object)
            ->execute(-1);
    }
}