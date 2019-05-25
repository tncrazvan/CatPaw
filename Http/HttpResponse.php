<?php
namespace com\github\tncrazvan\CatPaw\Http;

use com\github\tncrazvan\CatPaw\Http\HttpHeader;

class HttpResponse{
    private $header,$body;
    public function __construct($header=null, string $body=""){
        if(is_array($header)){
            $h = new HttpHeader();
            foreach($header as $key => &$value){
                if($key === "Status"){
                    $value = "HTTP/1.1 $value";
                }
                $h->set($key,$value);
            }
            $this->header = $h;
        }else{
            $this->header = $header;
        }
        $this->body = $body;
    }

    public function &getHeader():HttpHeader{
        return $this->header;
    }

    public function &getBody():string{
        return $this->body;
    }

    public function &toString():string{
        return $this->header->toString()."\n\n".$this->body;
    }
}