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
        $filename = Server::$webRoot."/".$this->location;
        if($this->location === "favicon.ico"){
            if(!\file_exists($filename)){
                $this->send(new HttpResponse([
                    "Status"=>Status::NOT_FOUND
                ]));
            }else{
                $this->send(Http::getFile($this->clientHeader,$filename));
            }
        }else{
            if(file_exists($filename)){
                if(!is_dir($filename)){
                    $this->send(Http::getFile($this->clientHeader,$filename));
                }else{
                    $this->send($this->onControllerRequest($this->location));
                }
            }else{
                $this->send($this->onControllerRequest($this->location));
            }
        }
        
        $this->close();
    }

    /**
     * Send data to the client.
     * @param string $data data to be sent to the client.
     * @return int number of bytes sent to the client. Returns -1 if an error occured.
     */
    protected function send($data):int{
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

    protected abstract function &onControllerRequest(string &$location);
}
