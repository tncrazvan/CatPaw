<?php
namespace com\github\tncrazvan\CatServer\Http;

use com\github\com\tncrazvan\CatServer\Tools\G;
use com\github\tncrazvan\CatServer\Http\HttpEvent;

abstract class HttpController extends G{
    public abstract function &main(HttpEvent &$e,array &$path,string &$content);
    public abstract function onClose();
}