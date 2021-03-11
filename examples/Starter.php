<?php
namespace examples;

use net\razshare\catpaw\attributes\ApplicationScoped;
use net\razshare\catpaw\attributes\Entry;
use net\razshare\catpaw\attributes\Singleton;
use React\Promise\Promise;

#[Singleton]
#[ApplicationScoped]
class Starter{
    #[Entry]
    public function main(){
        $hello = yield new Promise(fn($r)=>$r("Hello from Promise!\n"));
        echo $hello;
    }
}