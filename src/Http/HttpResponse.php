<?php
namespace com\github\tncrazvan\catpaw\http;

use com\github\tncrazvan\catpaw\tools\Server;
use com\github\tncrazvan\catpaw\tools\Strings;
use com\github\tncrazvan\catpaw\http\HttpHeader;

class HttpResponse{
    private $header,$body;
    public function __construct($header=null,$body=null){
        if($body === null) $body = "";
        if(is_array($header)){
            $this->header = new HttpHeader();
            foreach(Server::$header as $key => &$value){
                if($key === "Status"){
                    $value = "HTTP/1.1 $value";
                }
                $this->header->set($key,$value);
            }
            foreach($header as $key => &$value){
                if($key === "Status"){
                    $value = "HTTP/1.1 $value";
                }
                $this->header->set($key,$value);
            }
        }else if($header === null){
            $this->header = new HttpHeader();
        }else{
            $this->header = $header;
        }
        
        $this->body = $body;
    }

    public function &getHeader():HttpHeader{
        return $this->header;
    }

    public function &getBody():string{
        if(\is_array($this->body) || \is_object($this->body)){
            $result = json_encode($this->body);
            return $result;
        }
        return $this->body;
    }

    public function &toString():string{
        return $this->header->toString()."\n\n".$this->body;
    }
}