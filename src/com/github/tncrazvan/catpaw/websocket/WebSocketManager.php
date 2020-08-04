<?php

namespace com\github\tncrazvan\catpaw\websocket;

use com\github\tncrazvan\catpaw\tools\LinkedList;
use com\github\tncrazvan\catpaw\EventManager;
use com\github\tncrazvan\catpaw\tools\Status;

abstract class WebSocketManager extends EventManager{
    public array $subscriptions = [];
    public \Closure $callback;

    public ?WebSocketEventOnOpen $onOpen = null;
    public ?WebSocketEventOnMessage $onMessage = null;
    public ?WebSocketEventOnClose $onClose = null;
    private \SplDoublyLinkedList $commits;
    
    private const MAX_CHUNK_SIZE = 65535;

    public function run():void{
        $acceptKey = \base64_encode(sha1($this->listener->requestHeaders->get("Sec-WebSocket-Key").$this->listener->so->wsAcceptKey,true));
        $this->serverHeaders->setStatus(Status::SWITCHING_PROTOCOLS);
        $this->serverHeaders->set("Connection","Upgrade");
        $this->serverHeaders->set("Upgrade","websocket");
        $this->serverHeaders->set("Sec-WebSocket-Accept",$acceptKey);
        $handshake = $this->serverHeaders->toString()."\r\n";
        \fwrite($this->listener->client, $handshake, strlen($handshake));

        $message = '';
        $valid = false;
        $params = &$this->calculateParameters($message,$valid);
        if($valid) try{
            $this->commits = new \SplDoublyLinkedList();
            //$this->listener->so->websocketConnections->insertLast($this);
            $this->listener->so->websocketConnections[$this->requestId] = $this;
            \call_user_func_array($this->callback,$params);
            if($this->onOpen !== null)
                $this->onOpen->run();
            $this->read();
        } catch (\Exception $ex) {
            echo $ex->getMessage()."\n".$ex->getTraceAsString()."\n";
            $this->close();
            //$this->listener->so->websocketConnections->deleteNode($this);
            unset($this->listener->so->websocketConnections[$this->requestId]);
            $this->uninstall();
        } else
            echo "Error while calculating WebSocket parameters: $message\n";
        
        //$this->close();
    }

    private $unnecessary = 0;
    /**
     * Attempt to read once from the socket
     * */
    public function read():void{
        if(!$this->alive) {
            if($this->unnecessary > 10){ //allow 10 attempts before closing completely
                $this->close();
                //$this->listener->so->websocketConnections->deleteNode($this);
                unset($this->listener->so->websocketConnections[$this->requestId]);
                $this->uninstall();
            }
            $this->unnecessary++;
            return;
        }
        $this->unnecessary = 0;
        if($this->listener->so->wsMtu > 65535)
            $this->listener->so->wsMtu = 65535;
        $mtu = $this->listener->so->wsMtu;
        
        $masked = \fread($this->listener->client, $mtu);
        if ($masked === false) {
            $this->close();
            //$this->listener->so->websocketConnections->deleteNode($this);
            unset($this->listener->so->websocketConnections[$this->requestId]);
            $this->uninstall();
        } else {
            $unpacked = \unpack("C*", $masked);
            $length = \count($unpacked);
            $start = \round(microtime(true) * 1000000);
            for($i=1;$i<=$length;$i++){
                $this->unmask($unpacked[$i]);
            }
            $end = \round(microtime(true) * 1000000);
            $t = $end - $start;
            echo ("it took $t micros to unpack and unmask.\n");
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


    private const FIRST_BYTE = 0, SECOND_BYTE = 1, LENGTH2 = 2, LENGTH8 = 3, MASK = 4, PAYLOAD = 5, DONE = 6;
    private int $lengthKey = 0, $reading = self::FIRST_BYTE, $lengthIndex = 0, $maskIndex = 0, $payloadIndex = 0,
            $payloadLength = 0;
    // private boolean fin,rsv1,rsv2,rsv3;
    private $opcode;
    private /*?array*/ $payload = null, $mask = null, $length = null;
    private bool $isContinuation = false;
    private bool $isFinal = false;
    private array $listOfPayloads = [];
    
    private $startTime = 0;
    private $endTime;

    private function unmask(int $b){
        switch ($this->reading) {
            case self::FIRST_BYTE:
                $start = round(microtime(true) * 100000);
                $this->isFinal = (($b & 0x80) !== 0);
                // rsv1 = ((b & 0x40) != 0);
                // rsv2 = ((b & 0x20) != 0);
                // rsv3 = ((b & 0x10) != 0);
                if($this->startTime === 0)
                    $this->startTime = round(microtime(true) * 100000);
                
                $this->opcode = ($b & 0x0F);
                $this->isContinuation = $this->opcode === 0;
                if ($this->opcode == 0x8) { // fin
                    $this->close();
                    //$this->listener->so->websocketConnections->deleteNode($this);
                    unset($this->listener->so->websocketConnections[$this->requestId]);
                    $this->uninstall();
                }
                $this->mask = [null,null,null,null];
                $this->reading = self::SECOND_BYTE;
                $end = round(microtime(true) * 100000);
                echo "FIRST_BYTE took ".($end - $start)."ms\n";
            break;
            case self::SECOND_BYTE:
                $start = round(microtime(true) * 100000);
                $this->lengthKey = $b & 127;
                if ($this->lengthKey <= 125) {
                    $this->length = [null];
                    $this->length[0] = $this->lengthKey;
                    $this->payloadLength = $this->lengthKey & 0xff;
                    $this->reading = self::MASK;
                } else if ($this->lengthKey == 126) {
                    $this->reading = self::LENGTH2;
                    $this->length = [null,null];
                } else if ($this->lengthKey == 127) {
                    $this->reading = self::LENGTH8;
                    $this->length = [null,null,null,null,null,null,null,null];
                }
                $end = round(microtime(true) * 100000);
                echo "SECOND_BYTE took ".($end - $start)."ms\n";
            break;
            case self::LENGTH2:
                $start = round(microtime(true) * 100000);
                $this->length[$this->lengthIndex] = $b;
                $this->lengthIndex++;
                if ($this->lengthIndex === 2) {
                    $this->payloadLength = (($this->length[0] & 0xff) << 8) | ($this->length[1] & 0xff);
                    $this->reading = self::MASK;
                }
                $end = round(microtime(true) * 100000);
                echo "LENGTH2 took ".($end - $start)."ms\n";
            break;
            case self::LENGTH8:
                $start = round(microtime(true) * 100000);
                $this->length[$this->lengthIndex] = $b;
                $this->lengthIndex++;
                if ($this->lengthIndex === 8) {
                    $this->payloadLength = $this->length[0] & 0xff;
                    $length_length = count($this->length);
                    for ($i = 1; $i < $length_length; $i++) {
                        $this->payloadLength = (($this->payloadLength) << 8) | ($this->length[$i] & 0xff);
                    }
                    $this->reading = self::MASK;
                }
                $end = round(microtime(true) * 100000);
                echo "LENGTH8 took ".($end - $start)."ms\n";
                break;
            case self::MASK:
                $start = round(microtime(true) * 100000);
                $this->mask[$this->maskIndex] = $b;
                $this->maskIndex++;
                if ($this->maskIndex === 4) {
                    $this->reading = self::PAYLOAD;
                    // int l = (int)ByteBuffer.wrap(length).getLong();
                    //$this->payload = array_fill(0,$this->payloadLength,null);
                }
                $end = round(microtime(true) * 100000);
                echo "MASK took ".($end - $start)."ms\n";
            break;
            case self::PAYLOAD:
                
                //$payload_length = count($this->payload);
                if ($this->payloadLength === 0) {
                    $start = round(microtime(true) * 100000);
                    //$this->message = new WebSocketCommit($this->payload);
                    //onMessage(this.message);

                    if(null !== $this->onMessage){
                        //$payload = implode('',$this->payload);
                        $payload = '';
                        $this->listOfPayloads[] = $payload;
                        if($this->isFinal){
                            //$res = implode('',$this->listOfPayloads);
                            $res = '';
                            $this->listOfPayloads = [];
                            $this->endTime = round(microtime(true) * 100000);
                            echo "total time: ".($this->endTime-$this->startTime)."\n";
                            $this->startTime = 0;
                            $this->onMessage->run($res);
                        }
                    }
                    
                    $end = round(microtime(true) * 100000);
                    echo "PAYLOAD BREAK took ".($end - $start)."ms\n";
                    break;
                }
                /*try {
                    $this->payload[$this->payloadIndex] = chr(($b ^ $this->mask[$this->payloadIndex % 4]));
                } catch (\Exception $e) {
                    echo $e->getTraceAsString()."\n";
                }*/
                $this->payloadIndex++;
                if ($this->payloadIndex === $this->payloadLength) {
                    echo "here?\n";
                    $this->reading = self::DONE;
                    $start = round(microtime(true) * 100000);
                    
                    //$this->message = new WebSocketCommit($this->payload);
                    
                    if(null !== $this->onMessage){
                        //$payload = implode('',$this->payload);
                        $payload = '';
                        $this->listOfPayloads[] = $payload;
                        if( $this->isFinal){
                            //$res = implode('',$this->listOfPayloads);
                            $res = '';
                            $this->listOfPayloads = [];
                            $this->endTime = round(microtime(true) * 100000);
                            echo "time spent: ".($this->endTime-$this->startTime)."\n";
                            $this->startTime = 0;
                            $this->onMessage->run($res);
                        }
                    }
                    $this->lengthKey = 0;
                    $this->reading = self::FIRST_BYTE;
                    $this->lengthIndex = 0;
                    $this->payloadLength = 0;
                    $this->maskIndex = 0;
                    $this->payloadIndex = 0;
                    $this->payload = null;
                    $this->mask = null;
                    $this->length = null;
                    $end = round(microtime(true) * 100000);
                    echo "DONE took ".($end - $start)."ms\n";
                }
            break;
        }
    }

    private function encodeAndPushBytes(string $messageBytes, bool $binary):void {
        fflush($this->listener->client);
        // We need to set only FIN and Opcode.
        
        if(!fwrite($this->listener->client,$binary?chr(0b10000010):chr(0b10000001))){
            $this->close();
            //$this->listener->so->websocketConnections->deleteNode($this);
            unset($this->listener->so->websocketConnections[$this->requestId]);
            $this->uninstall();
            return;
        }

        $message_length = strlen($messageBytes);
        // Prepare the payload length.
        if ($message_length <= 125) {
            if(!fwrite($this->listener->client,chr($message_length))){
                $this->close();
                //$this->listener->so->websocketConnections->deleteNode($this);
                unset($this->listener->so->websocketConnections[$this->requestId]);
                $this->uninstall();
                return;
            }
        } else { // We assume it is 16 bit length. Not more than that.
            if(!fwrite($this->listener->client,chr(0b01111110))){
                $this->close();
                //$this->listener->so->websocketConnections->deleteNode($this);
                unset($this->listener->so->websocketConnections[$this->requestId]);
                $this->uninstall();
                return;
            }

            $b1 = ($message_length >> 8) & 0xff;
            $b2 = $message_length & 0xff;
            if(!fwrite($this->listener->client,chr($b1))){
                $this->close();
                //$this->listener->so->websocketConnections->deleteNode($this);
                unset($this->listener->so->websocketConnections[$this->requestId]);
                $this->uninstall();
                return;
            }
            if(!fwrite($this->listener->client,chr($b2))){
                $this->close();
                //$this->listener->so->websocketConnections->deleteNode($this);
                unset($this->listener->so->websocketConnections[$this->requestId]);
                $this->uninstall();
                return;
            }
        }
        $test = str_replace('a','',$messageBytes);
        // Write the data.
        if(!fwrite($this->listener->client,$messageBytes)){
            $this->close();
            //$this->listener->so->websocketConnections->deleteNode($this);
            unset($this->listener->so->websocketConnections[$this->requestId]);
            $this->uninstall();
            return;
        }
        fflush($this->listener->client);
    }

    public function commit($data):void{
        if(is_string($data)){
            $strlength = strlen($data);
            if($strlength > self::MAX_CHUNK_SIZE){
                $pieces = str_split($data,65535);
                $max = count($pieces);
                for($i = 0; $i < $max; $i++){
                    $this->commit($pieces[$i]);
                }
                return;
            }
        }
        $this->commits->push(new WebSocketCommit($data));
    }

    public function push(int $count=-1,bool $binary=false):void{
        $this->commits->setIteratorMode(\SplDoublyLinkedList::IT_MODE_DELETE);
        $i = 0;
        for ($this->commits->rewind(); $this->commits->valid(); $this->commits->next()) {
            $commit = $this->commits->current();
            $contents = &$commit->getData();
            $length = strlen($contents);
            $this->encodeAndPushBytes($contents,$binary);
            
            $i++;
            if($count > 0 && $i >= $count)
                break;
        }
    }
    
    const GROUP_MANAGER = null;
}