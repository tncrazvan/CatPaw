<?php
namespace com\github\tncrazvan\CatServer\Http;

use com\github\tncrazvan\CatServer\Cat;
abstract class HttpRequestReader{
    protected 
            $client,
            $header,
            $content;
    
    public function __construct($client) {
        $this->client = $client;
    }
    
    public function run():void{
        $input = socket_read($this->client, Cat::$http_mtu);
        if(!$input){
            return;
        }
        if(trim($input) === ""){
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
        return;
    }
    public abstract function onRequest(HttpHeader &$client_header, string &$content):void;
}
