<?php
namespace com\github\tncrazvan\catpaw\tools\helpers;

use com\github\tncrazvan\catpaw\attributes\Inject;
use com\github\tncrazvan\catpaw\tools\helpers\SimpleQueryBuilder;
use io\github\tncrazvan\orm\tools\Column;
use io\github\tncrazvan\orm\tools\Entity;

class SimpleRepository{
    
    #[Inject]
    protected SimpleQueryBuilder $builder;

    public function findAll():array{
        return $this
            ->builder
            ->select($this->classname)
            ->fetchAssoc();
    }

    public function findById(string $id):Entity{
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

    public function save(Entity $entity):void{
        $this
            ->builder
            ->insert($this->classname, $entity)
            ->execute(-1);
    }
}