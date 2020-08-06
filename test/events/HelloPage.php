<?php
namespace app\homepage;

use com\github\tncrazvan\catpaw\http\HttpEvent;
use com\github\tncrazvan\catpaw\http\HttpEventInterface;
use com\github\tncrazvan\catpaw\http\HttpEventOnClose;
use com\github\tncrazvan\catpaw\tools\ServerFile;

class HelloPage implements HttpEventInterface{
    private string $test;
    private HttpEvent $e;
    public function __construct(string $test, HttpEvent $e, ?HttpEventOnClose &$onClose = null){
        $this->test = $test;
        $this->e=$e;
        if($onClose !== null)
            $onClose = new Close();
    }
    public function run(){
        echo "Received test param:$this->test\n";
        return ServerFile::response($this->e,$this->e->listener->so->webRoot,"index.html");
    }
}

class Close extends HttpEventOnClose{
    public function run():void{
        
    }
}