<?php

namespace com\github\tncrazvan\catpaw\websocket;

use com\github\tncrazvan\catpaw\tools\SharedObject;
use com\github\tncrazvan\catpaw\tools\LinkedList;
use com\github\tncrazvan\catpaw\http\EventManager;
use com\github\tncrazvan\catpaw\websocket\WebSocketCommit;

abstract class WebSocketManager extends EventManager{
    public 
        $classname,
        $subscriptions = [];

    public function run():void{
        if($this->listener->so->websocketConnections == null){
            $this->listener->so->websocketConnections = new LinkedList();
        }
        $acceptKey = base64_encode(sha1($this->listener->requestHeaders->get("Sec-WebSocket-Key").$this->listener->so->wsAcceptKey,true));
        $this->serverHeaders->set("Status","HTTP/1.1 101 Switching Protocols");
        $this->serverHeaders->set("Connection","Upgrade");
        $this->serverHeaders->set("Upgrade","websocket");
        $this->serverHeaders->set("Sec-WebSocket-Accept",$acceptKey);
        $handshake = $this->serverHeaders->toString()."\r\n";
        fwrite($this->listener->client, $handshake, strlen($handshake));
        $this->listener->so->websocketConnections->insertLast($this);
        $this->onOpenCaller();
        $this->read();
    }

    private $unnecessary = 0;
    /**
     * Attempt to read once from the socket
     * */
    public function read():void{
        if(!$this->alive) {
            if($this->unnecessary > 10){
                $this->onCloseCaller();
                $this->close();
                $this->listener->so->websocketConnections->deleteNode($this);
            }
            $this->unnecessary++;
            return;
        }
        $masked = fread($this->listener->client, $this->listener->so->wsMtu);
        if(!isset($masked) || $masked === "") return;
        $opcode = (ord($masked[0]) & 0x0F);
        if ($masked === FALSE || $opcode === 8) {
            $this->onCloseCaller();
            $this->close();
            $this->listener->so->websocketConnections->deleteNode($this);
        } else if($masked !== null && unpack("C", $masked)[1] !== 136){
            $this->onMessageCaller($this->unmask($masked));
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
    
    
    public function mask(&$data,&$length):string{
        $b1 = 0x80 | (0x1 & 0x0f);
        $length = strlen($data);

        if( $length <= 125)
            $header = pack('CC', $b1, $length);
        elseif ($length > 125 && $length < 65536)
            $header = pack('CCS', $b1, 126, $length);
        elseif ($length >= 65536)
            $header = pack('CCN', $b1, 127, $length);

        $length += strlen($header);
        return $header.$data;
    }
    function &unmask(&$payload):string{
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
    
    private $commits = null;
    public function commit($data,int $length = 0):void{
        if($this->commits === null)
            $this->commits = new LinkedList();
        $string = &$this->mask($data,$len);
        if($length > 0 && $len > $length){
            $chunks = str_split($string,$length);
            for($i=0,$len=count($chunks);$i<$len;$i++){
                $response = $chunks[$i];
                if($i === $len -1)
                    $this->commits->insertLast(new WebSocketCommit($response,\strlen($response)));
                else
                    $this->commits->insertLast(new WebSocketCommit($response,$length));
            }
        }else
            $this->commits->insertLast(new WebSocketCommit($string,$len));
    }

    public function push(int $count=-1):void{
        if($this->commits === null)
            $this->commits = new LinkedList();
        $i = 0;
        while(!$this->commits->isEmpty() && ($count < 0 || ($count > 0 && $i < $count))){
            $wsCommit = $this->commits->getFirstNode();
            $this->commits->deleteFirstNode();
            if($wsCommit === null){
                $i++;
                continue;
            }
            $wsCommit = $wsCommit->readNode();
            if(!@fwrite($this->listener->client, $wsCommit->getData(), $wsCommit->getLength())){
                $this->onCloseCaller();
                $this->close();
                $this->listener->so->websocketConnections->deleteNode($this);
            }
            $i++;
        }
    }

    private function send($data):void{
        $string = $this->mask($data,$length);
        if(!@fwrite($this->listener->client, $string, $length)){
            $this->onCloseCaller();
            $this->close();
            $this->listener->so->websocketConnections->deleteNode($this);
        }
    }
    

    const GROUP_MANAGER = null;
    protected abstract function onOpenCaller():void;
    protected abstract function onMessageCaller(string $data):void;
    protected abstract function onCloseCaller():void;

}