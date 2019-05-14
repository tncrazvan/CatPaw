<?php
namespace com\github\tncrazvan\CatPaw;

use com\github\tncrazvan\CatPaw\Tools\G;
use com\github\tncrazvan\CatPaw\Tools\Strings;
use com\github\tncrazvan\CatPaw\Http\HttpSession;
use com\github\tncrazvan\CatPaw\Http\HttpEventListener;

class CatPaw extends G{
    private $socket,
            $binding,
            $listening;
    public function init(&$args):void{
        $settings = json_decode(file_get_contents($args[1]),true);
        $settingsDir = dirname($args[1]);
        if(isset($settings["sleep"]))
        G::$sleep = $settings["sleep"];
        G::$webRoot = preg_replace(Strings::PATTERN_DOUBLE_SLASH,"/",$settingsDir."/src/");
        if(isset($settings["webRoot"]))
        G::$webRoot = preg_replace(Strings::PATTERN_DOUBLE_SLASH,"/",$settingsDir."/".$settings["webRoot"]."/");
        if(isset($settings["port"]))
        G::$port = $settings["port"];
        if(isset($settings["timeout"]))
        G::$timeout = $settings["timeout"];
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
        HttpSession::$SESSION_DIR = preg_replace(Strings::PATTERN_DOUBLE_SLASH,"/",G::DIR."/".G::$sessionName);
        print_r([
            "port"=>G::$port,
            "bindAddress"=>G::$bindAddress,
            "webRoot"=>G::$webRoot,
            "charset"=>G::$charset,
            "timeout"=>G::$timeout." seconds",
            "sessionTtl"=>G::$sessionTtl." seconds",
            "sessionSize"=>G::$sessionSize." MB",
            "wsMtu"=>G::$wsMtu." bytes",
            "httpMtu"=>G::$httpMtu." bytes",
            "cookieTtl"=>G::$cookieTtl." seconds",
            "cacheMaxAge"=>G::$cacheMaxAge." seconds",
            "entryPoint"=>"[webRoot] ".G::$entryPoint,
            "sleep"=>G::$sleep." microseconds",
            "backlog"=>G::$backlog." connections",
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
    }
    
    /**
     * @param &$args This is the input array. The first element of this array should point to the settings json file, for example "http.json".
     * @param $intercept This is an anonymus function that will be called before the server socket is created and after the socket context is created.
     * This function will be fed one parameters: <b>$context</b>, this is the stream context of the server socket (which, by the way, is not created yet at this point).
     * Modify this context to suit your needs.
     * @param $certificate This is the certificate filename. Note that the path is relative to the settings (http.json) file.
     * @param $password This is the passphrase of your certificate.
     */
    public function __construct(&$args,$intercept=null) {
        $protocol="tcp";
        $argsLength = count($args);
        if($argsLength > 1 && file_exists($args[1])){
            $this->init($args);
            $context = stream_context_create();
            //check if SSL certificate file is specified
            if(G::$certificateName !== ""){
                //use the SSL certificate
                stream_context_set_option($context, 'ssl', 'local_cert', G::$certificateName);
                if(isset($settings["certificate"]["privateKey"]))
                    stream_context_set_option($context, 'ssl', 'local_pk', G::$certificatePrivateKey);
                stream_context_set_option($context, 'ssl', 'passphrase', G::$certificatePassphrase);
                stream_context_set_option($context, 'ssl', 'cyphers', 2);
                stream_context_set_option($context, 'ssl', 'allow_self_signed', true);
                stream_context_set_option($context, 'ssl', 'verify_peer', false);
            }
            //let the developer intercept the stream context
            if($intercept !== null) $intercept($context);
            // Create the server socket
            $this->socket = stream_socket_server(
                $protocol.'://'.G::$bindAddress.':'.G::$port,
                $errno,
                $errstr,
                STREAM_SERVER_BIND|STREAM_SERVER_LISTEN,
                $context
            );
            if(G::$certificateName !== "")
                stream_socket_enable_crypto($this->socket, false);
            if ($this->socket === false) throw new \Exception("$errstr ($errno)\n");
            $this->listening=true;
            $this->start();
        }else{
            throw new \Exception ("\nSettings json file doesn't exist\n");
        }
    }
    
    protected function mountSession():void{
        if(file_exists(HttpSession::SESSION_DIR)){
            echo shell_exec("umount ".HttpSession::SESSION_DIR);
        }
        echo shell_exec("rm ".HttpSession::SESSION_DIR." -fr");
        echo shell_exec("mkdir ".HttpSession::SESSION_DIR);
        echo shell_exec("mount -t tmpfs tmpfs ".HttpSession::SESSION_DIR." -o size=".G::$sessionSize."M");
    }
    
    private $clients = [];
    private function start():void{
        if (!$this->listening) return;
        array_push($this->clients, $this->socket);
        echo "\nServer started.\n";
        while($this->listening){
            // create a copy
            $copy = $this->clients;
            if (@stream_select($copy, $write = NULL, $except = NULL, 0, self::$sleep) < 1){
                continue;
            }
                
            // check if there is a client trying to connect
            
            if (in_array($this->socket, $copy)) {
                // accept the client, and add him to the $clients array
                $this->clients[] = $client = stream_socket_accept($this->socket,"-1",$remoteIp);
                // remove the listening sockets from the clients-with-data array
                $key = array_search($this->socket, $copy);
                unset($copy[$key]);
            }
            $this->watchClients($copy);
        }
        fclose($this->socket);
        echo "\nServer stopped.\n";
    }
    
    private function watchClients(array &$copy):void{
        foreach($copy as &$client){
            $key = array_search($client, $copy);
            $pid = pcntl_fork();
            if ($pid == -1) {
                //fork could not be made
                unset($copy[$key]);
                unset($this->clients[$key]);
                @fclose($client);
            } else if ($pid) {
                 // we are the parent
                unset($copy[$key]);
                unset($this->clients[$key]);
                @fclose($client);
            } else {
                if(G::$certificateName !== ""){
                    stream_set_blocking ($client, true); // block the connection until SSL is done.
                    @stream_socket_enable_crypto($client, true, STREAM_CRYPTO_METHOD_SSLv3_SERVER);
                }

                $listener = new HttpEventListener($client, $this->clients);
                $listener->run();
                
                if(G::$certificateName !== "")
                    stream_set_blocking ($client, false);
                exit;
            }
        }
    }
    
    public function isOnline():bool{
        return $this->listening;
    }
    
    public function stop():void{
        $this->listening = false;
    }
}