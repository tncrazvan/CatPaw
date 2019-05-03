<?php

namespace com\github\tncrazvan\CatServer\WebSocket;

use com\github\tncrazvan\CatServer\Http\EventManager;
use com\github\tncrazvan\CatServer\Http\HttpHeader;

class WebSocketManager extends EventManager{
    private $subscriptions = [],
            $client,
            $request_id,
            $connected = true,
            $content;
    public function __construct(&$client,HttpHeader &$client_header,string &$content) {
        parent::__construct($client_header);
        $this->client=$client;
        $this->request_id = spl_object_hash($this);
        $this->content=$content;
    }
    
    public function getClientHeader(): HttpHeader{
        return $this->client_header;
    }
    public function getClient(){
        return $this->client;
    }
    public function getUserLanguages():array{
        return $this->user_languages;
    }
    public function getUserDefaultLanguage():string{
        return $this->user_languages["DEFAULT-LANGUAGE"];
    }
    public function getUserAgent():string{
        return $this->client_header->get("User-Agent");
    }
    public function execute():void{
        $pid = \pcntl_fork();
        if ($pid == -1) {
             die('could not fork');
        } else if ($pid) {
             // we are the parent
             \pcntl_wait($status); //Protect against Zombie children
        } else {
             // we are the child
        }

    }
}