<?php
namespace com\github\tncrazvan\catpaw\websocket;

use com\github\tncrazvan\catpaw\tools\LinkedList;

abstract class WebSocketEventOnMessage{
    public abstract function run(LinkedList &$fragments):void;

    public static function joinFragments(LinkedList $fragments,string &$message,$iteratorMode = LinkedList::IT_MODE_DELETE):void{
        $fragments->iterate($iteratorMode,function(\SplFixedArray $payload) use(&$message){
            $message .= pack("C*",...$payload);
        });
    }
}