<?php
namespace com\github\tncrazvan\CatPaw\Tools;

use com\github\tncrazvan\CatPaw\Tools\G;
use com\github\tncrazvan\CatPaw\Tools\Http;
use com\github\tncrazvan\CatPaw\Tools\Strings;

abstract class G extends Http{
    const DIR = __DIR__;

    public static function &init(string $settingsFile,bool $print=true):array{
        $settings = json_decode(file_get_contents($settingsFile),true);
        $settingsDir = dirname($settingsFile);
        if(isset($settings["compress"]))
        G::$compress = $settings["compress"];
        if(isset($settings["sleep"]))
        G::$sleep = $settings["sleep"];
        G::$webRoot = preg_replace(Strings::PATTERN_DOUBLE_SLASH,"/",$settingsDir."/src/");
        if(isset($settings["webRoot"]))
        G::$webRoot = preg_replace(Strings::PATTERN_DOUBLE_SLASH,"/",$settingsDir."/".$settings["webRoot"]."/");
        if(isset($settings["port"]))
        G::$port = $settings["port"];
        if(isset($settings["timeout"]))
        G::$timeout = $settings["timeout"];
        if(isset($settings["ramSession"])){
            if(isset($settings["ramSession"]["allow"]))
            G::$ramSession["allow"] = $settings["ramSession"]["allow"];
            if(isset($settings["ramSession"]["size"]))
            G::$ramSession["size"] = $settings["ramSession"]["size"];
        }
        if(isset($settings["sessionTtl"]))
        G::$sessionTtl = $settings["sessionTtl"];
        if(isset($settings["charset"]))
        G::$charset = $settings["charset"];
        if(isset($settings["bindAddress"]))
        G::$bindAddress = $settings["bindAddress"];
        if(isset($settings["wsMtu"]))
        G::$wsMtu = $settings["wsMtu"];
        if(isset($settings["httpMtu"]))
        G::$httpMtu = $settings["httpMtu"];
        if(isset($settings["cacheMaxAge"]))
        G::$cacheMaxAge = $settings["cacheMaxAge"];
        if(isset($settings["entryPoint"]))
        G::$entryPoint = $settings["entryPoint"];
        if(isset($settings["sessionName"]))
        G::$sessionName = $settings["sessionName"];
        if(isset($settings["controller"])){
            if(isset($settings["controller"]["http"]))
            G::$httpControllerPackageName = $settings["controller"]["http"];
            if(isset($settings["controller"]["ws"]))
            G::$wsControllerPackageName = $settings["controller"]["ws"];
            if(isset($settings["controller"]["websocket"]))
            G::$wsControllerPackageName = $settings["controller"]["websocket"];
        }
        if(isset($settings["controllers"])){
            if(isset($settings["controllers"]["http"]))
            G::$httpControllerPackageName = $settings["controllers"]["http"];
            if(isset($settings["controllers"]["ws"]))
            G::$wsControllerPackageName = $settings["controllers"]["ws"];
            if(isset($settings["controllers"]["websocket"]))
            G::$wsControllerPackageName = $settings["controllers"]["websocket"];
        }
        if(isset($settings["certificate"])){
            if(isset($settings["certificate"]["name"]))
            G::$certificateName = preg_replace(Strings::PATTERN_DOUBLE_SLASH,"/",$settingsDir."/".$settings["certificate"]["name"]);
            if(isset($settings["certificate"]["privateKey"]))
            G::$certificatePrivateKey = preg_replace(Strings::PATTERN_DOUBLE_SLASH,"/",$settingsDir."/".$settings["certificate"]["privateKey"]);
            if(isset($settings["certificate"]["password"]))
            G::$certificatePassphrase = $settings["certificate"]["password"];
            if(isset($settings["certificate"]["passphrase"]))
            G::$certificatePassphrase = $settings["certificate"]["passphrase"];
        }
        G::$sessionDir = preg_replace(Strings::PATTERN_DOUBLE_SLASH,"/",$settingsDir."/".G::$sessionName);
        
        if($print) print_r([
            "port"=>G::$port,
            "bindAddress"=>G::$bindAddress,
            "webRoot"=>G::$webRoot,
            "charset"=>G::$charset,
            "timeout"=>G::$timeout." seconds",
            "sessionTtl"=>G::$sessionTtl." seconds",
            "ramSession"=>G::$ramSession,
            "wsMtu"=>G::$wsMtu." bytes",
            "httpMtu"=>G::$httpMtu." bytes",
            "cookieTtl"=>G::$cookieTtl." seconds",
            "cacheMaxAge"=>G::$cacheMaxAge." seconds",
            "entryPoint"=>"[webRoot] ".G::$entryPoint,
            "sleep"=>G::$sleep." microseconds",
            "backlog"=>G::$backlog." connections",
            "compress"=>G::$compress !== null?implode(" > ",G::$compress):"DISABLED",
            "controllers"=>[
                "http"=>G::$httpControllerPackageName,
                "websocket"=>G::$wsControllerPackageName,
            ],
            "certificate"=>[
                "name"=>G::$certificateName,
                "privateKey"=>G::$certificatePrivateKey,
                "passphrase"=>G::$certificatePassphrase
            ]
        ]);
        return $settings;
    }

    public static
            $compress = null,
            $sessionDir = "",
            $sessionName = "/SESSION",
            $certificateName = "",
            $certificateType = "",
            $certificatePrivateKey = "",
            $certificatePassphrase = "",
            $ramSession = [
                "allow"=>true,
                "size"=>1024 // 1024 MB
            ],
            $sessionTtl = 1440, // 24 minutes
            $sleep = 10, //microseconds
            $listen=true,
            $groupsAllowed=false,
            $smtpAllowed=false,
            $backlog=50,
            $port=80,
            $timeout=3000,
            $webRoot="/src/",
            $charset="UTF-8",
            $bindAddress="127.0.0.1",
            
            $httpControllerPackageNameOriginal="com\\github\\tncrazvan\\CatPaw\\Controller\\Http",
            $httpControllerPackageName="com\\github\\tncrazvan\\CatPaw\\Controller\\Http",
            
            $wsControllerPackageNameOriginal="com\\github\\tncrazvan\\CatPaw\\Controller\\WebSocket",
            $wsControllerPackageName="com\\github\\tncrazvan\\CatPaw\\Controller\\WebSocket",
            
            $httpNotFoundNameOriginal="ControllerNotFound",
            $httpNotFoundName="ControllerNotFound",
            
            $wsNotFoundNameOriginal="ControllerNotFound",
            $wsNotFoundName="ControllerNotFound",
            
            $httpDefaultNameOriginal="App",
            $httpDefaultName="App",

            $wsEvents,
            $cookieTtl=60*60*24*365, //year
            $wsGroupMaxClient=10,
            $wsMtu=65536,
            $httpMtu=65536,
            $cacheMaxAge=60*60*24*365, //year
            $entryPoint="/index.html",
            $wsAcceptKey = "258EAFA5-E914-47DA-95CA-C5AB0DC85B11",
            $mainSettings,
            $running=true;
}