<?php
namespace net\razshare\catpaw\attributes;

use net\razshare\catpaw\tools\helpers\Entity;
use net\razshare\catpaw\tools\helpers\Factory;

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