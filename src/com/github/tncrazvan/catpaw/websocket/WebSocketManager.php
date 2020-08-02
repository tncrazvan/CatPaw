<?php

namespace com\github\tncrazvan\catpaw\websocket;

use com\github\tncrazvan\catpaw\tools\LinkedList;
use com\github\tncrazvan\catpaw\EventManager;
use com\github\tncrazvan\catpaw\tools\Status;

abstract class WebSocketManager extends EventManager{
    public 
        $classname,
        $subscriptions = [],
        $serve = null;

    public $onOpen = null;
    public $onMessage = null;
    public $onClose = null;

    public function run():void{
        if($this->listener->so->websocketConnections == null){
            $this->listener->so->websocketConnections = new LinkedList();
        }
        $acceptKey = base64_encode(sha1($this->listener->requestHeaders->get("Sec-WebSocket-Key").$this->listener->so->wsAcceptKey,true));
        $this->serverHeaders->setStatus(Status::SWITCHING_PROTOCOLS);
        $this->serverHeaders->set("Connection","Upgrade");
        $this->serverHeaders->set("Upgrade","websocket");
        $this->serverHeaders->set("Sec-WebSocket-Accept",$acceptKey);
        $handshake = $this->serverHeaders->toString()."\r\n";
        fwrite($this->listener->client, $handshake, strlen($handshake));
        $this->listener->so->websocketConnections->insertLast($this);
        

        $message = '';
        $params = &$this->calculateParameters($message);
        if($this->serve && $params !== null){
            try{
                call_user_func_array($this->serve,$params);
            }catch(\Exception $ex){
                echo $ex->getMessage()."\n".$ex->getTraceAsString()."\n";
                $this->close();
            }
            if(!isset($this->listener->so->wsEvents[$this->classname])){
                $this->listener->so->wsEvents[$this->classname] = [$this->requestId => $this];
            }else{
                $this->listener->so->wsEvents[$this->classname][$this->requestId] = $this;
            }
            if($this->onOpen !== null)
                try{
                    $this->onOpen->run();
                } catch (\Exception $ex) {
                    echo $ex->getMessage()."\n".$ex->getTraceAsString()."\n";
                    $this->close();
                }
            
            $this->read();
        }else{
            if($params === null)
                echo "Error while calculating WebSocket parameters: $message\n";
            else
                echo "WebSocket callback for route '/".(\implode("/",$this->listener->location))."' is not defined.\n";
            $this->close();
        }
    }

    private $unnecessary = 0;
    /**
     * Attempt to read once from the socket
     * */
    public function read():void{
        if(!$this->alive) {
            if($this->unnecessary > 10){
                $this->close();
                $this->listener->so->websocketConnections->deleteNode($this);
            }
            $this->unnecessary++;
            return;
        }
        if($this->listener->so->wsMtu > 8192)
            $this->listener->so->wsMtu = 8192;
        $mtu = $this->listener->so->wsMtu;
        $masked = fread($this->listener->client, $mtu);
        $len = strlen($masked);
        if(!isset($masked) || $masked === "") return;
        $opcode = (ord($masked[0]) & 0x0F);
        if ($masked === FALSE || $opcode === 8) {
            $this->close();
            $this->listener->so->websocketConnections->deleteNode($this);
        } else if($masked !== null && unpack("C", $masked)[1] !== 136){
            if($this->onMessage !== null)
                try{
                    $this->onMessage->run($this->unmask($masked));
                } catch (\Exception $ex) {
                    echo "\n$ex\n";
                    $this->close();
                }
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

    private $savedMasks = null;
    private $savedIndex = 0;
    private $remainingLen = 0;
    function &unmask(&$payload):string{
        if($this->remainingLen <= 0){
            $this->savedIndex = 0;
            $this->savedMasks = null;
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
            $this->remainingLen = $len;
        }else{
            $data = &$payload;
            $masks = &$this->savedMasks;
            $len = $this->remainingLen;
        }
        $text = '';
        $dlen = strlen($data);
        $i = 0;
        for ($i = 0; $i < $len; ++$i) {
            if($i >= $dlen)
                break;
            
            $val = $data[$i] ^ $masks[$this->savedIndex%4];
            $text .= $data[$i] ^ $masks[$this->savedIndex%4];
            
            $this->remainingLen--;
            $this->savedIndex++;
        }
        
        if($i === $len && $len < $dlen){
            $extraPayload = substr($data,$i);
            $text .= $this->unmask($extraPayload);
        }else{
            $this->savedMasks = &$masks;
        }

        return $text;
    }
    
    private $commits = null;
    public function commit($data,int $length = 0):void{
        if($this->commits === null)
            $this->commits = new LinkedList();
        $string = $this->mask($data,$len);
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
                $this->close();
                $this->listener->so->websocketConnections->deleteNode($this);
                $this->uninstall();
            }
            $i++;
        }
    }

    private function send($data):void{
        $string = $this->mask($data,$length);
        if(!@fwrite($this->listener->client, $string, $length)){
            $this->close();
            $this->listener->so->websocketConnections->deleteNode($this);
            $this->uninstall();
        }
    }
    

    const GROUP_MANAGER = null;
}