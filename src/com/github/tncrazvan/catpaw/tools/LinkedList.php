<?php
namespace com\github\tncrazvan\catpaw\tools;

class LinkedList extends \SplDoublyLinkedList{
    /**
     * Iterate the linked list.
     * @param mode iteration mode (lookup constants).
     * @param call iteration callback.
     */
    public function iterate(int $mode,\Closure $call):void{
        $this->setIteratorMode($mode);
        for($this->rewind();$this->valid();$this->next()){
            $obj = $this->current();
            $call($obj);
        }
    }
}