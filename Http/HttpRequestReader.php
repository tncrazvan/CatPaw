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
        $chain= array_fill(0, 4, null);
        $keepReading=true;
        $input = "";
        while($keepReading){
            $result = socket_read($this->client, Cat::$http_mtu);
            if(!$result){
                $keepReading = false;
            }else{
                $input .=$result;
            }
        }
        if(trim($input) === ""){
            socket_close($this->client);
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
