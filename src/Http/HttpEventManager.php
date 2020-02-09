<?php
namespace com\github\tncrazvan\catpaw\http;

use Exception;
use com\github\tncrazvan\catpaw\tools\Http;
use com\github\tncrazvan\catpaw\tools\Mime;
use com\github\tncrazvan\catpaw\tools\Server;
use com\github\tncrazvan\catpaw\tools\Status;
use com\github\tncrazvan\catpaw\tools\Strings;
use com\github\tncrazvan\catpaw\http\EventManager;
use com\github\tncrazvan\catpaw\http\HttpResponse;

abstract class HttpEventManager extends EventManager{
    protected $defaultHeader=true;
    
    /**
     * Checks if event is alive.
     * @return bool true if the current event is alive, otherwise false.
     */
    public function isAlive():bool{
        return $this->alive;
    }
    /**
     * Send data to the client.
     * @param string $data data to be sent to the client.
     * @return int number of bytes sent to the client. Returns -1 if an error occured.
     */
    public function send($data):int{
        if(!is_a($data,HttpResponse::class)){
            return $this->send(new HttpResponse($this->serverHeader,$data));
        }
        try{
            if($this->alive){
                $body = &$data->getBody();
                $accepted = preg_split("/\\s*,\\s*/",$this->clientHeader->get("Accept-Encoding"));
                if(Server::$compress !== null && Strings::compress($type,$body,Server::$compress,$accepted)){
                    $len = strlen($body);
                    $data->getHeader()->set("Content-Encoding",$type);
                    $data->getHeader()->set("Content-Length",$len);
                }else{
                    $len = strlen($body);
                }
                $header = ($data->getHeader()->toString())."\r\n";
                $bytes = @fwrite($this->client, $header, strlen($header));
                $bytes += @fwrite($this->client, $body, $len);
                return $bytes;
            }else{
                return -2;
            }
        } catch (Exception $ex) {
            return -1;
        }
    }
}
