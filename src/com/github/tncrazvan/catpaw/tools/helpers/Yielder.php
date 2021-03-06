<?php
namespace com\github\tncrazvan\catpaw\tools\helpers;

use React\EventLoop\LoopInterface;
use React\Promise\Promise;
use React\Promise\PromiseInterface;

class Yielder{

    private static function next(
        mixed $result,
        LoopInterface $loop,
        \Generator $value,
        \Closure $r
    ):void{
        if($result instanceof PromiseInterface){
            static::await($loop,$value,$result,$r);
        }else if($result instanceof \Generator){
            static::await($loop,$value,static::toPromise($loop,$result),$r);
        }else if($result instanceof \Closure){
            $result = $result();
            static::next($result,$loop,$value,$r);
        }else{
            $value->send($result);
            static::yielder($loop,$value,$r);
        }
    }

    private static function await(LoopInterface $loop, \Generator $value, PromiseInterface $promise, \Closure $r):void{
        $promise->then(function($result) use(&$loop,&$r,&$value){
            $loop->futureTick(function() use(&$loop,&$r,&$value,&$result){
                static::next($result,$loop,$value,$r);
            });
        });
    }


    private static function yielder(LoopInterface $loop, \Generator $value, \Closure $r):void{
        $loop->futureTick(function() use(&$loop,&$value,&$r){
            if($value->valid()){ //cycle all generators until end of callback is reached
                $result = $value->current();
                static::next($result,$loop,$value,$r);
            }else{  //end of callback is reached
                $return = $value->getReturn();
                $r($return); //final result here
            }
        });
    }

    public static function toPromise(LoopInterface $loop, \Generator $value):Promise{
        return new Promise(function($r) use(&$loop,&$value){
            static::yielder($loop,$value,$r);
        });
    }

}