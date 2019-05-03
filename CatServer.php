<?php
namespace com\github\tncrazvan\CatServer;

use com\github\tncrazvan\CatServer\Http\HttpEventListener;

class CatServer extends Cat{
    private $socket,
            $binding,
            $listening,
            $started,
            $websocket_clients=[];
    public function addWebSocketClient(&$client,int &$memoryKey):void{
        
    }
    private function init(&$args):void{
        $settings = json_decode(file_get_contents($args[1]),true);
        if(isset($settings["sleep"]))
            Cat::$sleep = $settings["sleep"];
        Cat::$web_root = dirname($args[1])."/www/";
        if(isset($settings["webRoot"]))
            Cat::$web_root = dirname($args[1])."/".$settings["webRoot"];
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
            "sleep"=>Cat::$sleep." microseconds",
            "backlog"=>Cat::$backlog." connections",
            "controllers"=>[
                "http"=>Cat::$http_controller_package_name,
                "websocket"=>Cat::$ws_controller_package_name,
            ]
        ]);
    }
    
    public function __construct(&$args) {
        $args_length = count($args);
        if($args_length > 1 && file_exists($args[1])){
            $this->init($args);
            $this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
            if ($this->socket === false) throw new \Exception(socket_strerror(socket_last_error()));

            $this->binding = socket_bind($this->socket, self::$bind_address, self::$port);
            if ($this->binding === false) throw new \Exception(socket_strerror(socket_last_error($this->socket)));

            $this->listening = socket_listen($this->socket, self::$backlog);
            if ($this->listening === false) throw new \Exception(socket_strerror(socket_last_error($this->socket)));
            socket_set_nonblock($this->socket);
            
            $this->start();
        }else{
            throw new \Exception ("\nSettings json file doesn't exist\n");
        }
    }
    
    private function start():void{
        if (!$this->listening) return;
        $this->started = true;
        echo "\nServer started.\n";
        do{
            $client = socket_accept($this->socket);
            if ($client === false) {
                usleep(Cat::$sleep);
                continue;
            }
            
            $listener = new HttpEventListener($client);
            $listener->run();
        }while($this->started);
        socket_close($this->socket);
        echo "\nServer stopped.\n";
    }
    
    public function isOnline():bool{
        return $this->started;
    }
    
    public function stop():void{
        $this->started = false;
    }
}