<?php
namespace examples;

use com\github\tncrazvan\catpaw\attributes\http\methods\GET;
use com\github\tncrazvan\catpaw\attributes\http\Path;
use com\github\tncrazvan\catpaw\attributes\Inject;
use com\github\tncrazvan\catpaw\tools\helpers\Yielder;
use React\EventLoop\LoopInterface;
use React\Promise\Promise;

#[Path("/yield")]
class YieldTest{
    #[GET]
    #[Path("/1")]
    public function test1():\Generator|string{
        $user = yield new Promise(function($r){
            $r(new Promise(function($r){
                $r(new Promise(function($r){
                    $r("my cool nested username");
                }));
            }));
        });
        return $user;
    }

    #[GET]
    #[Path("/2")]
    public function test2():\Generator|string{
        $result = yield function(){
            $t = yield function(){
                return yield function(){
                    echo "I'm nested!!\n";
                    return yield function(){
                        return "!!!!!";
                    };
                };
            };
            return "hello from yielding function$t";
        };
        return $result;
    }

    #[GET]
    #[Path("/3")]
    public function test3():\Generator|string{
        $t = 
            yield fn() =>
                yield fn() =>
                    yield fn() => "!!!!!";
                    
        return "This works the same as the /yield/2 endpoint except that is uses the \"yield fn()=>...\" sugar syntax$t";
    }
}