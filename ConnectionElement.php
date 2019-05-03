<?php

namespace com\github\tncrazvan\CatServer;

class ConnectionElement{
    public $next=null;
    private $connection=null;
    public $prev=null;
    public function __construct(&$connection) {
        $this->connection=$connection;
    }
}