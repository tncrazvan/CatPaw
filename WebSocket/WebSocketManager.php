<?php

namespace com\github\tncrazvan\CatPaw\WebSocket;

use com\github\tncrazvan\CatPaw\Tools\Server;
use com\github\tncrazvan\CatPaw\Http\HttpHeader;
use com\github\tncrazvan\CatPaw\Http\EventManager;

abstract class WebSocketManager extends EventManager{
    protected $subscriptions = [],
            $client,
            $requestId,
            $connected = true,
            $content;
    public function __construct(&$client,HttpHeader &$clientHeader,string &$content) {
        parent::__construct($client,$clientHeader);
        $this->client=$client;
        $this->requestId = spl_object_hash($this).rand();
        $this->content=$content;
    }
    public function run(){
        $acceptKey = base64_encode(sha1($this->clientHeader->get("Sec-WebSocket-Key").Server::$wsAcceptKey,true));
        $this->serverHeader->set("Status","HTTP/1.1 101 Switching Protocols");
        $this->serverHeader->set("Connection","Upgrade");
        $this->serverHeader->set("Upgrade","websocket");
        $this->serverHeader->set("Sec-WebSocket-Accept",$acceptKey);
        $handshake = $this->serverHeader->toString()."\r\n";
        fwrite($this->client, $handshake, strlen($handshake));
        $this->onOpen();
        while($this->connected){
            $masked = fread($this->client, Server::$wsMtu);
            if(!isset($masked)) continue;
            $opcode = (ord($masked[0]) & 0x0F);
            if ($masked === FALSE || $opcode === 8) {
                $this->close();
            } else if($masked !== null && unpack("C", $masked)[1] !== 136){
                $this->onMessage($this->unmask($masked));
            }
            usleep(Server::$sleep);
        }
    }
    
    /*
        WEBSOCKET FRAME:


              0                   1                   2                   3
              0 1 2 3 4 5 6 7 8 9 0 1 2 3 4 5 6 7 8 9 0 1 2 3 4 5 6 7 8 9 0 1
             +-+-+-+-+-------+-+-------------+-------------------------------+
             |F|R|R|R| opcode|M| Payload len |    Extended payload length    |
             |I|S|S|S|  (4)  |A|     (7)     |             (16/64)           |
             |N|V|V|V|       |S|             |   (if payload len==126/127)   |
             | |1|2|3|       |K|             |                               |
             +-+-+-+-+-------+-+-------------+ - - - - - - - - - - - - - - - +
             |     Extended payload length continued, if payload len == 127  |
             + - - - - - - - - - - - - - - - +-------------------------------+
             |                               |Masking-key, if MASK set to 1  |
             +-------------------------------+-------------------------------+
             | Masking-key (continued)       |          Payload Data         |
             +-------------------------------- - - - - - - - - - - - - - - - +
             :                     Payload Data continued ...                :
             + - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - +
             |                     Payload Data continued ...                |
             +---------------------------------------------------------------+
        */
    
    
    public function mask($text,&$length){
        $b1 = 0x80 | (0x1 & 0x0f);
        $length = strlen($text);

        if( $length <= 125)
            $header = pack('CC', $b1, $length);
        elseif ($length > 125 && $length < 65536)
            $header = pack('CCS', $b1, 126, $length);
        elseif ($length >= 65536)
            $header = pack('CCN', $b1, 127, $length);

        $length += strlen($header);
        return $header.$text;
    }
    function unmask(&$payload):string{
        $length = ord($payload[1]) & 127;
        
        if($length == 126) {
            $masks = substr($payload, 4, 4);
            $len = (ord($payload[2]) << 8) + ord($payload[3]);
            $data = substr($payload, 8);
        }
        elseif($length == 127) {
            $masks = substr($payload, 10, 4);
            $len = (ord($payload[2]) << 56) + (ord($payload[3]) << 48) +
                (ord($payload[4]) << 40) + (ord($payload[5]) << 32) + 
                (ord($payload[6]) << 24) +(ord($payload[7]) << 16) + 
                (ord($payload[8]) << 8) + ord($payload[9]);
            $data = substr($payload, 14);
        }
        else {
            $masks = substr($payload, 2, 4);
            $len = $length;
            $data = substr($payload, 6);
        }
        $text = '';
        for ($i = 0; $i < $len; ++$i) {
            if(isset($data[$i])){
                $text .= $data[$i] ^ $masks[$i%4];
            }
        }
        return $text;
    }
    
    public function close():void{
        $this->connected = false;
        fclose($this->client);
        $this->onClose();
        exit;
    }
    
    public function send($data):void{
        $string = $this->mask($data,$length);
        if(!@fwrite($this->client, $string, $length)){
            $this->close();
        }
    }
    
    protected abstract function onOpen():void;
    protected abstract function onMessage($data):void;
    protected abstract function onClose():void;
}