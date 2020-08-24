<?php
namespace com\github\tncrazvan\catpaw\tools;

abstract class Http{
    /**
     * Generate a random string fit for multipart boundaries.
     * @return string random string.
     */
    public static function generateMultipartBoundary():string{
        return md5(uniqid(rand(), true));
    }
}