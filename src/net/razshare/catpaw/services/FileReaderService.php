<?php
namespace net\razshare\catpaw\services;

use React\EventLoop\LoopInterface;
use React\Stream\ReadableResourceStream;
use net\razshare\catpaw\attributes\Inject;
use net\razshare\catpaw\attributes\Service;
use React\Promise\PromiseInterface;
use React\Stream\ReadableStreamInterface;

#[Service]
class FileReaderService{

    public function __construct(
        #[Inject] private LoopInterface $loop,
    ){}

    public function stream($stream,int $chunk_size = 65536):ReadableStreamInterface{
        return (new ReadableResourceStream($stream,$this->loop,$chunk_size));
    }

    public function read(mixed $filename,int $chunk_size = 65536):PromiseInterface{
        return \React\Promise\Stream\buffer($this->stream(\fopen($filename,'r+'),$chunk_size));
    }
}