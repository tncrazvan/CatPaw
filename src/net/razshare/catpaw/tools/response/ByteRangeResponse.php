<?php
namespace net\razshare\catpaw\tools\response;

use net\razshare\catpaw\attributes\Service;

#[Service]
class ByteRangeResponse{
    private function __construct(
        private string $source
    ){}

    public static function from(
        string $source
    ):static{
        return new static($source);
    }

    public function getSource():string{
        return $this->source;
    }
}