<?php
namespace com\github\tncrazvan\catpaw\tools\helpers;

use React\EventLoop\LoopInterface;
use React\Promise\Promise;
use React\Promise\PromiseInterface;

class Yielder{

    private static function await(LoopInterface $loop, \Generator $value, PromiseInterface $promise,mixed $r){
        $promise->then(function($result) use(&$loop,&$r,&$value){
            $loop->futureTick(function() use(&$loop,&$r,&$value,&$result){
                if($result instanceof PromiseInterface){
                    static::await($loop,$value,$result,$r);
                }else{
                    $value->send($result);
                    $loop->futureTick(fn()=>static::yielder($loop,$value,$r));
                }
            });
        });
    }

    private static function yielder(LoopInterface $loop,\Generator $value,mixed $r):void{
        $loop->futureTick(function() use(&$loop,&$value,&$r){
            if($value->valid()){ //cycle all generators until end of callback is reached
                $item = $value->current();
                if($item instanceof PromiseInterface){
                    static::await($loop,$value,$item,$r);
                }else{
                    $value->send($item);
                    $loop->futureTick(fn()=>static::yielder($loop,$value,$r));
                }
            }else{  //end of callback is reached
                $return = $value->getReturn();
                $r($return); //http reply here
            }
        });
    }

    public static function toPromise(LoopInterface $loop, \Generator $value):Promise{
        return new Promise(function($r) use(&$loop,&$value){
            static::yielder($loop,$value,$r);
        });
    }

}