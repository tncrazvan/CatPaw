<?php
namespace com\github\tncrazvan\catpaw\tools;

use com\github\tncrazvan\catpaw\tools\Http;
use com\github\tncrazvan\catpaw\tools\Strings;
use com\github\tncrazvan\asciitable\AsciiTable;
use com\github\tncrazvan\catpaw\http\HttpDefaultEvents;
use com\github\tncrazvan\catpaw\http\HttpSessionManager;
use com\github\tncrazvan\catpaw\websocket\WebSocketEvent;
use com\github\tncrazvan\catpaw\websocket\WebSocketEventOnOpen;

class SharedObject extends Http{
    const DIR = __DIR__;
    public array $runOnce = [];

    public function __construct(string $settingsFile,bool $print=true){
        $settings = include($settingsFile);

        if(isset($settings['webRoot'])){
            $settings['webRoot'] = \preg_replace('/\/+(?=$)/','',$settings['webRoot']);
        }

        if(isset($settings["events"])){
            foreach($settings["events"] as &$protocol){
                foreach($protocol as $key => &$value){
                    if(Strings::startsWith($key,'@') || Strings::startsWith($key,'/')) continue;
                    $newKey = $key;
                    if($newKey === '')
                        $newKey = '/';
                    else {
                        if(!Strings::startsWith($key,'/')){
                            $newKey = '/'.$newKey;
                        }
                        if(Strings::endsWith($key,'/')){
                            $newKey = \substr($newKey,-1);
                        }
                    }
                    $protocol[$newKey] = $value;
                    unset($protocol[$key]);
                }
            }
        }

        HttpDefaultEvents::init();

        if(!isset($settings["events"]["http"]["@404"]))
            $settings["events"]["http"]["@404"] = HttpDefaultEvents::$notFound;
        if(!isset($settings["events"]["http"]["@file"]))
            $settings["events"]["http"]["@file"] = HttpDefaultEvents::$file;

        $settings["events"]["websocket"]["@404"] = function(WebSocketEvent &$e, WebSocketEventOnOpen &$onOpen){
            $onOpen = new class($e) extends WebSocketEventOnOpen{
                protected $e;
                public function __construct(WebSocketEvent $e){
                    $this->e=$e;
                }
                public function run():void{
                    $this->e->close();
                }
            };
        };


        $this->dir = dirname($settingsFile);
        if(isset($settings["compress"]))
        $this->compress = $settings["compress"];
        if(isset($settings["sleep"]))
        $this->sleep = $settings["sleep"];
        $this->webRoot = preg_replace(Strings::PATTERN_DOUBLE_SLASH,"/",$this->dir."/www");
        if(isset($settings["webRoot"])){
            if($settings["webRoot"][0] === '/')
                $this->webRoot = preg_replace(Strings::PATTERN_DOUBLE_SLASH,"/",$settings["webRoot"]);
            else
                $this->webRoot = preg_replace(Strings::PATTERN_DOUBLE_SLASH,"/",$this->dir."/".$settings["webRoot"]);
        }

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
        if(isset($settings["httpMaxBodyLength"]))
        $this->httpMaxBodyLength = $settings["httpMaxBodyLength"];
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
        
        if(isset($settings["events"])){
            if(isset($settings["events"]["http"]))
                foreach($settings["events"]["http"] as $path => &$event){
                    $this->events["http"][\strtolower(\trim($path))] = $event;
                }
            if(isset($settings["events"]["websocket"]))
                foreach($settings["events"]["websocket"] as $path => &$event){
                    $this->events["websocket"][\strtolower(\trim($path))] = $event;
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
                "events\n\n"
                ."There are the exposed Http and WebSocket events this server offers."
                ."Each event is identified by an anonymus function.\n\n"

                ."##################################################"
                ."Http events don't require any special setup, simply\n"
                ."specify the path of your event as the array key, provide an anonymus function\n"
                ."as the value. The result of your function will be sent to the client as an \n"
                ."HttpResponse object. You can choose to wrap your result in an HttpResponse \n"
                ."but you can also return custom objects, in whichcase the HttpEventManager\n"
                ."will automatically wrap your object as an HttpResponse.\n\n"
                
                ."##################################################"
                ."WebSocket events require a special setup for them to work properly.\n"
                ."Like the http event the websocket event requires a key => value pair,\n"
                ."where the key is the path of your event and the value is your anonymus function.\n"
                ."Your anonymus function can take 3 special parameters of types: WebSocketEventOnOpen,\n"
                ."WebSocketEventOnMessage, WebSocketEventOnClose. \n"
                ."Each being objects that will be called exactly when their names suggest:\n"
                ."WebSocketEventOnOpen: triggers when the websocket connection is opened (successful handshake).\n"
                ."WebSocketEventOnMessage: triggers a message is received.\n"
                ."WebSocketEventOnClose: triggers when the websocket connection is closed by either parties.\n"
                    => $this->events
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
    public $dir;

    public array $httpQueue = [];
    public array $httpConnections = [];
    public array $websocketConnections = [];
    public
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
        $sleep = 0, //microseconds
        $listen=true,
        $groupsAllowed=false,
        $smtpAllowed=false,
        $backlog=50,
        $port=80,
        $timeout=3000,
        $webRoot="/www/",
        $charset="UTF-8",
        $bindAddress="127.0.0.1",
        
        $events = [
            "http"=>[],
            "websocket"=>[]
        ],

        //$wsEvents,
        $cookieTtl=60*60*24*365, //year
        $wsGroupMaxClient=10,
        $wsMtu=65535,
        $httpMaxBodyLength=1024*1024*200, //200 MB
        $httpMtu=65535,
        $cacheMaxAge=60*60*24*365, //year
        $entryPoint="/index.html",
        $wsAcceptKey = "258EAFA5-E914-47DA-95CA-C5AB0DC85B11",
        $mainSettings,
        $running=true;
}
