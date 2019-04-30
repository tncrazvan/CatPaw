<?php
namespace com\github\tncrazvan\CatServer;

use com\github\tncrazvan\CatServer\Http\HttpEventListener;

class CatServer{
    private $socket,
            $binding,
            $listening,
            $online;
    public function __construct(string $binding_address,int $binding_port,int $backlog=50) {
        $this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if ($this->socket === false) throw new \Exception(socket_strerror(socket_last_error()));
        
        $this->binding = socket_bind($this->socket, $binding_address, $binding_port);
        if ($this->binding === false) throw new \Exception(socket_strerror(socket_last_error($this->socket)));
        
        $this->listening = socket_listen($this->socket, $backlog);
        if ($this->listening === false) throw new \Exception(socket_strerror(socket_last_error($this->socket)));
        socket_set_nonblock($this->socket);
    }
    
    public function go_online():void{
        if (!$this->listening) return;
        $this->online = true;
        echo "\nonline...";
        do{
            $client = socket_accept($this->socket);
            if ($client === false) {
                usleep(Cat::$sleep);
                continue;
            }
            $listener = new HttpEventListener($client);
            $listener->run();
        }while($this->online);
        socket_close($this->socket);
        echo "offline...";
    }
    
    public function go_offline():void{
        $this->online = false;
    }
}