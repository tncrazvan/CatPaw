<?php
require_once './vendor/autoload.php';
require_once './App.php';

use com\github\tncrazvan\catpaw\CatPaw;
use com\github\tncrazvan\catpaw\config\MainConfiguration;
use com\github\tncrazvan\catpaw\tools\helpers\Factory;
use React\EventLoop\LoopInterface;

Factory::setObject(LoopInterface::class,\React\EventLoop\Factory::create());


Factory::make(App::class);

$server = new CatPaw(new class extends MainConfiguration{
    public function __construct() {
        $this->show_exception = true;
        $this->show_stack_trace = false;
    }
});