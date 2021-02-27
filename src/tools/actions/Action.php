<?php
namespace com\github\tncrazvan\catpaw\tools\actions;

interface Action{
    public function run(mixed ...$args):void;
}