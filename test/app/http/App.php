<?php
namespace app\http;

use com\github\tncrazvan\CatPaw\Http\HttpEvent;
use com\github\tncrazvan\CatPaw\Http\HttpResponse;
use com\github\tncrazvan\CatPaw\Http\HttpController;

class App extends HttpController{
    public function main(HttpEvent &$e,array &$path,string &$content){
        return new HttpResponse([],"result");
    }
}