<?php
namespace com\github\tncrazvan\catpaw\http;
abstract class HttpEventOnClose{
    public abstract function run():void;
}