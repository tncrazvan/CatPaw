<?php

namespace com\github\tncrazvan\CatServer\WebSocket;

use com\github\tncrazvan\CatServer\Http\EventManager;
use com\github\tncrazvan\CatServer\Http\HttpHeader;
use com\github\tncrazvan\CatServer\Cat;

abstract class WebSocketManager extends EventManager{
    protected $subscriptions = [],
            $client,
            $request_id,
            $connected = true,
            $content;
    public function __construct(&$client,HttpHeader &$client_header,string &$content) {
        parent::__construct($client,$client_header);
        $this->client=$client;
        $this->request_id = spl_object_hash($this).rand();
        $this->content=$content;
    }
    public function run(){
        $accept_key = base64_encode(sha1($this->client_header->get("Sec-WebSocket-Key").Cat::$ws_accept_key,true));
        $this->server_header->set("Status","HTTP/1.1 101 Switching Protocols");
        $this->server_header->set("Connection","Upgrade");
        $this->server_header->set("Upgrade","websocket");
        $this->server_header->set("Sec-WebSocket-Accept",$accept_key);
        socket_write($this->client, $this->server_header->toString()."\r\n");
        $this->onOpen();
        while($this->connected){
            $result = socket_recv($this->client, $masked, Cat::$ws_mtu, MSG_DONTWAIT);
            $opcode = (ord($masked[0]) & 0x0F);
            if ($result === 0 || $opcode === 8) {
                $this->close();
            } else if($masked !== null && unpack("C", $masked)[1] !== 136){
                $this->onMessage($this->unmask($masked));
            }
            usleep(Cat::$sleep);
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
    
    
    public function mask($text){
        $b1 = 0x80 | (0x1 & 0x0f);
        $length = strlen($text);

        if( $length <= 125)
            $header = pack('CC', $b1, $length);
        elseif ($length > 125 && $length < 65536)
            $header = pack('CCS', $b1, 126, $length);
        elseif ($length >= 65536)
            $header = pack('CCN', $b1, 127, $length);

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
        socket_close($this->client);
        $this->onClose();
        exit;
    }
    
    public function send($data):void{
        if(!@socket_write($this->client, $this->mask($data))){
            $this->close();
        }
    }
    
    protected abstract function onOpen():void;
    protected abstract function onMessage($data):void;
    protected abstract function onClose():void;
}