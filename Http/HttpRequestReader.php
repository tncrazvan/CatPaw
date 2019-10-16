<?php
namespace com\github\tncrazvan\CatPaw\Http;

use com\github\tncrazvan\CatPaw\Tools\Server;
use com\github\tncrazvan\CatPaw\Http\HttpHeader;

abstract class HttpRequestReader{
    protected 
            $client,
            $header,
            $content;
    
    public function __construct($client) {
        $this->client = $client;
    }
    
    public function run():void{
        $input = fread($this->client, Server::$httpMtu);
        if(!$input){
            return;
        }
        if(trim($input) === ""){
            return;
        }
        $input = preg_split('/\r\n\r\n/', $input,2);
        $partsCounter = count($input);
        if($partsCounter === 0){
            fclose($this->client);
            return;
        }
        $strHeader = $input[0];
        $this->content = $partsCounter>1?$input[1]:"";
        $this->header = HttpHeader::fromString($strHeader);
        $this->onRequest($this->header,$this->content);
        return;
    }
    public abstract function onRequest(HttpHeader &$clientHeader, string &$content):void;
}
