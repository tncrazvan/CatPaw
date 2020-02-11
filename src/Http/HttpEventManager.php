<?php
namespace com\github\tncrazvan\catpaw\http;

use com\github\tncrazvan\catpaw\tools\Http;
use com\github\tncrazvan\catpaw\tools\Server;
use com\github\tncrazvan\catpaw\tools\Status;
use com\github\tncrazvan\catpaw\tools\Strings;
use com\github\tncrazvan\catpaw\http\HttpCommit;
use com\github\tncrazvan\catpaw\tools\LinkedList;
use com\github\tncrazvan\catpaw\http\EventManager;
use com\github\tncrazvan\catpaw\http\HttpResponse;

abstract class HttpEventManager extends EventManager{
    public 
        $commit = true,
        $defaultHeader=true,
        $serve = null;
    public static $connections = null;

    public function run():void{
        if(self::$connections == null){
            self::$connections = new LinkedList();
        }
        $filename = self::$webRoot."/".$this->listener->resource[0];
        if($this->serve === null){
            $this->send(new HttpResponse([
                "Status"=>Status::NOT_FOUND
            ]));
        }else if($this->listener->resource[0] === "favicon.ico"){
            if(!\file_exists($filename)){
                $this->send(new HttpResponse([
                    "Status"=>Status::NOT_FOUND
                ]));
            }else{
                $response = Http::getFile($this->listener->requestHeaders,$filename);
                if($this->commit){
                    $response = $response->toString();
                    $chunks = str_split($response,1024);
                    for($i=0,$len=count($chunks);$i<$len;$i++){
                        if($i === $len -1)
                            $this->commit($chunks[$i],\strlen($chunks[$i]));
                        else
                            $this->commit($chunks[$i]);
                    }
                }else {
                    $this->send(response);
                }
            }
        }else{
            if(file_exists($filename)){
                if(!is_dir($filename)){
                    $response = Http::getFile($this->listener->requestHeaders,$filename);
                    if($this->commit){
                        $response = $response->toString();
                        $chunks = str_split($response,1024);
                        for($i=0,$len=count($chunks);$i<$len;$i++){
                            if($i === $len -1)
                                $this->commit($chunks[$i],\strlen($chunks[$i]));
                            else
                                $this->commit($chunks[$i]);
                        }
                    }else {
                        $this->send($response);
                    }
                }else{
                    $response = $this->{$this->serve}();
                    if($this->commit){
                        if(!is_a($response,HttpResponse::class))
                            $response = new HttpResponse($this->serverHeader,$response);
                        $response = $response->toString();
                        $chunks = str_split($response,1024);
                        for($i=0,$len=count($chunks);$i<$len;$i++){
                            if($i === $len -1)
                                $this->commit($chunks[$i],\strlen($chunks[$i]));
                            else
                                $this->commit($chunks[$i]);
                        }
                    }else{
                        $this->send($response);
                    }
                }
            }else{
                $response = $this->{$this->serve}();
                if($this->commit){
                    if(!is_a($response,HttpResponse::class))
                        $response = new HttpResponse($this->serverHeader,$response);
                    $response = $response->toString();
                    $chunks = str_split($response,1024);
                    for($i=0,$len=count($chunks);$i<$len;$i++){
                        if($i === $len -1)
                            $this->commit($chunks[$i],\strlen($chunks[$i]));
                        else
                            $this->commit($chunks[$i]);
                    }
                }else{
                    $this->send($response);
                }
            }
        }
        if(!$this->commit){
            if(method_exists($this, "onClose"))
                $this->onClose();
            $this->close();
            self::$connections->deleteNode($this);
        }else{
            self::$connections->insertLast($this);
        }
    }
    /**
     * Checks if event is alive.
     * @return bool true if the current event is alive, otherwise false.
     */
    public function isAlive():bool{
        return $this->alive;
    }

    private $commits = null;
    public function commit(&$data,int $length = 1024):void{
        if($this->commits === null)
            $this->commits = new LinkedList();
        $this->commits->insertLast(new HttpCommit($data,$length));
    }

    public function push(int $count=-1){
        if($this->commits === null)
            $this->commits = new LinkedList();
        $i = 0;
        while(!$this->commits->isEmpty() && ($count < 0 || ($count > 0 && $i < $count))){
            $httpCommit = $this->commits->getFirstNode();
            $this->commits->deleteFirstNode();
            if($httpCommit === null){
                $i++;
                continue;
            }
            $httpCommit = $httpCommit->readNode();
            if(!@fwrite($this->listener->client, $httpCommit->getData(), $httpCommit->getLength())){
                if(method_exists($this, "onClose"))
                $this->onClose();
                $this->close();
                self::$connections->deleteNode($this);
            }
            $i++;
        }
    }

    /**
     * Send data to the client.
     * @param string $data data to be sent to the client.
     * @return int number of bytes sent to the client. Returns -1 if an error occured.
     */
    private function send($data):int{
        if(!is_a($data,HttpResponse::class))
            return $this->send(new HttpResponse($this->serverHeader,$data));
        
        try{
            if($this->alive){
                $body = &$data->getBody();
                $accepted = preg_split("/\\s*,\\s*/",$this->listener->requestHeaders->get("Accept-Encoding"));
                if(Server::$compress !== null && Strings::compress($type,$body,Server::$compress,$accepted)){
                    $len = strlen($body);
                    $data->getHeaders()->set("Content-Encoding",$type);
                    $data->getHeaders()->set("Content-Length",$len);
                }else{
                    $len = strlen($body);
                }
                $header = ($data->getHeaders()->toString())."\r\n";
                $bytes = @fwrite($this->listener->client, $header, strlen($header));
                $bytes += @fwrite($this->listener->client, $body, $len);
                return $bytes;
            }else{
                return -2;
            }
        } catch (Exception $ex) {
            return -1;
        }
    }
}
