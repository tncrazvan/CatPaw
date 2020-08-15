<?php
namespace com\github\tncrazvan\catpaw\http;

class HttpConsumer{
    private ?string $value;
    private bool $hasMore = true;
    private bool $first = true;
    public function produce(?string &$value):void{
        $this->value = $value;
    }
    public function consume(?string &$value):HttpConsumer{
        if(!$this->valid()) {
            $value = '';
            return $this;
        }

        $this->first = false;
        $value = $this->value;
        $this->value = null;
        return $this;
    }
    public function valid():bool{
        return $this->hasMore;
    }
    public function done():void{
        $this->hasMore = false;
    }
    public function rewind():void{
        $this->first = true;
    }
}