<?php
namespace net\razshare\catpaw\tools\actions;

interface Action{
    public function run(mixed ...$args):void;
}