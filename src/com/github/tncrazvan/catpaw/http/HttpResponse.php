<?php
namespace com\github\tncrazvan\catpaw\http;

use com\github\tncrazvan\catpaw\http\HttpHeaders;

class HttpResponse{
    private HttpHeaders $headers;
    private $body;
    public function __construct($headers=null,$body=null){
        if($body === null) $body = "";
        if(is_array($headers)){
            $this->headers = new HttpHeaders();
            foreach($headers as $key => &$value){
                if($key === "Status"){
                    $this->headers->setStatus($value);
                }else
                    $this->headers->set($key,$value);
            }
        }else if($headers === null){
            $this->headers = new HttpHeaders();
        }else{
            $this->headers = $headers;
        }
        
        $this->body = $body;
    }

    public function &getHeaders():HttpHeaders{
        return $this->headers;
    }

    public function &getBody(){
        if(\is_array($this->body) || \is_object($this->body)){
            $result = json_encode($this->body);
            return $result;
        }
        return $this->body;
    }

    public function toString():string{
        return $this->headers->toString()."\r\n".$this->body;
    }
}