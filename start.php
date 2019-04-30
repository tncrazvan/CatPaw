<?php
error_reporting(E_ALL);
set_time_limit(0);
ob_implicit_flush();

echo "\n".shell_exec("composer dump-autoload -o");
require_once './vendor/autoload.php';

use com\github\tncrazvan\CatServer\CatServer;
if(count($argv) === 1) $argv[1] = __DIR__."/http.json";
$server = new CatServer($argv);
$server->go_online();