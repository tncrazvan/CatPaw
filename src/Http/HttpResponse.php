<?php
namespace com\github\tncrazvan\catpaw\http;

use com\github\tncrazvan\catpaw\http\HttpHeaders;

class HttpResponse{
    private $header,$body;
    public function __construct($header=null,$body=null){
        if($body === null) $body = "";
        if(is_array($header)){
            $this->header = new HttpHeaders();
            foreach($header as $key => &$value){
                if($key === "Status"){
                    $value = "HTTP/1.1 $value";
                }
                $this->header->set($key,$value);
            }
        }else if($header === null){
            $this->header = new HttpHeaders();
        }else{
            $this->header = $header;
        }
        
        $this->body = $body;
    }

    public function &getHeaders():HttpHeaders{
        return $this->header;
    }

    public function &getBody():string{
        if(\is_array($this->body) || \is_object($this->body)){
            $result = json_encode($this->body);
            return $result;
        }
        return $this->body;
    }

    public function toString():string{
        return $this->header->toString()."\r\n".$this->body;
    }
}