<?php
namespace com\github\tncrazvan\CatPaw\Tools;

class ListNode
{
    /* Data to hold */
    public $data;
 
    /* Link to next node */
    public $next;
 
 
    /* Node constructor */
    function __construct($data)
    {
        $this->data = $data;
        $this->next = NULL;
    }
 
    function readNode()
    {
        return $this->data;
    }
}