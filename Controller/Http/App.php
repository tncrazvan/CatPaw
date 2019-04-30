<?php
namespace com\github\tncrazvan\CatServer\Controller\Http;

use com\github\tncrazvan\CatServer\Http\HttpController;
use com\github\tncrazvan\CatServer\Http\HttpEvent;
use com\github\tncrazvan\CatServer\Cat;

class App extends HttpController{
    
    public function main(HttpEvent &$e, array &$path, string &$content) {
        $e->send_file_contents(self::$entry_point);
    }

    public function on_close() {}

}