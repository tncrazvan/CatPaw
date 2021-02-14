<?php
namespace com\github\tncrazvan\catpaw\services;

use React\EventLoop\LoopInterface;
use React\Stream\ReadableResourceStream;
use com\github\tncrazvan\catpaw\attributes\Inject;
use com\github\tncrazvan\catpaw\attributes\Service;
use React\Promise\PromiseInterface;

#[Service]
class FileReaderService{

    public function __construct(
        #[Inject] private LoopInterface $loop,
    ){}

    public function read(string $filename,int $chunk_size = 65536):PromiseInterface{
        $stream = (new ReadableResourceStream(\fopen($filename,'r'),$this->loop,$chunk_size));
        return \React\Promise\Stream\buffer($stream);
    }
}