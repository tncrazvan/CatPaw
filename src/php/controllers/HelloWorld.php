<?php
namespace App;
use com\github\tncrazvan\CatServer\Http\HttpController;
use com\github\tncrazvan\CatServer\Http\HttpEvent;
class HelloWorld extends HttpController{
    
    public function main(HttpEvent &$e, array &$path, string &$content) {
        return "hello world!";
    }

    public function on_close() {
        //close your database handlers here
    }

}
