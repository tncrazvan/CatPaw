<?php
namespace com\github\tncrazvan\catpaw\tools\actions;

interface BooleanAction{
    public function run(mixed ...$args):bool;
}