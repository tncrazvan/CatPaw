<?php
namespace com\github\tncrazvan\catpaw\qb\tools\interfaces;

use com\github\tncrazvan\catpaw\qb\tools\Entity;

interface IntoCallback{
    public function run(?Entity $entity);
}