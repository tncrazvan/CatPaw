<?php
use com\github\tncrazvan\CatServer\Http\HttpController;
use \com\github\tncrazvan\CatServer\Http\HttpEvent;
class App extends HttpController{
    
    public function main(HttpEvent &$e, array &$path, string &$content) {
        return file_get_contents(self::$web_root."/".self::$entry_point);
    }

    public function on_close() {}

}

