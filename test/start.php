<?php
require_once __DIR__.'/../vendor/autoload.php';
use com\github\tncrazvan\catpaw\CatPaw;
if(count($argv) === 1) $argv[1] = __DIR__."/config/http.php";

error_reporting(E_ALL);
set_time_limit(0);
ob_implicit_flush();

$server = new CatPaw($argv[1],function(&$context){
    
});