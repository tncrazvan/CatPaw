<?php
error_reporting(E_ALL);
set_time_limit(0);
ob_implicit_flush();

echo "\n".shell_exec("composer dump-autoload -o");
require_once './vendor/autoload.php';

use com\github\tncrazvan\CatServer\CatServer;
use com\github\tncrazvan\CatServer\Cat;
if(!isset($argv[1]) || $argv[1] === ""){
    $argv[1] = __DIR__."/app/http.json";
}

if(file_exists($argv[1])){
    $settings = json_decode(file_get_contents($argv[1]),true);
    if(isset($settings["sleep"]))
        Cat::$sleep = $settings["sleep"];
    Cat::$web_root = dirname($argv[1])."/www/";
    if(isset($settings["webRoot"]))
        Cat::$web_root = dirname($argv[1])."/".$settings["webRoot"];
    if(isset($settings["port"]))
        Cat::$port = $settings["port"];
    if(isset($settings["timeout"]))
        Cat::$timeout = $settings["timeout"];
    if(isset($settings["charset"]))
        Cat::$charset = $settings["charset"];
    if(isset($settings["bindAddress"]))
        Cat::$bind_address = $settings["bindAddress"];
    if(isset($settings["wsMtu"]))
        Cat::$ws_mtu = $settings["wsMtu"];
    if(isset($settings["httpMtu"]))
        Cat::$http_mtu = $settings["httpMtu"];
    if(isset($settings["cacheMaxAge"]))
        Cat::$cache_max_age = $settings["cacheMaxAge"];
    if(isset($settings["entryPoint"]))
        Cat::$entry_point = $settings["entryPoint"];
    if(isset($settings["controller"])){
        if(isset($settings["controller"]["http"]))
            Cat::$http_controller_package_name = $settings["controller"]["http"];
        if(isset($settings["controller"]["ws"]))
            Cat::$ws_controller_package_name = $settings["controller"]["ws"];
        if(isset($settings["controller"]["websocket"]))
            Cat::$ws_controller_package_name = $settings["controller"]["websocket"];
    }
    if(isset($settings["controllers"])){
        if(isset($settings["controllers"]["http"]))
            Cat::$http_controller_package_name = $settings["controllers"]["http"];
        if(isset($settings["controllers"]["ws"]))
            Cat::$ws_controller_package_name = $settings["controllers"]["ws"];
        if(isset($settings["controllers"]["websocket"]))
            Cat::$ws_controller_package_name = $settings["controllers"]["websocket"];
    }
    print_r([
        "sleep"=>Cat::$sleep." microseconds",
        "port"=>Cat::$port,
        "bindAddress"=>Cat::$bind_address,
        "webRoot"=>Cat::$web_root,
        "charset"=>Cat::$charset,
        "timeout"=>Cat::$timeout." seconds",
        "wsMtu"=>Cat::$ws_mtu." bytes",
        "httpMtu"=>Cat::$http_mtu." bytes",
        "cookieTtl"=>Cat::$cookie_ttl." seconds",
        "cacheMaxAge"=>Cat::$cache_max_age." seconds",
        "entryPoint"=>Cat::$entry_point,
        "controllers"=>[
            "http"=>Cat::$http_controller_package_name,
            "websocket"=>Cat::$ws_controller_package_name,
        ]
    ]);
    $server = new CatServer(Cat::$bind_address,Cat::$port);
    $server->go_online();
}else{
    echo "File doesn't exist";
}