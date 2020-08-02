<?php
chdir(dirname(__FILE__));
require_once '../vendor/autoload.php';
use com\github\tncrazvan\catpaw\CatPaw;
$argv[] = "config.php";
$server = new CatPaw($argv);
$server->listen();