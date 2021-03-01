<?php
namespace com\github\tncrazvan\catpaw\tools\helpers;

use com\github\tncrazvan\catpaw\attributes\Inject;
use com\github\tncrazvan\catpaw\tools\helpers\SimpleQueryBuilder;
use com\github\tncrazvan\catpaw\qb\tools\Column;
use com\github\tncrazvan\catpaw\qb\tools\Page;
use React\Promise\Promise;

class CrudRepository{
    
    #[Inject]
    protected SimpleQueryBuilder $builder;

    public function findAll(?Page $page = null):Promise{
        $query = 
                $this
                ->builder
                ->select($this->classname);
        
        if($page)
            $query->limit(...$page->get());

        return $query->fetchAssoc();
    }

    public function findById(string|int|float|bool|null $id,?Page $page = null):Promise{
        $query = 
            $this->builder
            ->select($this->classname)
            ->where();

        foreach($this->id as &$i){
            $query->column($i, Column::EQUALS, $id);
        }

        if($page)
            $query->limit(...$page->get());

        return $query->fetchObject($this->classname);
    }

    public function deleteById(string|int|float|bool|null $id):void{
        $query = 
        $this
            ->builder
            ->delete($this->classname)
            ->where();

        foreach($this->id as &$i){
            $query->column($i, Column::EQUALS, $id);
        }
        
        $query->execute(-1);
    }

    public function insert(object $object):void{
        $this
            ->builder
            ->insert($this->classname, $object)
            ->execute(-1);
    }
}