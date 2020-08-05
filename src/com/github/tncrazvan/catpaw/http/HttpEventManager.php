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
    public \Closure $callback;
    public ?HttpEventOnClose $onClose = null;
    public bool $defaultHeader = true;
    public static array $connections = [];
    private \SplDoublyLinkedList $commits;

    public function run():void{
        /*if($this->listener->so->httpConnections == null){
            $this->listener->so->httpConnections = new LinkedList();
        }*/
        $message = '';
        $valid = true;
        $params = &$this->calculateParameters($message,$valid);
        $responseObject = new HttpResponse([
            "Status"=>Status::BAD_REQUEST
        ],$message);
        if($valid){
            try{
                $this->commits = new \SplDoublyLinkedList();
                $responseObject = \call_user_func_array($this->callback,$params);
                if($responseObject instanceof HttpEventInterface){
                    $responseObject = $responseObject->run();
                }
            }catch(\TypeError $ex){
                $responseObject = new HttpResponse([
                    "Status"=>Status::INTERNAL_SERVER_ERROR
                ],$ex->getMessage()."\n".$ex->getTraceAsString());
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

        if(!$responseHeader->has("Content-Length")){
            $responseHeader->set("Content-Length",''.strlen($responseObject->getBody()));
        }

        $chunks = \str_split($responseObject->toString(),$this->listener->so->httpMtu);

        for($i=0,$len=\count($chunks);$i<$len;$i++){
            $this->commit($chunks[$i]);
        }
    
        $this->listener->so->httpConnections[$this->requestId] = $this;
        
    }
    /**
     * Checks if event is alive.
     * @return bool true if the current event is alive, otherwise false.
     */
    public function isAlive():bool{
        return $this->alive;
    }

    public function commit(&$data):void{
        $this->commits->push(new HttpCommit($data));
    }

    public function push(int $count=-1):bool{
        $i = 0 ;
        $this->commits->setIteratorMode(\SplDoublyLinkedList::IT_MODE_DELETE);
        for ($this->commits->rewind(); $this->commits->valid(); $this->commits->next()) {
            $httpCommit = $this->commits->current();
            $contents = &$httpCommit->getData();
            $result = @\stream_socket_sendto($this->listener->client, $contents);
            if(!$result){
                if($this->onClose !== null)
                    $this->onClose->run();
                $this->close();
                unset($this->listener->so->httpConnections[$this->requestId]);
                $this->uninstall();
            }

            $i++;
            if($count > 0 && $i >= $count)
                break;
        }

        //No more commits to send, end connection.
        $isEmpty = $this->commits->isEmpty();
        if($isEmpty){
            if($this->onClose !== null)
                $this->onClose->run();
            $this->close();
            //$this->listener->so->httpConnections->deleteNode($this);
            unset($this->listener->so->httpConnections[$this->requestId]);
            $this->uninstall();
        }
        return $isEmpty;
    }
}
