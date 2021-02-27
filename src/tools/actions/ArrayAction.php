<?php
namespace com\github\tncrazvan\catpaw\tools\actions;

interface ArrayAction{
    public function run(mixed ...$args):array;
}