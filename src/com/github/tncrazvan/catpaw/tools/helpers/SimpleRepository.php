<?php
namespace com\github\tncrazvan\catpaw\tools\helpers;

use com\github\tncrazvan\catpaw\attributes\Extend;

#[Extend(SimpleRepositoryExtension::class)]
interface SimpleRepository{
    public function findAll():array;
}