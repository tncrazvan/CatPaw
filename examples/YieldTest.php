<?php
namespace examples;

use net\razshare\catpaw\attributes\http\methods\GET;
use net\razshare\catpaw\attributes\http\Path;
use net\razshare\catpaw\attributes\Singleton;
use React\Promise\Promise;

#[Singleton]
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
                    
        return "This works the same as the /yield/2 endpoint except that it uses the \"yield fn()=>...\" sugar syntax$t";
    }
}