<?php
namespace com\github\tncrazvan\CatServer\Http;

use com\github\tncrazvan\CatServer\Cat;
abstract class HttpRequestReader{
    protected 
            $client,
            $clients,
            $header,
            $content;
    
    public function __construct($client,array &$clients) {
        $this->client = $client;
        $this->clients = $clients;
    }
    
    public function run():void{
        $input = socket_read($this->client, Cat::$http_mtu);
        if(!$input){
            $key = array_search($this->client, $this->clients);
            unset($this->clients[$key]);
            return;
        }
        if(trim($input) === ""){
            socket_close($this->client);
            unset($this->clients[$key]);
            return;
        }
        $input = preg_split("/\\r\\?\\n\\r\\?\\n/m", $input);
        $parts_counter = count($input);
        if($parts_counter === 0){
            socket_close($this->client);
            return;
        }
        $str_header = $input[0];
        $this->content = $parts_counter>1?$input[1]:"";
        $this->header = HttpHeader::fromString($str_header);
        $this->onRequest($this->header,$this->content);
    }
    public abstract function onRequest(HttpHeader &$client_header, string $content):void;
}
