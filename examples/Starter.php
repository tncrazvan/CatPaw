<?php
namespace examples;

use net\razshare\catpaw\attributes\ApplicationScoped;
use net\razshare\catpaw\attributes\Entry;
use React\Promise\Promise;

#[ApplicationScoped]
class Starter{
    #[Entry]
    public function main(){
        $hello = yield new Promise(fn($r)=>$r("Hello this from Promise!\n"));
        echo $hello;
    }
}