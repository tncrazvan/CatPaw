<?php
namespace com\github\tncrazvan\catpaw\app\http;

use com\github\tncrazvan\catpaw\http\HttpEvent;
use com\github\tncrazvan\catpaw\http\HttpResponse;
use com\github\tncrazvan\catpaw\http\HttpController;

class App extends HttpController{
    public function main(HttpEvent &$e,array &$path,string &$content){
        return new HttpResponse([],"result");
    }
}