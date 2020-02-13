<?php
namespace com\github\tncrazvan\catpaw\tools;

use com\github\tncrazvan\asciitable\AsciiTable;
use com\github\tncrazvan\catpaw\http\HttpSessionManager;
use com\github\tncrazvan\catpaw\tools\Http;
use com\github\tncrazvan\catpaw\tools\Strings;
use stdClass;

class SharedObject extends Http{
    const DIR = __DIR__;

    public function __construct(string $settingsFile,bool $print=true){
        $settings = include($settingsFile);
        $this->dir = dirname($settingsFile);
        if(isset($settings["compress"]))
        $this->compress = $settings["compress"];
        if(isset($settings["sleep"]))
        $this->sleep = $settings["sleep"];
        $this->webRoot = preg_replace(Strings::PATTERN_DOUBLE_SLASH,"/",$this->dir."/www/");
        if(isset($settings["webRoot"]))
        $this->webRoot = preg_replace(Strings::PATTERN_DOUBLE_SLASH,"/",$this->dir."/".$settings["webRoot"]."/");

        
        if(isset($settings["scripts"]))
        $this->scripts = $settings["scripts"];

        if(isset($settings["port"]))
        $this->port = $settings["port"];
        if(isset($settings["timeout"]))
        $this->timeout = $settings["timeout"];
        if(isset($settings["ramSession"])){
            if(isset($settings["ramSession"]["allow"]))
            $this->ramSession["allow"] = $settings["ramSession"]["allow"];
            if(isset($settings["ramSession"]["size"]))
            $this->ramSession["size"] = $settings["ramSession"]["size"];
        }
        if(isset($settings["sessionTtl"]))
        $this->sessionTtl = $settings["sessionTtl"];
        if(isset($settings["charset"]))
        $this->charset = $settings["charset"];
        if(isset($settings["bindAddress"]))
        $this->bindAddress = $settings["bindAddress"];
        if(isset($settings["wsMtu"]))
        $this->wsMtu = $settings["wsMtu"];
        if(isset($settings["httpMtu"]))
        $this->httpMtu = $settings["httpMtu"];
        if(isset($settings["cacheMaxAge"]))
        $this->cacheMaxAge = $settings["cacheMaxAge"];
        if(isset($settings["entryPoint"]))
        $this->entryPoint = $settings["entryPoint"];
        if(isset($settings["sessionName"]))
        $this->sessionName = $settings["sessionName"];
        if(isset($settings["editor"]))
        $this->editor = $settings["editor"];
        
        if(isset($settings["controller"])){
            if(isset($settings["controller"]["http"]))
            $this->httpControllerPackageName = $settings["controller"]["http"];
            if(isset($settings["controller"]["ws"]))
            $this->wsControllerPackageName = $settings["controller"]["ws"];
            if(isset($settings["controller"]["websocket"]))
            $this->wsControllerPackageName = $settings["controller"]["websocket"];
        }
        if(isset($settings["controllers"])){
            if(isset($settings["controllers"]["http"]))
                foreach($settings["controllers"]["http"] as $path => &$classname){
                    $this->controllers["http"][\strtolower(\trim($path))] = $classname;
                }
            if(isset($settings["controllers"]["websocket"]))
                foreach($settings["controllers"]["websocket"] as $path => &$classname){
                    $this->controllers["websocket"][\strtolower(\trim($path))] = $classname;
                }
        }
        if(isset($settings["certificate"])){
            if(isset($settings["certificate"]["name"]))
            $this->certificateName = preg_replace(Strings::PATTERN_DOUBLE_SLASH,"/",$this->dir."/".$settings["certificate"]["name"]);
            if(isset($settings["certificate"]["privateKey"]))
            $this->certificatePrivateKey = preg_replace(Strings::PATTERN_DOUBLE_SLASH,"/",$this->dir."/".$settings["certificate"]["privateKey"]);
            if(isset($settings["certificate"]["password"]))
            $this->certificatePassphrase = $settings["certificate"]["password"];
            if(isset($settings["certificate"]["passphrase"]))
            $this->certificatePassphrase = $settings["certificate"]["passphrase"];
        }
        if(isset($settings["headers"])){
            $this->headers = $settings["headers"];
        }
        $this->sessionDir = preg_replace(Strings::PATTERN_DOUBLE_SLASH,"/",$this->dir."/".$this->sessionName);
        
        
        if($print) {
            $data = [
                "port\n\n"
                ."This is the port the server will belistening to."
                    =>$this->port,
                "bindAddress\n\n"
                ."This is the address the server should bind to.\nThe address 0.0.0.0 means the server will bind to all available interfaces."
                    =>$this->bindAddress,
                "webRoot\n\n"
                ."This is the public directory your clients will be able to access."
                    =>$this->webRoot,
                "editor\n\n"
                ."This is the script that launches your chosen code editor.\n"
                ."This script is used by the controller generator script (./controller) to open your controller in edit more automatically."
                    =>$this->editor,
                "charset\n\n"
                ."This is the charset your server will be using to decode/encode data."
                    =>$this->charset,
                "timeout\n\n"
                ."Your server will timeout after this many seconds for each incoming request."
                    =>$this->timeout." seconds",
                "sessionName\n\n"
                ."Your server will save all sessions on a ramdisk.\n"
                ."This is your session ramdisk name.\n"
                ."The ramdisk is always located in the same directory as the configuration file (which is by default \"/config/http.php\")."
                    =>$this->sessionName,
                "ramSession\n\n"
                ."This is your server's session details."
                    =>$this->ramSession,
                "sessionTtl\n\n"
                ."This is the lifespan of each session (in seconds).\n"
                ."NOTE: Sessions are stored on a ramdisk, normally your "
                ."OS should not let you delete the ramdisk as long as it's "
                ."being used by the server, however, it can be forceully "
                ."unmounted and deleted."
                    =>$this->sessionTtl." seconds",
                "wsMtu\n\n"
                ."This is the maximum payload length your server will send over WebSockets."
                    =>$this->wsMtu." bytes",
                "httpMtu\n\n"
                ."This is the maximum payload length your server will send over Http."
                    =>$this->httpMtu." bytes",
                "cookieTtl\n\n"
                ."Cookies Time To Live."
                    =>$this->cookieTtl." seconds",
                "cacheMaxAge\n\n"
                ."Cache Time To Live."
                    =>$this->cacheMaxAge." seconds",
                "entryPoint\n\n"
                ."This is your server's entry point, usually \"index.html\"."
                    =>"[webRoot] ".$this->entryPoint,
                "sleep\n\n"
                ."This idicates how long the server should sleep before checking if there are any new requests (in microseconds)."
                    =>$this->sleep." microseconds",
                "backlog\n\n"
                ."A maximum of backlog incoming connections will be queued for processing. If a connection request arrives with the queue full the client may receive an error with an indication of ECONNREFUSED, or, if the underlying protocol supports retransmission, the request may be ignored so that retries may succeed."
                    =>$this->backlog." connections",
                "compress\n\n"
                ."Type of compression.\n\n"
                ."There's no fallback compression, is if no compression is specified, the server will not compress the data"
                    =>$this->compress !== null?implode(" > ",$this->compress):"DISABLED",
                "controllers\n\n"
                ."This is a mapping of your controllers Http and WebSocket controllers.\n"
                ."All your controllers should live under these two namespaces.\n"
                ."The server does not require you to setup any routing system, your client's requests will map directly to your controllers.\n"
                ."Check the documentation to read more details about the controller/request mapping system."
                    =>[
                    "http"=>$this->httpControllerPackageName,
                    "websocket"=>$this->wsControllerPackageName,
                ],
                "certificate\n\n"
                ."PEM certificate details.\n"
                ."[NOTE]: You can use /mkcert to make your own certificate, and you can find the /mkcert configuration in /config/certificate.php"
                    =>[
                    "name\n\n"=>$this->certificateName,
                    "privateKey"=>$this->certificatePrivateKey,
                    "passphrase\n\n"
                    ."[WARNING]: Don't commit this to github or any public repository!"
                        =>$this->certificatePassphrase
                ],
                "headers\n\n"
                ."Extra headers to add to your HttpResponse objects.\n"
                ."[NOTE]: These can be overwritten at runtime."
                    =>$this->headers,
                "scripts\n\n"
                ."These are some of the scripts the server could use for quality of life improvements.\n"
                ."For example the scripts.editor script will be executed after running 'php controller' actions.\n"
                ."To know more about controller actions run the 'php controller actions' script located in the root of this project."
                    =>$this->scripts,
                "controllers\n\n"
                ."There are the exposed Http and WebSocket controllers this server offers."
                    =>$this->controllers
            ];
            echo Strings::tableFromArray($data,false,function(AsciiTable $table,int $lvl){
                //echo "LBL: $lvl\n";
                switch($lvl){
                    case 0:
                        $table->style(0,[
                            "width"=>50
                        ]);
                        $table->style(1,[
                            "width"=>2048
                        ]);
                    break;
                    case 1:
                        $table->style(0,[
                            "width"=>2048
                        ]);
                        $table->style(1,[
                            "width"=>2048
                        ]);
                    break;
                }
            })."\n";
        }
        $this->sessions = new HttpSessionManager();
    }

    public
        $dir,
        $scripts=[
            //"minify" => "minify --type=@type \"@filename\"",
            "minify" => "'No minify script defined.'",
            "editor" => "code @filename"
        ],
        $httpConnections = null,
        $websocketConnections = null,
        $sessions,
        $headers = [],
        $compress = null,
        $sessionDir = "",
        $sessionName = "/_SESSION",
        $editor = "",
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
        $charset="UTF-8",
        $bindAddress="127.0.0.1",

        $controllers = [
            "http"=>[
                "/"=>\com\github\tncrazvan\catpaw\controller\http\EntryPoint::class,
                "@file"=>\com\github\tncrazvan\catpaw\controller\http\File::class,
                "@404"=>\com\github\tncrazvan\catpaw\controller\http\ControllerNotFound::class
            ],
            "websocket"=>[
                "@404"=>\com\github\tncrazvan\catpaw\controller\websocket\ControllerNotFound::class
            ]
        ],

        $httpControllerPackageNameOriginal="com\\github\\tncrazvan\\catpaw\\controller\\http",
        $httpControllerPackageName="com\\github\\tncrazvan\\catpaw\\controller\\http",

        $wsControllerPackageNameOriginal="com\\github\\tncrazvan\\catpaw\\controller\\WebSocket",
        $wsControllerPackageName="com\\github\\tncrazvan\\catpaw\\controller\\WebSocket",

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
