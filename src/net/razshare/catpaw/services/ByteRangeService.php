<?php
namespace net\razshare\catpaw\services;

use net\razshare\catpaw\attributes\Service;

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