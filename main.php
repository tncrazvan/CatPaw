<?php
require_once './vendor/autoload.php';

use com\github\tncrazvan\catpaw\attributes\Entry;
use com\github\tncrazvan\catpaw\attributes\Inject;
use com\github\tncrazvan\catpaw\attributes\Produces;
use com\github\tncrazvan\catpaw\attributes\Singleton;
use com\github\tncrazvan\catpaw\CatPaw;
use com\github\tncrazvan\catpaw\config\MainConfiguration;
use com\github\tncrazvan\catpaw\tools\helpers\Factory;
use com\github\tncrazvan\catpaw\tools\helpers\Route;
use React\EventLoop\Factory as EventLoopFactory;
use React\EventLoop\LoopInterface;
use React\Promise\Promise;

Singleton::$map[LoopInterface::class] = \React\EventLoop\Factory::create();

#[Singleton]
class App{

    #[Entry]
    public function main(
        #[Inject] LoopInterface $loop
    ):void{
        $loop->addTimer(1,fn($r)=>$r("test test\n"));
    }
}

Factory::make(App::class);

Route::get("/asd", #[Produces("text/plain")] function(
    #[Inject] LoopInterface $loop
){
    return new Promise(fn($resolve)=>$loop->addTimer(5,fn()=>$resolve("hello")));
});

$server = new CatPaw(new class extends MainConfiguration{
    public function __construct() {
        $this->show_exception = true;
        $this->show_stack_trace = false;
    }
});