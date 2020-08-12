<?php
namespace com\github\tncrazvan\catpaw\tools;

class LinkedList extends \SplDoublyLinkedList{
    public function iterate($mode,\Closure $callback):void{
        $this->setIteratorMode($mode);
        for($this->rewind();$this->valid();$this->next()){
            $obj = $this->current();
            $callback($obj);
        }
    }
}