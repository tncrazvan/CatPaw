<?php
namespace com\github\tncrazvan\catpaw\http;

class HttpEventException extends \Exception{
    private string $status;
    public function __construct($message, string $status, $code = 0, \Exception $previous = null) {
        parent::__construct($message, $code, $previous);
        $this->status = $status;
    }

    public function toString():string {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }

    public function &getStatus():string {
        return $this->status;
    }

}