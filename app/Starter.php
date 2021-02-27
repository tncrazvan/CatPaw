<?php
namespace app;

use com\github\tncrazvan\catpaw\attributes\ApplicationScoped;
use com\github\tncrazvan\catpaw\attributes\Entry;
use React\Promise\Promise;

#[ApplicationScoped]
class Starter{
    #[Entry]
    public function main(){
        $hello = yield new Promise(fn($r)=>$r("hello!!!!\n"));
        echo $hello;
    }
}