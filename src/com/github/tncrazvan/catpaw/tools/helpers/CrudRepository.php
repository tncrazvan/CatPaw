<?php
namespace com\github\tncrazvan\catpaw\tools\helpers;

use com\github\tncrazvan\catpaw\attributes\Inject;
use com\github\tncrazvan\catpaw\tools\helpers\SimpleQueryBuilder;
use com\github\tncrazvan\catpaw\qb\tools\Column;
use React\Promise\Promise;

class CrudRepository{
    
    #[Inject]
    protected SimpleQueryBuilder $builder;

    public function findAll():Promise{
        return $this
            ->builder
            ->select($this->classname)
            ->fetchAssoc();
    }

    public function findById(string $id):Promise{
        $b = 
        $this->builder
        ->select($this->classname)
        ->where();

        foreach($this->id as &$i){
            $b->column($i, Column::EQUALS, $id);
        }

        return $b->fetchObject($this->classname);
    }

    public function deleteById(string $id):void{
        $b = 
        $this
            ->builder
            ->delete($this->classname)
            ->where();

        foreach($this->id as &$i){
            $b->column($i, Column::EQUALS, $id);
        }

        $b->execute(-1);
    }

    public function save(object $object):void{
        $this
            ->builder
            ->insert($this->classname, $object)
            ->execute(-1);
    }
}