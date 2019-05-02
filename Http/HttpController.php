<?php
namespace com\github\tncrazvan\CatServer\Http;
use com\github\tncrazvan\CatServer\Cat;

abstract class HttpController extends Cat{
    public abstract function &main(HttpEvent &$e,array &$path,string &$content);
    public abstract function onClose();
}