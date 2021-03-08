<?php
namespace net\razshare\catpaw\tools\actions;

interface ArrayAction{
    public function run(mixed ...$args):array;
}