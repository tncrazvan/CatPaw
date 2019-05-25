<?php
namespace com\github\tncrazvan\CatPaw\Http;

use Exception;
use com\github\tncrazvan\CatPaw\Tools\G;
use com\github\tncrazvan\CatPaw\Tools\Http;
use com\github\tncrazvan\CatPaw\Tools\Mime;
use com\github\tncrazvan\CatPaw\Tools\Strings;
use com\github\tncrazvan\CatPaw\Http\HttpHeader;
use com\github\tncrazvan\CatPaw\Http\EventManager;
use com\github\tncrazvan\CatPaw\Http\HttpResponse;

abstract class HttpEventManager extends EventManager{
    protected 
            $defaultHeader=true,
            $content;
    public function __construct($client,HttpHeader &$clientHeader,string &$content) {
        parent::__construct($client,$clientHeader);
        $this->content=$content;
    }
    
    /**
     * Checks if event is alive.
     * @return bool true if the current event is alive, otherwise false.
     */
    public function isAlive():bool{
        return $this->alive;
    }
    
    /**
     * Execute the current event.
     * 
     * @return void This method is invoked once by the server to trigger all the required components 
     * in order to reply to the http request
     * such as the appropriate http header and requested file or controller.
     * 
     * Be aware that missuse of this method could lead to request loops.
     */
    public function run(){
        $this->findUserLanguages();
        $filename = G::$webRoot."/".$this->location;
        if(file_exists($filename)){
            if(!is_dir($filename)){
                $this->send(Http::getFile($this->clientHeader,$filename));
            }else{
                $this->send($this->onControllerRequest($this->location));
            }
        }else{
            $this->send($this->onControllerRequest($this->location));
        }
        $this->close();
    }
    
    protected abstract function &onControllerRequest(string &$location);
    
    private $firstMessage = true;
    private function sendHeader(HttpHeader &$header):void{
        $string = $header->toString()."\r\n";
        @fwrite($this->client, $string,strlen($string));
        $this->alive=true;
    }
    
    /**
     * Send data to the client.
     * @param string $data data to be sent to the client.
     * @return int number of bytes sent to the client. Returns -1 if an error occured.
     */
    public function send($data=null):int{
        try{
            if($this->alive){
                $dataClass = $data !== null && is_object($data)?\get_class($data):"";
                switch($dataClass){
                    case HttpResponse::class:
                        if($this->firstMessage){
                            $this->firstMessage=false;
                            $this->sendHeader($data->getHeader());
                        }
                        return @fwrite($this->client, $data->getBody(), strlen($data->getBody()));
                    break;
                    default:
                        if($this->firstMessage){
                            $this->firstMessage=false;
                            $this->sendHeader($this->serverHeader);
                        }
                        return @fwrite($this->client, $data, strlen($data));
                    break;
                }
            }else{
                return -2;
            }
        } catch (Exception $ex) {
            return -1;
        }
    }
    
    /**
     * Set the Content-Type field to the response header.
     * @param string $type content type string, such as "text/plain", "text/html" ecc...
     */
    public function setContentType(string $type):void{
        $this->setHeaderField("Content-Type", $type);
    }
}
