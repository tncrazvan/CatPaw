<?php
require_once './vendor/autoload.php';
require_once './App.php';

use com\github\tncrazvan\catpaw\CatPaw;
use com\github\tncrazvan\catpaw\config\MainConfiguration;
use com\github\tncrazvan\catpaw\tools\helpers\Factory;
use com\github\tncrazvan\catpaw\tools\helpers\SimpleQueryBuilder;
use React\EventLoop\LoopInterface;

Factory::setObject(LoopInterface::class,\React\EventLoop\Factory::create());

$credentials = require_once './.login/database.php';

Factory::setConstructorInjector(
    SimpleQueryBuilder::class,
    fn()=>[
        new \PDO(
            "{$credentials['driver']}:dbname={$credentials['dbname']};host={$credentials['host']}",
            $credentials['username'],
            $credentials['password']
        ), //provide database login
        Factory::make(LoopInterface::class) //provide main loop
    ]
);

Factory::make(App::class);

$server = new CatPaw(new class extends MainConfiguration{
    public function __construct() {
        $this->show_exception = true;
        $this->show_stack_trace = false;
    }
});