<?php
namespace com\github\tncrazvan\catpaw\tools;

abstract class Http{
    public static function generateMultipartBoundary():string{
        return md5(uniqid(rand(), true));
    }
}