<?php
namespace com\github\tncrazvan\catpaw\tools;

use com\github\tncrazvan\catpaw\tools\Http;
use com\github\tncrazvan\catpaw\tools\Strings;
use com\github\tncrazvan\asciitable\AsciiTable;

abstract class Server extends Http{
    const DIR = __DIR__;

    public static function &init(string $settingsFile,bool $print=true):array{
        //$settings = json_decode(file_get_contents($settingsFile),true);
        $storage = new \stdClass();
        $settings = include($settingsFile);
        $settingsDir = dirname($settingsFile);
        if(isset($settings["compress"]))
        Server::$compress = $settings["compress"];
        if(isset($settings["sleep"]))
        Server::$sleep = $settings["sleep"];
        Server::$webRoot = preg_replace(Strings::PATTERN_DOUBLE_SLASH,"/",$settingsDir."/www/");
        if(isset($settings["webRoot"]))
        Server::$webRoot = preg_replace(Strings::PATTERN_DOUBLE_SLASH,"/",$settingsDir."/".$settings["webRoot"]."/");

        if(isset($settings["minifier"])){
            if(isset($settings["minifier"]["location"]))
            Server::$minifier["location"] = preg_replace(Strings::PATTERN_DOUBLE_SLASH,"/",$settingsDir."/".$settings["minifier"]["location"]);
            if(isset($settings["minifier"]["sleep"]))
            Server::$minifier["sleep"] = $settings["minifier"]["sleep"];
        }

        if(isset($settings["port"]))
        Server::$port = $settings["port"];
        if(isset($settings["timeout"]))
        Server::$timeout = $settings["timeout"];
        if(isset($settings["ramSession"])){
            if(isset($settings["ramSession"]["allow"]))
            Server::$ramSession["allow"] = $settings["ramSession"]["allow"];
            if(isset($settings["ramSession"]["size"]))
            Server::$ramSession["size"] = $settings["ramSession"]["size"];
        }
        if(isset($settings["sessionTtl"]))
        Server::$sessionTtl = $settings["sessionTtl"];
        if(isset($settings["charset"]))
        Server::$charset = $settings["charset"];
        if(isset($settings["bindAddress"]))
        Server::$bindAddress = $settings["bindAddress"];
        if(isset($settings["wsMtu"]))
        Server::$wsMtu = $settings["wsMtu"];
        if(isset($settings["httpMtu"]))
        Server::$httpMtu = $settings["httpMtu"];
        if(isset($settings["cacheMaxAge"]))
        Server::$cacheMaxAge = $settings["cacheMaxAge"];
        if(isset($settings["entryPoint"]))
        Server::$entryPoint = $settings["entryPoint"];
        if(isset($settings["sessionName"]))
        Server::$sessionName = $settings["sessionName"];
        if(isset($settings["controller"])){
            if(isset($settings["controller"]["http"]))
            Server::$httpControllerPackageName = $settings["controller"]["http"];
            if(isset($settings["controller"]["ws"]))
            Server::$wsControllerPackageName = $settings["controller"]["ws"];
            if(isset($settings["controller"]["websocket"]))
            Server::$wsControllerPackageName = $settings["controller"]["websocket"];
        }
        if(isset($settings["controllers"])){
            if(isset($settings["controllers"]["http"]))
            Server::$httpControllerPackageName = $settings["controllers"]["http"];
            if(isset($settings["controllers"]["ws"]))
            Server::$wsControllerPackageName = $settings["controllers"]["ws"];
            if(isset($settings["controllers"]["websocket"]))
            Server::$wsControllerPackageName = $settings["controllers"]["websocket"];
        }
        if(isset($settings["certificate"])){
            if(isset($settings["certificate"]["name"]))
            Server::$certificateName = preg_replace(Strings::PATTERN_DOUBLE_SLASH,"/",$settingsDir."/".$settings["certificate"]["name"]);
            if(isset($settings["certificate"]["privateKey"]))
            Server::$certificatePrivateKey = preg_replace(Strings::PATTERN_DOUBLE_SLASH,"/",$settingsDir."/".$settings["certificate"]["privateKey"]);
            if(isset($settings["certificate"]["password"]))
            Server::$certificatePassphrase = $settings["certificate"]["password"];
            if(isset($settings["certificate"]["passphrase"]))
            Server::$certificatePassphrase = $settings["certificate"]["passphrase"];
        }
        if(isset($settings["header"])){
            Server::$header = $settings["header"];
        }
        Server::$sessionDir = preg_replace(Strings::PATTERN_DOUBLE_SLASH,"/",$settingsDir."/".Server::$sessionName);
        
        
        if($print) {
            $data = [
                "port\n\n"
                ."This is the port the server will belistening to."
                    =>Server::$port,
                "bindAddress\n\n"
                ."This is the address the server should bind to.\nThe address 0.0.0.0 means the server will bind to all available interfaces."
                    =>Server::$bindAddress,
                "webRoot\n\n"
                ."This is the public directory your clients will be able to access."
                    =>Server::$webRoot,
                "minifier\n\n"
                ."This is the application that will minify your assets."
                    =>Server::$minifier,
                "charset\n\n"
                ."This is the charset your server will be using to decode/encode data."
                    =>Server::$charset,
                "timeout\n\n"
                ."Your server will timeout after this many seconds for each incoming request."
                    =>Server::$timeout." seconds",
                "sessionName\n\n"
                ."Your server will save all sessions on a ramdisk.\n"
                ."This is your session ramdisk name.\n"
                ."The ramdisk is always located in the same directory as the configuration file (which is by default \"/config/http.php\")."
                    =>Server::$sessionName,
                "ramSession\n\n"
                ."This is your server's session details."
                    =>Server::$ramSession,
                "sessionTtl\n\n"
                ."This is the lifespan of each session (in seconds).\n"
                ."NOTE: Sessions are stored on a ramdisk, normally your "
                ."OS should not let you delete the ramdisk as long as it's "
                ."being used by the server, however, it can be forceully "
                ."unmounted and deleted."
                    =>Server::$sessionTtl." seconds",
                "wsMtu\n\n"
                ."This is the maximum payload length your server will send over WebSockets."
                    =>Server::$wsMtu." bytes",
                "httpMtu\n\n"
                ."This is the maximum payload length your server will send over Http."
                    =>Server::$httpMtu." bytes",
                "cookieTtl\n\n"
                ."Cookies Time To Live."
                    =>Server::$cookieTtl." seconds",
                "cacheMaxAge\n\n"
                ."Cache Time To Live."
                    =>Server::$cacheMaxAge." seconds",
                "entryPoint\n\n"
                ."This is your server's entry point, usually \"index.html\"."
                    =>"[webRoot] ".Server::$entryPoint,
                "sleep\n\n"
                ."This idicates how long the server should sleep before checking if there are any new requests (in microseconds)."
                    =>Server::$sleep." microseconds",
                "backlog\n\n"
                ."A maximum of backlog incoming connections will be queued for processing. If a connection request arrives with the queue full the client may receive an error with an indication of ECONNREFUSED, or, if the underlying protocol supports retransmission, the request may be ignored so that retries may succeed."
                    =>Server::$backlog." connections",
                "compress\n\n"
                ."Type of compression.\n\n"
                ."There's no fallback compression, is if no compression is specified, the server will not compress the data"
                    =>Server::$compress !== null?implode(" > ",Server::$compress):"DISABLED",
                "controllers\n\n"
                ."This is a mapping of your controllers Http and WebSocket controllers.\n"
                ."All your controllers should live under these two namespaces.\n"
                ."The server does not require you to setup any routing system, your client's requests will map directly to your controllers.\n"
                ."Check the documentation to read more details about the controller/request mapping system."
                    =>[
                    "http"=>Server::$httpControllerPackageName,
                    "websocket"=>Server::$wsControllerPackageName,
                ],
                "certificate\n\n"
                ."PEM certificate details.\n"
                ."[NOTE]: You can use /mkcert to make your own certificate, and you can find the /mkcert configuration in /config/certificate.php"
                    =>[
                    "name\n\n"=>Server::$certificateName,
                    "privateKey"=>Server::$certificatePrivateKey,
                    "passphrase\n\n"
                    ."[WARNING]: Don't commit this to github or any public repository!"
                        =>Server::$certificatePassphrase
                ],
                "header\n\n"
                ."Extra headers to add to your HttpResponse objects.\n"
                ."[NOTE]: These can be overwritten at runtime."
                    =>Server::$header
            ];
            echo Strings::tableFromArray($data,false,function(AsciiTable $table,int $lvl){
                //echo "LBL: $lvl\n";
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
            $webRoot="/www/",
            $minifier=[
                "location"=>"",
                "sleep"=>1000
            ],
            $charset="UTF-8",
            $bindAddress="127.0.0.1",
            
            $httpControllerPackageNameOriginal="com\\github\\tncrazvan\\catpaw\\Controller\\http",
            $httpControllerPackageName="com\\github\\tncrazvan\\catpaw\\Controller\\http",
            
            $wsControllerPackageNameOriginal="com\\github\\tncrazvan\\catpaw\\Controller\\WebSocket",
            $wsControllerPackageName="com\\github\\tncrazvan\\catpaw\\Controller\\WebSocket",
            
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
