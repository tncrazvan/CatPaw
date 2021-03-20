<?php
namespace examples;

use Exception;
use Generator;
use net\razshare\catpaw\attributes\http\methods\GET;
use net\razshare\catpaw\attributes\http\Path;
use net\razshare\catpaw\attributes\http\PathParam;
use net\razshare\catpaw\attributes\http\Query;
use net\razshare\catpaw\attributes\Inject;
use React\ChildProcess\Process;
use React\EventLoop\LoopInterface;
use React\Promise\Promise;

#[Path("/child-process")]
class ProcessTest{

    #[GET]
    #[Path("/{message}")]
    public function start(
        #[Inject] LoopInterface $loop,
        #[PathParam] string $message = 'hello from child',
        #[Query] int $sleep = 3
    ):Generator|string{
        $message = yield new Promise(function($r) use(&$loop,&$message,&$sleep){
            $dir = dirname(__FILE__).'/files';
            if(!is_dir($dir) && !is_file($dir))
                mkdir($dir,0777,true);

            $process = new Process("sleep $sleep && echo $message");
            $process->start($loop);
            $message = '';
            $process->stdout->on('data', function ($chunk) use(&$message){
                $message .=$chunk;
            });
            $process->on("exit",function() use(&$r,&$message){
                $r($message);
            });
            $process->stdout->on('error', function (Exception $e) use(&$r){
                $r($e);
            });
        });
        return $message;
    }
}