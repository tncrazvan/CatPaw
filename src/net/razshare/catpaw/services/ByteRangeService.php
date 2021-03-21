<?php
namespace net\razshare\catpaw\services;

use React\EventLoop\LoopInterface;
use net\razshare\catpaw\attributes\Inject;
use net\razshare\catpaw\attributes\Service;
use net\razshare\catpaw\models\ByteRangeResponse;
use net\razshare\catpaw\tools\Status;
use net\razshare\catpaw\tools\Strings;
use React\Stream\ThroughStream;

use function React\Promise\Stream\buffer;

#[Service]
class ByteRangeService{
    private string $source;
    
    public function from(
        string $source
    ):static{
        $this->source = $source;
        return $this;
    }

    public function getSource():string{
        return $this->source;
    }
}