<?php
namespace com\github\tncrazvan\catpaw\attributes;

use com\github\tncrazvan\catpaw\tools\helpers\Factory;
use com\github\tncrazvan\catpaw\qb\tools\Entity;

#[\Attribute]
class Repository extends Singleton{
    public function __construct(
        private string $entityClassName = ''
    ){}

    public function getEntityClassName():string{
        return $this->entityClassName;
    }

    private function entity():Entity{
        return Factory::make($this->entityClassName);
    }

    public function getEntityId():array{
        return $this->entity()->primaryKeys();
    }
}