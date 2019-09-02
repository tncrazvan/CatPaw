<?php
namespace com\github\tncrazvan\CatPaw\Tools;

use com\github\tncrazvan\CatPaw\Tools\G;
use com\github\tncrazvan\CatPaw\Tools\Http;
use com\github\tncrazvan\CatPaw\Tools\Strings;
use com\github\tncrazvan\AsciiTable\AsciiTable;

abstract class G extends Http{
    const DIR = __DIR__;

    public static function &init(string $settingsFile,bool $print=true):array{
        //$settings = json_decode(file_get_contents($settingsFile),true);
        $settings = include($settingsFile);
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
        if(isset($settings["header"])){
            G::$header = $settings["header"];
        }
        G::$sessionDir = preg_replace(Strings::PATTERN_DOUBLE_SLASH,"/",$settingsDir."/".G::$sessionName);
        
        
        if($print) {
            $data = [
                "port\n\n"
                ."This is the port the server will belistening to."
                    =>G::$port,
                "bindAddress\n\n"
                ."This is the address the server should bind to.\nThe address 0.0.0.0 means the server will bind to all available interfaces."
                    =>G::$bindAddress,
                "webRoot\n\n"
                ."This is the public directory your clients will be able to access."
                    =>G::$webRoot,
                "charset\n\n"
                ."This is the charset your server will be using to decode/encode data."
                    =>G::$charset,
                "timeout\n\n"
                ."Your server will timeout after this many seconds for each incoming request."
                    =>G::$timeout." seconds",
                "sessionName\n\n"
                ."Your server will save all sessions on a ramdisk.\n"
                ."This is your session ramdisk name.\n"
                ."The ramdisk is always located in the same directory as the configuration file (which is by default \"/config/http.php\")."
                    =>G::$sessionName,
                "ramSession\n\n"
                ."This is your server's session details."
                    =>G::$ramSession,
                "sessionTtl\n\n"
                ."This is the lifespan of each session (in seconds).\n"
                ."NOTE: Sessions are stored on a ramdisk, normally your "
                ."OS should not let you delete the ramdisk as long as it's "
                ."being used by the server, however, it can be forceully "
                ."unmounted and deleted."
                    =>G::$sessionTtl." seconds",
                "wsMtu\n\n"
                ."This is the maximum payload length your server will send over WebSockets."
                    =>G::$wsMtu." bytes",
                "httpMtu\n\n"
                ."This is the maximum payload length your server will send over Http."
                    =>G::$httpMtu." bytes",
                "cookieTtl\n\n"
                ."Cookies Time To Live."
                    =>G::$cookieTtl." seconds",
                "cacheMaxAge\n\n"
                ."Cache Time To Live."
                    =>G::$cacheMaxAge." seconds",
                "entryPoint\n\n"
                ."This is your server's entry point, usually \"index.html\"."
                    =>"[webRoot] ".G::$entryPoint,
                "sleep\n\n"
                ."This idicates how long the server should sleep before checking if there are any new requests (in microseconds)."
                    =>G::$sleep." microseconds",
                "backlog\n\n"
                ."A maximum of backlog incoming connections will be queued for processing. If a connection request arrives with the queue full the client may receive an error with an indication of ECONNREFUSED, or, if the underlying protocol supports retransmission, the request may be ignored so that retries may succeed."
                    =>G::$backlog." connections",
                "compress\n\n"
                ."Type of compression.\n\n"
                ."There's no fallback compression, is if no compression is specified, the server will not compress the data"
                    =>G::$compress !== null?implode(" > ",G::$compress):"DISABLED",
                "controllers\n\n"
                ."This is a mapping of your controllers Http and WebSocket controllers.\n"
                ."All your controllers should live under these two namespaces.\n"
                ."The server does not require you to setup any routing system, your client's requests will map directly to your controllers.\n"
                ."Check the documentation to read more details about the controller/request mapping system."
                    =>[
                    "http"=>G::$httpControllerPackageName,
                    "websocket"=>G::$wsControllerPackageName,
                ],
                "certificate\n\n"
                ."PEM certificate details.\n"
                ."[NOTE]: You can use /mkcert to make your own certificate, and you can find the /mkcert configuration in /config/certificate.php"
                    =>[
                    "name\n\n"=>G::$certificateName,
                    "privateKey"=>G::$certificatePrivateKey,
                    "passphrase\n\n"
                    ."[WARNING]: Don't commit this to github or any public repository!"
                        =>G::$certificatePassphrase
                ],
                "header\n\n"
                ."Extra headers to add to your HttpResponse objects.\n"
                ."[NOTE]: These can be overwritten at runtime."
                    =>G::$header
            ];
            echo Strings::tableFromArray($data,false,function(AsciiTable $table,int $lvl){
                echo "LBL: $lvl\n";
                switch($lvl){
                    case 0:
                        $table->style(0,[
                            "width"=>37
                        ]);
                        $table->style(1,[
                            "width"=>37
                        ]);
                    break;
                    case 1:
                        $table->style(0,[
                            "width"=>25
                        ]);
                        $table->style(1,[
                            "width"=>25
                        ]);
                    break;
                }
            })."\n";
        }
        return $settings;
    }

    public static
            $header = [],
            $compress = null,
            $sessionDir = "",
            $sessionName = "/_SESSION",
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
            $webRoot="/public/",
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
