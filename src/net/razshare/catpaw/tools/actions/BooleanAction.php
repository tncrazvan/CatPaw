<?php
namespace net\razshare\catpaw\tools\actions;

interface BooleanAction{
    public function run(mixed ...$args):bool;
}