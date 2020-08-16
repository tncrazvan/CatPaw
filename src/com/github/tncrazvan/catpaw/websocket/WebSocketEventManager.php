<?php

namespace com\github\tncrazvan\catpaw\websocket;

use com\github\tncrazvan\catpaw\tools\LinkedList;
use com\github\tncrazvan\catpaw\EventManager;
use com\github\tncrazvan\catpaw\tools\Status;
use SplDoublyLinkedList;

abstract class WebSocketEventManager extends EventManager{
    public array $subscriptions = [];
    public \Closure $callback;

    public ?WebSocketEventOnOpen $onOpen = null;
    public ?WebSocketEventOnMessage $onMessage = null;
    public ?WebSocketEventOnClose $onClose = null;
    private \SplDoublyLinkedList $commits;
    
    private const MAX_CHUNK_SIZE = 65535;
    public function __construct(){
        $scope = &$this;
        $this->_commit_fn = function($data,&$dataByReference = null) use(&$scope){
            if($data === false)
                $scope->commit($dataByReference);
            else
                $scope->commit($data);
        };
    }
    public function run():void{
        $acceptKey = \base64_encode(sha1($this->listener->requestHeaders->get("Sec-WebSocket-Key").$this->listener->so->wsAcceptKey,true));
        $this->serverHeaders->setStatus(Status::SWITCHING_PROTOCOLS);
        $this->serverHeaders->set("Connection","Upgrade");
        $this->serverHeaders->set("Upgrade","websocket");
        $this->serverHeaders->set("Sec-WebSocket-Accept",$acceptKey);
        $handshake = $this->serverHeaders->toString()."\r\n";
        @\stream_socket_sendto($this->listener->client, $handshake);

        $message = '';
        $valid = false;
        $params = &$this->calculateParameters($message,$valid);
        if($valid) try{
            $this->commits = new \SplDoublyLinkedList();
            $this->listOfFragments = new LinkedList();
            //$this->payload = new LinkedList();
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
        
        $masked = \stream_get_contents($this->listener->client, -1);
        if ($masked === false) {
            $this->close();
            //$this->listener->so->websocketConnections->deleteNode($this);
            unset($this->listener->so->websocketConnections[$this->requestId]);
            $this->uninstall();
        } else {
            if($masked === '') return;
            //$start = \round(microtime(true) * 1000);
            $unpacked = \unpack("C*", $masked);
            //$length = \count($unpacked);
            /*$end = \round(microtime(true) * 1000);
            $t = $end - $start;
            echo ("it took $t ms to unpack and count.\n");*/
            
            //$start = (\round(microtime(true) * 1000));
            $j = $this->unmask($unpacked);
            while($j > 0){
                $j = $this->unmask($unpacked,$j);
            }
            //echo ("it took ".(\round(microtime(true) * 1000) - $start)." ms.\n##########################################\n");
        }

    }

    private const FIRST_BYTE = 0, SECOND_BYTE = 1, LENGTH2 = 2, LENGTH8 = 3, MASK = 4, PAYLOAD = 5, DONE = 6;
    private int $lengthKey = 0, $reading = self::FIRST_BYTE, $lengthIndex = 0, $maskIndex = 0, $payloadIndex = 0,
            $payloadLength = 0;
    // private boolean fin,rsv1,rsv2,rsv3;
    private $opcode;
    private /*?array*/ /* $payload = null,  */$mask = null, $length = null;
    private \SplFixedArray $payload;
    private bool $isContinuation = false;
    private bool $isFinal = false;
    private LinkedList $listOfFragments;


    private function unmask(array &$unpacked,int $shift = 0):int{
        if($this->reading === self::PAYLOAD){
            return $this->unmask_payload($unpacked);
        }else{
            $this->unmask_first_byte($unpacked[1+$shift]);
            $this->unmask_second_byte($unpacked[2+$shift]);
            switch ($this->reading) {
                case self::MASK:
                    $this->unmask_mask([
                        $unpacked[3+$shift],$unpacked[4+$shift],
                        $unpacked[5+$shift],$unpacked[6+$shift]
                    ]);
                    return $this->unmask_payload($unpacked,7+$shift);
                break;
                case self::LENGTH2:
                    $this->unmask_length2([$unpacked[3+$shift],$unpacked[4+$shift]]);
                    $this->unmask_mask([
                        $unpacked[5+$shift],$unpacked[6+$shift],
                        $unpacked[7+$shift],$unpacked[8+$shift]
                    ]);
                    return $this->unmask_payload($unpacked,9+$shift);
                break;
                case self::LENGTH8:
                    $this->unmask_length8([
                        $unpacked[3+$shift],$unpacked[4+$shift],
                        $unpacked[5+$shift],$unpacked[6+$shift],
                        $unpacked[7+$shift],$unpacked[8+$shift],
                        $unpacked[9+$shift],$unpacked[10+$shift]
                    ]);
                    $this->unmask_mask([
                        $unpacked[11+$shift],$unpacked[12+$shift],
                        $unpacked[13+$shift],$unpacked[14+$shift]
                    ]);
                    return $this->unmask_payload($unpacked,15+$shift);
                break;
            }
        }
        return -1;
    }

    private function unmask_first_byte(int $b):void{
        //$start = round(microtime(true) * 100000);
        $this->isFinal = (($b & 0x80) !== 0);
        // rsv1 = ((b & 0x40) != 0);
        // rsv2 = ((b & 0x20) != 0);
        // rsv3 = ((b & 0x10) != 0);
        
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
        //$end = round(microtime(true) * 100000);
        //echo "FIRST_BYTE took ".($end - $start)."ms\n";
    }

    private function unmask_second_byte(int $b):void{
        //$start = round(microtime(true) * 100000);
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
        //$end = round(microtime(true) * 100000);
        //echo "SECOND_BYTE took ".($end - $start)."ms\n";
    }

    private function unmask_length2(array $b):void{
        //$start = round(microtime(true) * 100000);
        $l = count($b);
        for($j=0;$j<$l;$j++){
            $this->length[$this->lengthIndex] = $b[$j];
            $this->lengthIndex++;
            if ($this->lengthIndex === 2) {
                $this->payloadLength = (($this->length[0] & 0xff) << 8) | ($this->length[1] & 0xff);
                $this->reading = self::MASK;
            }
        }
        
        //$end = round(microtime(true) * 100000);
        //echo "LENGTH2 took ".($end - $start)."ms\n";
    }

    private function unmask_length8(array $b):void{
        //$start = round(microtime(true) * 100000);
        $l = count($b);
        for($j=0;$j<$l;$j++){
            $this->length[$this->lengthIndex] = $b[$j];
            $this->lengthIndex++;
            if ($this->lengthIndex === 8) {
                $this->payloadLength = $this->length[0] & 0xff;
                $length_length = count($this->length);
                for ($i = 1; $i < $length_length; $i++) {
                    $this->payloadLength = (($this->payloadLength) << 8) | ($this->length[$i] & 0xff);
                }
                $this->reading = self::MASK;
            }
        }
        //$end = round(microtime(true) * 100000);
        //echo "LENGTH8 took ".($end - $start)."ms\n";
    }

    private function unmask_mask(array $b):void{
        //$start = round(microtime(true) * 100000);
        for($j=0;$j<4;$j++){
            $this->mask[$this->maskIndex] = $b[$j];
            $this->maskIndex++;
            if ($this->maskIndex === 4) {
                $this->reading = self::PAYLOAD;
                // int l = (int)ByteBuffer.wrap(length).getLong();
                //$this->payload = array_fill(0,$this->payloadLength,null);
                //$this->payload = new \SplFixedArray($this->payloadLength);
                $this->listOfFragments->push(new \SplFixedArray($this->payloadLength));
            }
        }
        //$end = round(microtime(true) * 100000);
        //echo "MASK took ".($end - $start)."ms\n";
    }

    private function unmask_payload(array &$b,int $offset = 1):int{
        $l=count($b);
        try{
            for($j=$offset;$j<=$l;$j++){
                //$this->payload[$this->payloadIndex] = chr(($b[$j] ^ $this->mask[($this->payloadIndex) % 4]));
                //$this->payload->push(chr(($b[$j] ^ $this->mask[($this->payloadIndex) % 4])));
                //$this->payload[$this->payloadIndex] = ($b[$j] ^ $this->mask[($this->payloadIndex) % 4]);
                $this->listOfFragments->top()[$this->payloadIndex] = ($b[$j] ^ $this->mask[($this->payloadIndex) % 4]);
    
                $this->payloadIndex++;
                
    
                
                if ($this->payloadIndex === $this->payloadLength) {
                    
                    $this->reading = self::DONE;
    
                    if(null !== $this->onMessage){
                        //$payload = $this->payload;
                        //$this->listOfFragments->push($payload);
                        if($this->isFinal){
                            $this->onMessage->run($this->listOfFragments);
                            $this->listOfFragments = new LinkedList();
                        }
                        
                    }
    
                    $this->lengthKey = 0;
                    $this->reading = self::FIRST_BYTE;
                    $this->lengthIndex = 0;
                    $this->payloadLength = 0;
                    $this->maskIndex = 0;
                    $this->payloadIndex = 0;
                    //$this->payload = new LinkedList();
                    $this->mask = null;
                    $this->length = null;
    
                    if ($j+1<=$l) 
                        return $j;
                }
                
            }
        }catch(\Exception $e){
            echo $e->getMessage()."\n";
            echo $e->getTraceAsString()."\n";
            $this->close();
            unset($this->listener->so->websocketConnections[$this->requestId]);
            $this->uninstall();
        }catch(\ErrorException $e){
            echo $e->getMessage()."\n";
            echo $e->getTraceAsString()."\n";
            $this->close();
            unset($this->listener->so->websocketConnections[$this->requestId]);
            $this->uninstall();
        }
        return -1;
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

    private function encodeAndPushBytes(string $messageBytes, bool $binary):void {
        fflush($this->listener->client);
        // We need to set only FIN and Opcode.
        
        if(!@\stream_socket_sendto($this->listener->client, $binary?chr(0b10000010):chr(0b10000001))){
            $this->close();
            //$this->listener->so->websocketConnections->deleteNode($this);
            unset($this->listener->so->websocketConnections[$this->requestId]);
            $this->uninstall();
            return;
        }

        $message_length = strlen($messageBytes);
        // Prepare the payload length.
        if ($message_length <= 125) {
            if(!@\stream_socket_sendto($this->listener->client, chr($message_length))){
                $this->close();
                //$this->listener->so->websocketConnections->deleteNode($this);
                unset($this->listener->so->websocketConnections[$this->requestId]);
                $this->uninstall();
                return;
            }
        } else { // We assume it is 16 bit length. Not more than that.
            if(!@\stream_socket_sendto($this->listener->client, chr(0b01111110))){
                $this->close();
                //$this->listener->so->websocketConnections->deleteNode($this);
                unset($this->listener->so->websocketConnections[$this->requestId]);
                $this->uninstall();
                return;
            }

            $b1 = ($message_length >> 8) & 0xff;
            $b2 = $message_length & 0xff;
            if(!@\stream_socket_sendto($this->listener->client, chr($b1))){
                $this->close();
                //$this->listener->so->websocketConnections->deleteNode($this);
                unset($this->listener->so->websocketConnections[$this->requestId]);
                $this->uninstall();
                return;
            }
            if(!stream_socket_sendto($this->listener->client,chr($b2))){
                $this->close();
                //$this->listener->so->websocketConnections->deleteNode($this);
                unset($this->listener->so->websocketConnections[$this->requestId]);
                $this->uninstall();
                return;
            }
        }
        // Write the data.
        if(!stream_socket_sendto($this->listener->client,$messageBytes)){
            $this->close();
            //$this->listener->so->websocketConnections->deleteNode($this);
            unset($this->listener->so->websocketConnections[$this->requestId]);
            $this->uninstall();
            return;
        }
        fflush($this->listener->client);
    }

    public function commit($data,bool $binary = false):void{
        if(is_string($data)){
            $strlength = strlen($data);
            if($strlength > $this->listener->so->wsMtu){
                $pieces = str_split($data,$this->listener->so->wsMtu);
                $max = count($pieces);
                for($i = 0; $i < $max; $i++){
                    $this->commit($pieces[$i],$binary);
                }
                return;
            }
        }else if(is_array($data)){
            $arrlength = count($data);
            if($arrlength > $this->listener->so->wsMtu){
                $pieces = \array_chunk($data,$this->listener->so->wsMtu);
                $max = count($pieces);
                for($i = 0; $i < $max; $i++){
                    $this->commit($pieces[$i],$binary);
                }
                return;
            }
        }else if($data instanceof \SplFixedArray){
            $arrlength = $data->count();
            $pieces = [];
            $c = 0;
            for($i=0;$i<$arrlength;$i++){
                $pieces[] = $data[$i];

                if($i+1 === $this->listener->so->wsMtu || $i+1 === $arrlength){
                    $this->commit($pieces,$binary);
                    $pieces = [];
                    $c++;
                }
            }
            return;
        }

        $this->commits->push(new WebSocketCommit($data,$binary));
    }

    public function push(int $count=-1):void{
        $this->commits->setIteratorMode(\SplDoublyLinkedList::IT_MODE_DELETE);
        $i = 0;
        for ($this->commits->rewind(); $this->commits->valid(); $this->commits->next()) {
            $commit = $this->commits->current();
            $contents = &$commit->getData();
            if(\is_array($contents) || $contents instanceof \SplFixedArray){
                $contents = \pack("C*",...$contents);
            }
            $length = strlen($contents);
            
            $this->encodeAndPushBytes($contents,$commit->isBinary());
            
            $i++;
            if($count > 0 && $i >= $count)
                break;
        }
    }
    
    const GROUP_MANAGER = null;
}