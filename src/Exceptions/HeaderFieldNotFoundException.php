<?php
namespace com\github\tncrazvan\catpaw\exception;
/**
 * Define a custom exception class
 */
class HeaderFieldNotFoundException extends \Exception
{
    // Redefine the exception so message isn't optional
    public function __construct(string $key,Exception $previous = null) {
        // some code
    
        // make sure everything is assigned properly
        parent::__construct("Header field '$key' was not found.", $code, $previous);
    }

    // custom string representation of object
    public function __toString() {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }
}