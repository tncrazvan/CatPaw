<?php
namespace com\github\tncrazvan\catpaw\http;

use com\github\tncrazvan\catpaw\EventManager;
use com\github\tncrazvan\catpaw\http\HttpCommit;
use com\github\tncrazvan\catpaw\http\HttpResponse;
use com\github\tncrazvan\catpaw\tools\Http;
use com\github\tncrazvan\catpaw\tools\LinkedList;
use com\github\tncrazvan\catpaw\tools\Status;
use com\github\tncrazvan\catpaw\tools\Strings;

abstract class HttpEventManager extends EventManager{
    public 
        $isCommit = true,
        $defaultHeader=true,
        $serve = null;
    public $onClose = null;
    public static $connections = null;

    public function run():void{
        if($this->listener->so->httpConnections == null){
            $this->listener->so->httpConnections = new LinkedList();
        }
        $message = '';
        $params = &$this->calculateParameters($message);
        $responseObject = new HttpResponse([
            "Status"=>Status::BAD_REQUEST
        ],$message);
        if($params !== null){
            try{
                try{
                    $responseObject = call_user_func_array($this->serve,$params);
                    if(is_a($responseObject,HttpClassEvent::class)){
                        $responseObject = $responseObject->run();
                    }
                }catch(\TypeError $ex){
                    $responseObject = new HttpResponse([
                        "Status"=>Status::INTERNAL_SERVER_ERROR
                    ],$ex->getMessage()."\n".$ex->getTraceAsString());
                }catch(\Exception $ex){
                    $responseObject = new HttpResponse([
                        "Status"=>Status::INTERNAL_SERVER_ERROR
                    ],$ex->getMessage()."\n".$ex->getTraceAsString());
                }
            }catch(HttpEventException $ex){
                $responseObject = new HttpResponse([
                    "Status"=>$ex->getStatus()
                ],$ex->getMessage()."\n".$ex->getTraceAsString());
            }catch(\Exception $ex){
                $responseObject = new HttpResponse([
                    "Status"=>Status::INTERNAL_SERVER_ERROR
                ],$ex->getMessage()."\n".$ex->getTraceAsString());
            }
        }
        
        if(!is_a($responseObject,HttpResponse::class))
            $responseObject = new HttpResponse($this->serverHeaders,$responseObject);

        $responseHeader = $responseObject->getHeaders();
        $responseHeader->initialize($this);
        $responseHeader->mix($this->serverHeaders);

        if($this->isCommit){
            if(!$responseHeader->has("Content-Length")){
                $responseHeader->set("Content-Length",''.strlen($responseObject->getBody()));
            }
            $response = $responseObject->toString();
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
        
        if(!$this->isCommit){
            if($this->onClose !== null)
                $this->onClose->run();
            $this->close();
            $this->listener->so->httpConnections->deleteNode($this);
        }else{
            $this->listener->so->httpConnections->insertLast($this);
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

    public function push(int $count=-1):bool{
        if($this->commits === null)
            $this->commits = new LinkedList();
        $i = 0;
        $isEmpty = $this->commits->isEmpty();
        while(!$isEmpty && ($count < 0 || ($count > 0 && $i < $count))){
            $httpCommit = $this->commits->getFirstNode();
            $this->commits->deleteFirstNode();
            if($httpCommit === null){
                $i++;
                continue;
            }
            $httpCommit = $httpCommit->readNode();
            $payload = $httpCommit->getData();
            $len = $httpCommit->getLength();
            if(!@fwrite($this->listener->client, $httpCommit->getData(), $httpCommit->getLength())){
                if($this->onClose !== null)
                    $this->onClose->run();
                $this->close();
                $this->listener->so->httpConnections->deleteNode($this);
                $this->uninstall();
            }
            $i++;
            $isEmpty = $this->commits->isEmpty();
        }
        if($isEmpty){
            if($this->onClose !== null)
                $this->onClose->run();
            $this->close();
            $this->listener->so->httpConnections->deleteNode($this);
            $this->uninstall();
        }
        return $isEmpty;
    }

    /**
     * Send data to the client.
     * @param string $data data to be sent to the client.
     * @return int number of bytes sent to the client. Returns -1 if an error occured.
     */
    private function send(HttpCommit &$data):int{
        if(!is_a($data,HttpResponse::class))
            return $this->send(new HttpResponse($this->serverHeader,$data));
        
        try{
            if($this->alive){
                $headers = &$data->getHeaders();
                $body = &$data->getBody();
                $accepted = preg_split("/\\s*,\\s*/",$this->listener->requestHeaders->get("Accept-Encoding"));
                if($this->listener->so->compress !== null && Strings::compress($type,$body,$this->listener->so->compress,$accepted)){
                    $len = strlen($body);
                    $headers->set("Content-Encoding",$type);
                    //$headers->set("Content-Length",$len);
                }else{
                    $len = strlen($body);
                }

                if(!$headers->has("Content-Length")){
                    $headers->set("Content-Length",$len);
                }

                $header = ($headers->toString())."\r\n";
                $bytes = @fwrite($this->listener->client, $header, strlen($header));
                $bytes += @fwrite($this->listener->client, $body, $len);
                return $bytes;
            }else{
                return -2;
            }
        } catch (\Exception $ex) {
            return -1;
        }
    }
}
