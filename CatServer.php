<?php
namespace com\github\tncrazvan\CatServer;

use com\github\tncrazvan\CatServer\Http\HttpEventListener;

class CatServer extends Cat{
    private $socket,
            $binding,
            $listening,
            $started;
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
            socket_set_option($this->socket, SOL_SOCKET, SO_REUSEADDR, 1);
            $this->binding = socket_bind($this->socket, self::$bind_address, self::$port);
            if ($this->binding === false) throw new \Exception(socket_strerror(socket_last_error($this->socket)));
            $this->listening = socket_listen($this->socket, self::$backlog);
            if ($this->listening === false) throw new \Exception(socket_strerror(socket_last_error($this->socket)));
            $this->start();
        }else{
            throw new \Exception ("\nSettings json file doesn't exist\n");
        }
    }
    private $clients = [];
    private function start():void{
        if (!$this->listening) return;
        array_push($this->clients, $this->socket);
        socket_set_block($this->socket);
        echo "\nServer started.\n";
        while($this->listening){
            // create a copy
            $copy = $this->clients;
            if (@socket_select($copy, $write, $except, 0) < 1){
                continue;
            }
                
            // check if there is a client trying to connect
            
            if (in_array($this->socket, $copy)) {
                // accept the client, and add him to the $clients array
                $this->clients[] = $newsock = socket_accept($this->socket);
                
                // remove the listening socket from the clients-with-data array
                $key = array_search($this->socket, $copy);
                unset($copy[$key]);
            }
            $this->watch_clients($copy);
            usleep(self::$sleep);
        }
        socket_close($this->socket);
        echo "\nServer stopped.\n";
    }
    
    private function watch_clients(array &$copy):void{
        foreach($copy as &$client){
            $listener = new HttpEventListener($client, $this->clients);
            $listener->run();
            $key = array_search($client, $copy);
            unset($copy[$key]);
            $key = array_search($client, $this->clients);
            unset($this->clients[$key]);
        }
    }
    
    public function isOnline():bool{
        return $this->listening;
    }
    
    public function stop():void{
        $this->listening = false;
    }
}